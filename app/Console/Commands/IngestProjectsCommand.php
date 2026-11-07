<?php

namespace App\Console\Commands;

use App\Services\AutoBidService;
use App\Services\RedisQuotaService;
use App\Services\ReverseMatchingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Project-Centric Global Auto-Bid Ingestion Command.
 *
 * Ingests newly published projects from Freelancer.com globally in 1 single API hit,
 * filters previously seen projects via Redis in O(1), and performs high-speed reverse matching.
 *
 * Usage:
 *   php artisan autobid:ingest           # Ingest once
 *   php artisan autobid:ingest --dry-run # Preview reverse matching without dispatching bids
 *   php artisan autobid:ingest --daemon  # Continuous daemon loop every 20-30s
 */
class IngestProjectsCommand extends Command
{
    protected $signature = 'autobid:ingest
                            {--limit=50 : Number of active projects to fetch}
                            {--dry-run : Preview matching without dispatching bid jobs}
                            {--daemon : Run as a continuous background daemon every 25 seconds}';

    protected $description = 'Globally ingest active Freelancer projects and perform fast reverse matching for all users';

    public function handle(
        AutoBidService $autoBidService,
        RedisQuotaService $redisQuota,
        ReverseMatchingService $reverseMatching
    ): int {
        $isDryRun = (bool) $this->option('dry-run');
        $isDaemon = (bool) $this->option('daemon');
        $limit = (int) $this->option('limit') ?: 50;

        $this->info("🚀 Initializing Project-Centric Auto-Bid Ingestion...");

        if ($isDryRun) {
            $this->warn("🔍 DRY RUN MODE — No bid jobs will be dispatched.");
        }

        do {
            $startTime = microtime(true);
            Cache::put('autobid_last_ingest_timestamp', now()->timestamp, now()->addDays(7));

            // 1. Get tracked skill job IDs from active users
            $activeSkillJobIds = $redisQuota->getActiveSkillJobIds();

            // 2. Fetch active projects in 1 single global API hit
            $rawProjects = $autoBidService->fetchGlobalProjects($activeSkillJobIds, $limit);
            $fetchCount = count($rawProjects);

            if ($fetchCount === 0) {
                $this->line("📭 No active projects returned from API.");
            } else {
                // 3. Fast O(1) Redis deduplication
                $newProjects = $redisQuota->filterAndMarkUnseenProjects($rawProjects);
                $newCount = count($newProjects);

                $this->line("📡 Fetched {$fetchCount} projects, discovered {$newCount} NEW unseen project(s).");

                if ($newCount > 0) {
                    // 4. Reverse match new projects against enabled users
                    $stats = $reverseMatching->matchAndDispatch($newProjects, $isDryRun);

                    $this->info("⚡ Reverse Matching: {$stats['total_matches']} user matches found across {$newCount} projects.");

                    if ($isDryRun && !empty($stats['preview_matches'])) {
                        foreach ($stats['preview_matches'] as $match) {
                            $this->line("   👉 [User #{$match['user_id']}] matched Project #{$match['project_id']} \"{$match['project_title']}\" (Bid: \${$match['bid_amount']})");
                        }
                    } else {
                        $this->info("📦 Dispatched {$stats['bids_dispatched']} background bid job(s) to Redis queue.");
                    }
                }
            }

            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
            $this->line("⏱ Cycle completed in {$elapsedMs}ms.");

            if ($isDaemon) {
                $this->line("💤 Sleeping 25 seconds before next cycle...\n");
                sleep(25);
            }
        } while ($isDaemon);

        return self::SUCCESS;
    }
}
