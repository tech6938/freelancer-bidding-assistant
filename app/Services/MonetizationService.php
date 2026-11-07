<?php

namespace App\Services;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\BidPackage;
use App\Models\BankCard;
use App\Models\UserSubscription;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MonetizationService
{
    /**
     * Submit a Bank Card Order with Payment Screenshot for a Subscription Plan.
     */
    public function submitSubscriptionOrder(
        User $user,
        SubscriptionPlan $plan,
        BankCard $bankCard,
        string $screenshotPath,
        ?string $senderName = null,
        ?string $senderNote = null
    ): Order {
        return DB::transaction(function () use ($user, $plan, $bankCard, $screenshotPath, $senderName, $senderNote) {
            $bidsToCredit = (int) $plan->included_ai_bids;
            $sender = $senderName ?: $user->name;

            // Create pending order for admin verification
            return Order::create([
                'user_id'            => $user->id,
                'order_type'         => 'subscription',
                'item_id'            => $plan->id,
                'item_name'          => $plan->name . ' (' . $plan->duration_days . ' Days)',
                'amount'             => $plan->price,
                'bid_quantity_added' => $bidsToCredit,
                'payment_method'     => 'bank_card',
                'bank_card_id'       => $bankCard->id,
                'screenshot_path'    => $screenshotPath,
                'sender_name'        => $sender,
                'sender_note'        => $senderNote,
                'card_holder_name'   => $sender,
                'card_brand'         => $bankCard->bank_name,
                'card_last_four'     => null,
                'status'             => 'pending',
                'transaction_ref'    => 'TXN-SUB-' . strtoupper(Str::random(10)),
                'notes'              => "Bank: {$bankCard->bank_name}",
            ]);
        });
    }

    /**
     * Submit a Bank Card Order with Payment Screenshot for an AI Bid Package.
     * User MUST have an active subscription.
     */
    public function submitBidPackageOrder(
        User $user,
        BidPackage $package,
        BankCard $bankCard,
        string $screenshotPath,
        ?string $senderName = null,
        ?string $senderNote = null
    ): Order {
        if (!$user->hasActiveSubscription()) {
            throw new \DomainException('You must have an active subscription plan to purchase AI Bid packages.');
        }

        return DB::transaction(function () use ($user, $package, $bankCard, $screenshotPath, $senderName, $senderNote) {
            $bidsToCredit = (int) $package->bid_quantity;
            $sender = $senderName ?: $user->name;

            return Order::create([
                'user_id'            => $user->id,
                'order_type'         => 'bid_package',
                'item_id'            => $package->id,
                'item_name'          => $package->name . ' (+' . $bidsToCredit . ' AI Bids)',
                'amount'             => $package->price,
                'bid_quantity_added' => $bidsToCredit,
                'payment_method'     => 'bank_card',
                'bank_card_id'       => $bankCard->id,
                'screenshot_path'    => $screenshotPath,
                'sender_name'        => $sender,
                'sender_note'        => $senderNote,
                'card_holder_name'   => $sender,
                'card_brand'         => $bankCard->bank_name,
                'card_last_four'     => null,
                'status'             => 'pending',
                'transaction_ref'    => 'TXN-BID-' . strtoupper(Str::random(10)),
                'notes'              => "Bank: {$bankCard->bank_name}",
            ]);
        });
    }

    /**
     * Approve an Order (activates subscription or credits bid package balance).
     */
    public function approveOrder(Order $order): array
    {
        if ($order->status === 'completed') {
            return ['success' => true, 'message' => 'Order is already approved.'];
        }

        return DB::transaction(function () use ($order) {
            $user = $order->user;

            if ($order->order_type === 'subscription') {
                $plan = SubscriptionPlan::withTrashed()->find($order->item_id);
                $durationDays = $plan ? $plan->duration_days : 30;
                $features = $plan ? $plan->features : [];
                $planName = $plan ? $plan->name : $order->item_name;

                $now = Carbon::now();
                $currentActive = $user->activeSubscription;

                if ($currentActive && $currentActive->expires_at && $currentActive->expires_at->isFuture()) {
                    $expiresAt = $currentActive->expires_at->copy()->addDays($durationDays);
                    $currentActive->update([
                        'subscription_plan_id' => $plan ? $plan->id : $currentActive->subscription_plan_id,
                        'plan_name'            => $planName,
                        'started_at'           => $now,
                        'expires_at'           => $expiresAt,
                        'features_snapshot'    => $features,
                        'price_paid'           => $currentActive->price_paid + $order->amount,
                    ]);
                } else {
                    $expiresAt = $now->copy()->addDays($durationDays);
                    $user->userSubscriptions()->where('status', 'active')->update(['status' => 'expired']);

                    UserSubscription::create([
                        'user_id'              => $user->id,
                        'subscription_plan_id' => $plan ? $plan->id : null,
                        'plan_name'            => $planName,
                        'started_at'           => $now,
                        'expires_at'           => $expiresAt,
                        'status'               => 'active',
                        'price_paid'           => $order->amount,
                        'features_snapshot'    => $features,
                    ]);
                }

                // Credit included AI bids
                if ($order->bid_quantity_added > 0) {
                    $user->addAiBids($order->bid_quantity_added);
                }
            } elseif ($order->order_type === 'bid_package') {
                if ($order->bid_quantity_added > 0) {
                    $user->addAiBids($order->bid_quantity_added);
                }
            }

            $order->update([
                'status' => 'completed',
            ]);

            return [
                'success' => true,
                'message' => "Order #{$order->transaction_ref} approved successfully. User benefits activated.",
            ];
        });
    }

    /**
     * Reject an Order.
     */
    public function rejectOrder(Order $order, ?string $reason = null): void
    {
        $notes = $order->notes;
        if ($reason) {
            $notes .= " | Rejected Reason: " . $reason;
        }

        $order->update([
            'status' => 'rejected',
            'notes'  => $notes,
        ]);
    }
}
