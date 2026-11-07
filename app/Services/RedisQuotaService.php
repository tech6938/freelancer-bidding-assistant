<?php

namespace App\Services;

use App\Models\PlacedBid;
use App\Models\Skill;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * High-performance Cache service for auto-bidding duplicate prevention,
 * atomic daily quota tracking, and active skill ID management.
 * Compatible with any Laravel cache driver (Database, Redis, File, Memcached).
 */
class RedisQuotaService
{
    const SEEN_PROJECT_PREFIX = 'fl_seen_proj_';
    const ACTIVE_JOB_IDS_KEY = 'autobid_active_freelancer_job_ids';
    const SEEN_PROJECT_TTL = 172800; // 48 hours in seconds
    const USER_BID_TTL = 2592000; // 30 days in seconds
    const USER_QUOTA_TTL = 86400; // 24 hours

    /**
     * Filter a list of raw projects from the API down to only unseen projects.
     * Marks newly seen projects into Cache in O(1).
     *
     * @param array $projects Array of raw project dictionaries
     * @return array Array of newly discovered projects
     */
    public function filterAndMarkUnseenProjects(array $projects): array
    {
        if (empty($projects)) {
            return [];
        }

        $unseenProjects = [];

        foreach ($projects as $project) {
            $projectId = (int) ($project['id'] ?? 0);
            if ($projectId <= 0) {
                continue;
            }

            $cacheKey = self::SEEN_PROJECT_PREFIX . $projectId;

            // Check if project has already been seen
            if (!Cache::has($cacheKey)) {
                $unseenProjects[] = $project;
                // Mark as seen in cache for 48 hours
                Cache::put($cacheKey, 1, self::SEEN_PROJECT_TTL);
            }
        }

        return $unseenProjects;
    }

    /**
     * Check if a user has already placed a bid on a given project.
     * Checks Cache first; if miss, queries DB and warms cache.
     *
     * @param int $userId
     * @param int $projectId
     * @return bool
     */
    public function hasUserBid(int $userId, int $projectId): bool
    {
        $cacheKey = "user_{$userId}_bid_proj_{$projectId}";

        if (Cache::has($cacheKey)) {
            return (bool) Cache::get($cacheKey);
        }

        $exists = PlacedBid::where('user_id', $userId)
            ->where('freelancer_project_id', $projectId)
            ->exists();

        if ($exists) {
            Cache::put($cacheKey, 1, self::USER_BID_TTL);
        }

        return $exists;
    }

    /**
     * Record a placed bid for a user in Cache (marks project as bid + increments daily quota).
     *
     * @param int $userId
     * @param int $projectId
     * @return void
     */
    public function recordUserBid(int $userId, int $projectId): void
    {
        $today = Carbon::today()->toDateString();
        $bidKey = "user_{$userId}_bid_proj_{$projectId}";
        $quotaKey = "user_{$userId}_quota_{$today}";

        // Mark project as bidded by this user
        Cache::put($bidKey, 1, self::USER_BID_TTL);

        // Increment daily bid quota counter
        if (Cache::has($quotaKey)) {
            try {
                Cache::increment($quotaKey);
            } catch (\Throwable $e) {
                $count = (int) Cache::get($quotaKey, 0);
                Cache::put($quotaKey, $count + 1, self::USER_QUOTA_TTL);
            }
        } else {
            $dbCount = PlacedBid::where('user_id', $userId)
                ->where('bid_type', 'auto')
                ->whereDate('created_at', Carbon::today())
                ->count();

            Cache::put($quotaKey, $dbCount, self::USER_QUOTA_TTL);
        }
    }

    /**
     * Get remaining daily bids for a user using Cache counter.
     *
     * @param int $userId
     * @param int $maxDaily
     * @return int
     */
    public function getUserRemainingQuota(int $userId, int $maxDaily): int
    {
        if ($maxDaily <= 0) {
            return 0;
        }

        $today = Carbon::today()->toDateString();
        $quotaKey = "user_{$userId}_quota_{$today}";

        $currentCount = Cache::remember($quotaKey, self::USER_QUOTA_TTL, function () use ($userId) {
            return PlacedBid::where('user_id', $userId)
                ->where('bid_type', 'auto')
                ->whereDate('created_at', Carbon::today())
                ->count();
        });

        return max(0, $maxDaily - (int) $currentCount);
    }

    /**
     * Retrieve all unique freelancer job IDs across all active autobid users.
     * Cached for 10 minutes.
     *
     * @return array
     */
    public function getActiveSkillJobIds(): array
    {
        return Cache::remember(self::ACTIVE_JOB_IDS_KEY, 600, function () {
            return $this->syncActiveSkillJobIds();
        });
    }

    /**
     * Rebuild and cache the list of active skill job IDs from the database.
     *
     * @return array
     */
    public function syncActiveSkillJobIds(): array
    {
        $jobIds = DB::table('skills')
            ->join('ai_bid_settings', 'skills.user_id', '=', 'ai_bid_settings.user_id')
            ->where('ai_bid_settings.is_autobid_enabled', true)
            ->where('skills.is_active', true)
            ->whereNotNull('skills.freelancer_job_id')
            ->distinct()
            ->pluck('skills.freelancer_job_id')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values()
            ->toArray();

        Cache::put(self::ACTIVE_JOB_IDS_KEY, $jobIds, 600);

        return $jobIds;
    }
}
