<?php

namespace App\Console\Commands;

use App\Jobs\PlaceAutoBidJob;
use App\Models\AiBidSetting;
use App\Models\FreelancerConnection;
use App\Models\Skill;
use App\Services\AutoBidService;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * Artisan command to process auto-bids for all enabled users.
 *
 * Designed to run on a 2-minute schedule via Laravel's scheduler.
 * Processes users sequentially to respect Freelancer API rate limits.
 *
 * Usage:
 *   php artisan autobid:process           # Normal mode
 *   php artisan autobid:process --dry-run # Preview without placing bids
 */
class ProcessAutoBids extends Command
{
    protected $signature = 'autobid:process
                            {--dry-run : Preview actions without placing actual bids}
                            {--user= : Process only a specific user ID}';

    protected $description = 'Scan for new projects and place auto-bids for enabled users';

    public function handle(AutoBidService $autoBidService): int
    {
        $isDryRun = $this->option('dry-run');
        $specificUser = $this->option('user');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE — No bids will be placed.');
        }

        // 1. Fetch all users with auto-bid enabled
        $query = AiBidSetting::where('is_autobid_enabled', true);

        if ($specificUser) {
            $query->where('user_id', $specificUser);
        }

        $totalCount = (clone $query)->count();
        if ($totalCount === 0) {
            $this->info('No users with auto-bid enabled.');
            return self::SUCCESS;
        }

        $this->info("Processing {$totalCount} user(s) with auto-bid enabled...");
        $totalBidsPlaced = 0;

        $query->chunkById(100, function ($settings) use ($autoBidService, $isDryRun, &$totalBidsPlaced) {
            foreach ($settings as $setting) {
            $userId = $setting->user_id;

            // 2. Check if enough time has passed since last scan
            if ($setting->last_scanned_at && $setting->last_scanned_at->copy()->addSeconds($setting->bidding_delay_seconds)->isFuture()) {
                $waitSecs = now()->diffInSeconds($setting->last_scanned_at->copy()->addSeconds($setting->bidding_delay_seconds));
                $this->line("  ⏳ User #{$userId}: Skipping, scanned recently (waiting {$waitSecs}s)");
                continue;
            }

            // 3. Check daily quota
            $remaining = $autoBidService->getRemainingQuota($userId, $setting->max_daily_bids);
            if ($remaining <= 0) {
                $this->line("  🚫 User #{$userId}: Daily quota reached ({$setting->max_daily_bids} bids)");
                $setting->update(['status' => 'quota_reached']);
                continue;
            }

            // 4. Get Freelancer connection
            $connection = FreelancerConnection::where('user_id', $userId)->first();
            if (!$connection || empty($connection->access_token)) {
                $this->line("  ⚠️  User #{$userId}: No valid Freelancer connection, skipping");
                continue;
            }

            // 5. Fetch user's active skills
            $userSkills = Skill::where('user_id', $userId)
                ->where('is_active', true)
                ->whereNotNull('freelancer_job_id')
                ->get();

            $userSkillIds = $userSkills->pluck('freelancer_job_id')
                ->map(fn($id) => (int) $id)
                ->filter()
                ->values()
                ->toArray();

            $userSkillNames = $userSkills->pluck('name')
                ->map(fn($name) => strtolower(trim($name)))
                ->filter()
                ->values()
                ->toArray();

            if (empty($userSkillIds)) {
                $this->line("  ⚠️  User #{$userId}: No active skills configured, skipping");
                continue;
            }

            // 6. Update status to scanning
            $setting->update(['status' => 'scanning']);

            // 7. Fetch latest projects from Freelancer API (fetch pool of 20 to find enough valid matches)
            $projects = $autoBidService->fetchProjects($connection, $userSkillIds, 20);

            if (empty($projects)) {
                $this->line("  📭 User #{$userId}: No projects found from API");
                $setting->update([
                    'status'          => 'idle',
                    'last_scanned_at' => now(),
                ]);
                continue;
            }

            $this->line("  📡 User #{$userId}: Fetched " . count($projects) . " projects, quota remaining: {$remaining}");

            $userBidsPlaced = 0;
            $delaySeconds = 0;

            foreach ($projects as $project) {
                // Stop if quota exhausted
                if ($userBidsPlaced >= $remaining) {
                    $this->line("    🚫 Quota exhausted for user #{$userId}");
                    break;
                }

                $projectId = (int) ($project['id'] ?? 0);
                $projectTitle = $project['title'] ?? 'Untitled';

                // 8. Skip already-bid projects
                if ($autoBidService->hasAlreadyBid($userId, $projectId)) {
                    continue;
                }

                // 9. Verify skill match (at least one skill must match)
                $projectSkillNames = $autoBidService->extractProjectSkills($project);
                $projectJobIds = array_column($project['jobs'] ?? [], 'id');

                $hasIdMatch = !empty(array_intersect($projectJobIds, $userSkillIds));
                $hasNameMatch = !empty(array_intersect($projectSkillNames, $userSkillNames));

                if (!$hasIdMatch && !$hasNameMatch) {
                    continue;
                }

                // 10. Apply user filters
                $filterResult = $autoBidService->passesFilters($setting, $project);
                if (!$filterResult['passes']) {
                    if ($isDryRun) {
                        $this->line("    ❌ #{$projectId} \"{$projectTitle}\" — Filtered: {$filterResult['reason']}");
                    }
                    continue;
                }

                // 11. Build project details for AI proposal generation
                $projectData = [
                    'title'       => $projectTitle,
                    'description' => $project['preview_description'] ?? $project['description'] ?? '',
                    'client_name' => $autoBidService->extractClientName($project),
                    'skills'      => $projectSkillNames,
                ];

                // 12. Generate personalized AI Proposal (no template selection)
                $proposalText = $autoBidService->generateAiProposal($projectData);
                $skillsSnippet = !empty($projectSkillNames) ? implode(', ', array_slice($projectSkillNames, 0, 3)) : 'project skills';
                $reason = "AI Generated Proposal tailored for {$skillsSnippet}";
                $matchScore = 1.0;

                // 13. Calculate bid amount
                $bidStats = $project['bid_stats'] ?? [];
                $budget = $project['budget'] ?? [];
                $bidAmount = $autoBidService->calculateBidAmount($setting, $bidStats, $budget, $project);

                // 14. Dispatch or preview
                if ($isDryRun) {
                    $this->info("    ✅ #{$projectId} \"{$projectTitle}\"");
                    $this->line("       Amount: \${$bidAmount} | Proposal: AI Generated | Score: {$matchScore}");
                    $this->line("       Reason: {$reason}");
                } else {
                    PlaceAutoBidJob::dispatch(
                        $userId,
                        $projectId,
                        $projectTitle,
                        $bidAmount,
                        $setting->delivery_days,
                        $proposalText,
                        null, // No template used
                        $matchScore,
                        $reason
                    )->delay(now()->addSeconds($delaySeconds));

                    $delaySeconds += $setting->bidding_delay_seconds;
                }

                $userBidsPlaced++;
                $totalBidsPlaced++;
            }

            // 15. Update scan tracking
            $newStatus = ($userBidsPlaced >= $remaining) ? 'quota_reached' : 'idle';
            $setting->update([
                'status'          => $newStatus,
                'last_scanned_at' => now(),
            ]);

            $this->line("  ✅ User #{$userId}: " . ($isDryRun ? 'Would place' : 'Dispatched') . " {$userBidsPlaced} bid(s)");
        }
    });

        $this->newLine();
        $this->info("🏁 Done. Total bids " . ($isDryRun ? 'previewed' : 'dispatched') . ": {$totalBidsPlaced}");

        return self::SUCCESS;
    }
}
