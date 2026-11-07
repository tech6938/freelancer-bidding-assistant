<?php

namespace App\Jobs;

use App\Models\AiBidSetting;
use App\Models\FreelancerConnection;
use App\Models\PlacedBid;
use App\Services\AutoBidService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Queued job to place a single auto-bid on a Freelancer project.
 *
 * Dispatched by ProcessAutoBids command with a delay (bidding_delay_seconds).
 * Handles retries, duplicate prevention, and atomic DB writes.
 */
class PlaceAutoBidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    public int $backoff = 10;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        protected int    $userId,
        protected int    $projectId,
        protected string $projectTitle,
        protected float  $bidAmount,
        protected int    $deliveryDays,
        protected string $proposalText,
        protected ?int   $matchedTemplateId,
        protected float  $skillMatchScore,
        protected string $autoBidReason,
    ) {}

    public function handle(AutoBidService $autoBidService): void
    {
        // 1. Verify user still has auto-bid enabled
        $settings = AiBidSetting::where('user_id', $this->userId)
            ->where('is_autobid_enabled', true)
            ->first();

        if (!$settings) {
            return;
        }

        // 2. Check daily quota hasn't been exceeded since dispatch
        $remaining = $autoBidService->getRemainingQuota($this->userId, $settings->max_daily_bids);
        if ($remaining <= 0) {
            $settings->update(['status' => 'quota_reached']);
            return;
        }

        // 3. Prevent duplicate bids (defense-in-depth alongside unique constraint)
        if ($autoBidService->hasAlreadyBid($this->userId, $this->projectId)) {
            return;
        }

        // 4. Get connection
        $connection = FreelancerConnection::where('user_id', $this->userId)->first();
        if (!$connection || empty($connection->access_token)) {
            return;
        }

        // 5. Place bid via API within a DB transaction
        $payload = [
            'project_id'           => $this->projectId,
            'bidder_id'            => (int) $connection->freelancer_user_id,
            'amount'               => $this->bidAmount,
            'period'               => $this->deliveryDays,
            'description'          => $this->proposalText,
            'milestone_percentage' => 100,
        ];

        $result = $autoBidService->placeBidViaApi($connection, $payload); 

        if ($result['success']) {
            try {
                DB::transaction(function () use ($result) {
                    PlacedBid::create([
                        'user_id'               => $this->userId,
                        'freelancer_project_id' => $this->projectId,
                        'bid_amount'            => $this->bidAmount,
                        'delivery_days'         => $this->deliveryDays,
                        'proposal_text'         => $this->proposalText,
                        'freelancer_bid_id'     => $result['data']['id'] ?? null,
                        'bid_type'              => 'auto',
                        'matched_template_id'   => $this->matchedTemplateId,
                        'skill_match_score'     => $this->skillMatchScore,
                        'auto_bid_reason'       => $this->autoBidReason,
                    ]);
                });

                // Keep Redis sets and daily quotas synchronized
                app(RedisQuotaService::class)->recordUserBid($this->userId, $this->projectId);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Duplicate bid — safe to ignore (unique constraint caught it)
            }
        } else {
            // If Freelancer API returns a bid limit / quota / out of bids error, automatically disable Auto-Bidding engine
            if (!empty($result['is_limit_error'])) {
                $settings->update([
                    'is_autobid_enabled' => false,
                    'status'             => 'limit_reached',
                ]);
                return;
            }

            // Only retry on connection/transient errors, not on API rejections
            if ($this->attempts() < $this->tries && str_contains($result['error'] ?? '', 'Connection')) {
                $this->release($this->backoff);
                return;
            }
        }
    }

    /**
     * Handle job failure after all retry attempts.
     */
    public function failed(\Throwable $exception): void
    {
        // Failures are automatically recorded in the failed_jobs DB table
    }
}
