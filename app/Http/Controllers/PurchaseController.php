<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\BidPackage;
use App\Models\BankCard;
use App\Services\MonetizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    /**
     * Display the user Purchase & Plans blade.
     */
    public function index()
    {
        $user = Auth::user();
        $activeSubscription = $user->activeSubscription;
        $hasActiveSubscription = $user->hasActiveSubscription();

        $plans = SubscriptionPlan::active()->get();
        $bidPackages = BidPackage::active()->get();
        $bankCards = BankCard::active()->get();
        $recentOrders = $user->orders()->with('bankCard')->latest()->take(10)->get();

        return view('purchase.index', compact(
            'user',
            'activeSubscription',
            'hasActiveSubscription',
            'plans',
            'bidPackages',
            'bankCards',
            'recentOrders'
        ));
    }

    /**
     * Process Bank Card Payment Submission (with Screenshot) for a Subscription Plan.
     */
    public function purchaseSubscription(
        Request $request,
        MonetizationService $monetizationService
    ) {
        $request->validate([
            'plan_id'        => 'required|exists:subscription_plans,id',
            'bank_card_id'   => 'required|exists:bank_cards,id',
            'payment_proof'  => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'bank_card_id.required'  => 'Please select a bank.',
            'payment_proof.required' => 'Please upload your payment screenshot.',
            'payment_proof.image'    => 'Payment proof must be an image (JPG, PNG, WEBP).',
            'payment_proof.max'      => 'Payment proof image size must not exceed 5MB.',
        ]);

        $user = Auth::user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $bankCard = BankCard::findOrFail($request->bank_card_id);

        try {
            // Store payment screenshot proof
            $screenshotPath = $request->file('payment_proof')->store('payment_proofs', 'public');

            // Record pending order
            $order = $monetizationService->submitSubscriptionOrder(
                $user,
                $plan,
                $bankCard,
                $screenshotPath
            );

            $msg = "🎉 Payment proof submitted successfully! Order Ref: {$order->transaction_ref}. Your '{$plan->name}' subscription is pending verification.";
            return redirect()->route('purchase.index')->with('success', $msg);

        } catch (\Exception $e) {
            return redirect()->route('purchase.index')
                ->withInput()
                ->with('error', '❌ Submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Process Bank Card Payment Submission (with Screenshot) for an AI Bid Package.
     * Strictly verifies active subscription exists.
     */
    public function purchaseBidPackage(
        Request $request,
        MonetizationService $monetizationService
    ) {
        $request->validate([
            'package_id'     => 'required|exists:bid_packages,id',
            'bank_card_id'   => 'required|exists:bank_cards,id',
            'payment_proof'  => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'bank_card_id.required'  => 'Please select a bank.',
            'payment_proof.required' => 'Please upload your payment screenshot.',
            'payment_proof.image'    => 'Payment proof must be an image (JPG, PNG, WEBP).',
            'payment_proof.max'      => 'Payment proof image size must not exceed 5MB.',
        ]);

        $user = Auth::user();

        // 1. Strict Subscription Lock Guard
        if (!$user->hasActiveSubscription()) {
            return redirect()->route('purchase.index')
                ->with('error', '⚠️ AI Bid Packages can strictly only be purchased with an active subscription. Please subscribe to a plan first.');
        }

        $package = BidPackage::findOrFail($request->package_id);
        $bankCard = BankCard::findOrFail($request->bank_card_id);

        try {
            // Store payment screenshot proof
            $screenshotPath = $request->file('payment_proof')->store('payment_proofs', 'public');

            // Record pending order
            $order = $monetizationService->submitBidPackageOrder(
                $user,
                $package,
                $bankCard,
                $screenshotPath
            );

            $msg = "🎉 Payment proof submitted successfully! Order Ref: {$order->transaction_ref}. +{$package->bid_quantity} AI Bids top-up is pending verification.";
            return redirect()->route('purchase.index')->with('success', $msg);

        } catch (\DomainException $e) {
            return redirect()->route('purchase.index')
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('purchase.index')
                ->withInput()
                ->with('error', '❌ Top-up submission failed: ' . $e->getMessage());
        }
    }
}
