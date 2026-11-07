<?php

namespace App\Jobs;

use App\Models\AiBidSetting;
use App\Models\FreelancerConnection;
use App\Models\PlacedBid;
use App\Services\AutoBidService;
use App\Services\RedisQuotaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Parallel background job to generate Groq AI proposal and place auto-bid
 * with Redis rate-limiting per user and resilient fallback.
 */
class ProcessUserAutoBidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $maxExceptions = 3;
    public int $backoff = 10;
    public bool $deleteWhenMissingModels = true;

    /**
     * @param int   $userId
     * @param array $projectData Raw project data or normalized payload
     * @param float $bidAmount
     * @param int   $deliveryDays
     * @param int   $biddingDelaySeconds
     */
    public function __construct(
        public int   $userId,
        public array $projectData,
        public float $bidAmount,
        public int   $deliveryDays,
        public int   $biddingDelaySeconds = 0,
    ) {}

    public function handle(AutoBidService $autoBidService, RedisQuotaService $redisQuota): void
    {
        $projectId = (int) ($this->projectData['id'] ?? 0);
        $projectTitle = $this->projectData['title'] ?? 'Untitled Project';

        // 1. Check if user still has auto-bid enabled
        $settings = AiBidSetting::where('user_id', $this->userId)
            ->where('is_autobid_enabled', true)
            ->first();

        if (!$settings) {
            return;
        }

        // 1.1 Subscription & AI Bids Balance Verification
        $user = \App\Models\User::find($this->userId);
        if (!$user || !$user->canUseAutobid() || $user->ai_bids_balance <= 0) {
            $settings->update([
                'is_autobid_enabled' => false,
                'status'             => 'paused',
            ]);
            return;
        }

        // 2. Fast Redis Quota and duplicate verification
        $remaining = $redisQuota->getUserRemainingQuota($this->userId, $settings->max_daily_bids);
        if ($remaining <= 0) {
            $settings->update(['status' => 'quota_reached']);
            return;
        }

        if ($redisQuota->hasUserBid($this->userId, $projectId)) {
            return;
        }

        // 3. Check connection
        $connection = FreelancerConnection::where('user_id', $this->userId)->first();
        if (!$connection || empty($connection->access_token)) {
            return;
        }

        // 4. Rate-limiting per user (respecting user's bidding_delay_seconds)
        $delay = max(1, $this->biddingDelaySeconds);
        $rateLimitKey = "autobid_rate_user_{$this->userId}";

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($rateLimitKey) ?: $delay;
            $this->release($seconds);
            return;
        }

        \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, $delay);
        $this->executeBidPlacement($autoBidService, $redisQuota, $connection, $settings, $projectId, $projectTitle);
    }

    /**
     * Generate proposal and dispatch bid to Freelancer API.
     */
    protected function executeBidPlacement(
        AutoBidService $autoBidService,
        RedisQuotaService $redisQuota,
        FreelancerConnection $connection,
        AiBidSetting $settings,
        int $projectId,
        string $projectTitle
    ): void {
        // 1. Prepare project info for AI proposal
        $projectSkillNames = $autoBidService->extractProjectSkills($this->projectData);
        $clientName = $autoBidService->extractClientName($this->projectData);

        $proposalPayload = [
            'title'       => $projectTitle,
            'description' => $this->projectData['preview_description'] ?? $this->projectData['description'] ?? '',
            'client_name' => $clientName,
            'skills'      => $projectSkillNames,
        ];

        // 2. Generate AI proposal with automatic fallback
        $proposalText = $autoBidService->generateAiProposal($proposalPayload);
        $skillsSnippet = !empty($projectSkillNames) ? implode(', ', array_slice($projectSkillNames, 0, 3)) : 'required skills';
        $reason = "AI Generated Proposal tailored for {$skillsSnippet}";
        $matchScore = 1.0;

        // 3. Build API payload
        $apiPayload = [
            'project_id'           => $projectId,
            'bidder_id'            => (int) $connection->freelancer_user_id,
            'amount'               => $this->bidAmount,
            'period'               => $this->deliveryDays,
            'description'          => $proposalText,
            'milestone_percentage' => 100,
        ];

        // 4. Dispatch to Freelancer API
        $result = $autoBidService->placeBidViaApi($connection, $apiPayload);

        if ($result['success']) {
            try {
                DB::transaction(function () use ($result, $projectId, $proposalText, $matchScore, $reason) {
                    PlacedBid::create([
                        'user_id'               => $this->userId,
                        'freelancer_project_id' => $projectId,
                        'bid_amount'            => $this->bidAmount,
                        'delivery_days'         => $this->deliveryDays,
                        'proposal_text'         => $proposalText,
                        'freelancer_bid_id'     => $result['data']['id'] ?? null,
                        'bid_type'              => 'auto',
                        'matched_template_id'   => null,
                        'skill_match_score'     => $matchScore,
                        'auto_bid_reason'       => $reason,
                    ]);
                });

                // Update Redis tracking
                $redisQuota->recordUserBid($this->userId, $projectId);

                // Deduct 1 AI Bid from user balance
                $user = \App\Models\User::find($this->userId);
                $user?->deductAiBid(1);

            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Duplicate bid caught by DB unique constraint - safely ignored
            }
        } else {
            if (!empty($result['is_limit_error'])) {
                $settings->update([
                    'is_autobid_enabled' => false,
                    'status'             => 'limit_reached',
                ]);
                return;
            }

            if ($this->attempts() < $this->tries && str_contains($result['error'] ?? '', 'Connection')) {
                $this->release($this->backoff);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Failed job details are automatically captured by Laravel in the failed_jobs DB table
    }
}
