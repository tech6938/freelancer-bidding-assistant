<?php

namespace App\Services;

use App\Jobs\ProcessUserAutoBidJob;
use App\Models\AiBidSetting;
use App\Models\FreelancerConnection;
use Illuminate\Support\Facades\DB;
use App\Models\Skill;

/**
 * High-performance reverse matching engine.
 *
 * Given a batch of incoming projects, finds all matching users via indexed DB lookups,
 * evaluates fast Redis quotas & filters, and dispatches parallel worker jobs.
 */
class ReverseMatchingService
{
    public function __construct(
        protected AutoBidService $autoBidService,
        protected RedisQuotaService $redisQuota
    ) {}

    /**
     * Match a batch of incoming projects against all enabled users.
     *
     * @param array $projects Array of raw project data from Freelancer API
     * @param bool  $isDryRun If true, previews without dispatching jobs
     * @return array Summary statistics of matching
     */
    public function matchAndDispatch(array $projects, bool $isDryRun = false): array
    {
        $stats = [
            'projects_processed' => count($projects),
            'total_matches'      => 0,
            'bids_dispatched'    => 0,
            'preview_matches'    => [],
        ];

        if (empty($projects)) {
            return $stats;
        }

        $userDelays = [];
        foreach ($projects as $project) {
            $projectId = (int) ($project['id'] ?? 0);
            $projectTitle = $project['title'] ?? 'Untitled';
            $projectJobIds = array_column($project['jobs'] ?? [], 'id');

            if (empty($projectJobIds) || $projectId <= 0) {
                continue;
            }

            // 1. Fast indexed reverse query: Find all enabled users who have at least one matching skill
            $candidateUserIds = Skill::query()
                ->where('is_active', true)
                ->whereIn('freelancer_job_id', $projectJobIds)
                ->whereHas('aiBidSetting', function ($query) {
                    $query->where('is_autobid_enabled', true);
                })
                ->distinct()
                ->pluck('user_id')
                ->toArray();

            if (empty($candidateUserIds)) {
                continue;
            }

            // 2. Fetch AiBidSetting models for candidate users in bulk
            $settingsList = AiBidSetting::whereIn('user_id', $candidateUserIds)
                ->where('is_autobid_enabled', true)
                ->get()
                ->keyBy('user_id');

            // 3. Evaluate each candidate user
            foreach ($settingsList as $userId => $setting) {
                // Check Redis duplicate bid
                if ($this->redisQuota->hasUserBid($userId, $projectId)) {
                    continue;
                }

                // Check Redis daily quota
                $remainingQuota = $this->redisQuota->getUserRemainingQuota($userId, $setting->max_daily_bids);
                if ($remainingQuota <= 0) {
                    continue;
                }

                // Evaluate filters (budget range, project type, country, language, currency, rating, payment)
                $filterResult = $this->autoBidService->passesFilters($setting, $project);
                if (!$filterResult['passes']) {
                    continue;
                }

                // Calculate bid amount
                $bidStats = $project['bid_stats'] ?? [];
                $budget = $project['budget'] ?? [];
                $bidAmount = $this->autoBidService->calculateBidAmount($setting, $bidStats, $budget, $project);

                $stats['total_matches']++;

                if ($isDryRun) {
                    $stats['preview_matches'][] = [
                        'user_id'       => $userId,
                        'project_id'    => $projectId,
                        'project_title' => $projectTitle,
                        'bid_amount'    => $bidAmount,
                        'delivery_days' => $setting->delivery_days,
                    ];
                } else {
                    $userDelayOffset = $userDelays[$userId] ?? 0;
                    $biddingDelay = (int) ($setting->bidding_delay_seconds ?? 0);

                    // Dispatch asynchronous background job with staggered delay per user
                    ProcessUserAutoBidJob::dispatch(
                        $userId,
                        $project,
                        $bidAmount,
                        $setting->delivery_days,
                        $biddingDelay
                    )
                    ->delay(now()->addSeconds($userDelayOffset));

                    $userDelays[$userId] = $userDelayOffset + max(5, $biddingDelay);
                    $stats['bids_dispatched']++;
                }
            }
        }

        return $stats;
    }
}
