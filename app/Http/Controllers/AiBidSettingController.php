<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiBidSetting;
use App\Models\FreelancerConnection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AiBidSettingController extends Controller
{
    public function edit()
    {
        $userId = Auth::id();

        // 1. Fetch or create default settings
        $settings = AiBidSetting::firstOrCreate(
            ['user_id' => $userId],
            [
                'is_autobid_enabled'       => false,
                'max_daily_bids'           => 10,
                'min_budget'               => 30,
                'max_budget'               => 2500,
                'project_type'             => 'both',
                'bid_calculation_mode'     => 'average',
                'delivery_days'            => 7,
                'bidding_delay_seconds'    => 12,
                'require_payment_verified' => true,
                'min_client_rating'        => 4.0,
                'min_client_reviews'       => 0,
                'excluded_keywords'        => [
                    'telegram',
                    'whatsapp',
                    'academic writing',
                    'deposit fee',
                    'contact me outside'
                ],
            ]
        );

        return view('settings.ai-bid-setting', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'max_daily_bids'         => 'required|integer|min:1|max:200',
            'min_budget'             => 'required|integer|min:1',
            'max_budget'             => 'required|integer|gte:min_budget',
            'project_type'           => 'required|in:both,fixed,hourly',
            'bid_calculation_mode'   => 'required|in:minimum,average,maximum',
            'delivery_days'          => 'required|integer|min:1|max:90',
            'bidding_delay_seconds'  => 'required|integer|min:3|max:120',
            'min_client_rating'      => 'required|numeric|min:0|max:5',
            'min_client_reviews'     => 'required|integer|min:0',
            'excluded_keywords'      => 'nullable|string',
        ]);

        $settings = AiBidSetting::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'is_autobid_enabled'       => false,
                'max_daily_bids'           => 10,
                'min_budget'               => 30,
                'max_budget'               => 2500,
                'project_type'             => 'both',
                'bid_calculation_mode'     => 'average',
                'delivery_days'            => 7,
                'bidding_delay_seconds'    => 12,
                'require_payment_verified' => true,
                'min_client_rating'        => 4.0,
                'min_client_reviews'       => 0,
                'excluded_keywords'        => [],
            ]
        );

        $keywordsArray = [];
        if ($request->filled('excluded_keywords')) {
            $decoded = json_decode($request->excluded_keywords, true);
            if (is_array($decoded)) {
                $keywordsArray = array_values(array_unique(array_filter(array_map('trim', $decoded))));
            }
        }

        $isEnabled = $request->has('is_autobid_enabled');
        $user = Auth::user();

        // Enforce Subscription & Balance Restrictions when enabling auto-bid
        if ($isEnabled) {
            if (!$user->hasActiveSubscription()) {
                return back()->with('error', '⚠️ Auto-Bidding requires an active subscription. Please subscribe to a plan.');
            }

            if (!$user->canUseAutobid()) {
                return back()->with('error', '⚠️ Auto-Bidding engine access is not included in your current subscription. Please upgrade your plan.');
            }

            if ($user->ai_bids_balance <= 0) {
                return back()->with('error', '⚠️ Your AI Bids balance is 0. Please top up your AI Bids to enable Auto-Bidding.');
            }
        }

        $settings->update([
            'is_autobid_enabled'       => $isEnabled,
            'status'                   => $isEnabled ? 'idle' : 'paused',
            'max_daily_bids'           => $request->max_daily_bids,
            'min_budget'               => $request->min_budget,
            'max_budget'               => $request->max_budget,
            'project_type'             => $request->project_type,
            'bid_calculation_mode'     => $request->bid_calculation_mode,
            'delivery_days'            => $request->delivery_days,
            'bidding_delay_seconds'    => $request->bidding_delay_seconds,
            'require_payment_verified' => $request->has('require_payment_verified'),
            'min_client_rating'        => $request->min_client_rating,
            'min_client_reviews'       => $request->min_client_reviews,
            'excluded_keywords'        => $keywordsArray,
        ]);

        return back()->with('success', 'AI Auto-Bidding settings saved successfully!');
    }

    /**
     * 1-Click Toggle Auto-Bidding Engine Status (from Top Banner or AJAX)
     */
    public function toggleStatus(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $settings = AiBidSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'is_autobid_enabled'       => false,
                'max_daily_bids'           => 10,
                'min_budget'               => 30,
                'max_budget'               => 2500,
                'project_type'             => 'both',
                'bid_calculation_mode'     => 'average',
                'delivery_days'            => 7,
                'bidding_delay_seconds'    => 12,
                'require_payment_verified' => true,
                'min_client_rating'        => 4.0,
                'min_client_reviews'       => 0,
                'excluded_keywords'        => [],
            ]
        );

        $currentState = (bool) $settings->is_autobid_enabled;
        $targetState = $request->has('state') ? $request->boolean('state') : !$currentState;

        if ($targetState) {
            if (!$user->hasActiveSubscription()) {
                return response()->json([
                    'success' => false,
                    'message' => '⚠️ Auto-Bidding requires an active subscription. Please subscribe to a plan.'
                ], 403);
            }

            if (!$user->canUseAutobid()) {
                return response()->json([
                    'success' => false,
                    'message' => '⚠️ Auto-Bidding engine access is not included in your current subscription. Please upgrade your plan.'
                ], 403);
            }

            if ($user->ai_bids_balance <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => '⚠️ Your AI Bids balance is 0. Please top up your AI Bids to enable Auto-Bidding.'
                ], 403);
            }
        }

        $settings->update([
            'is_autobid_enabled' => $targetState,
            'status'             => $targetState ? 'idle' : 'paused',
        ]);

        return response()->json([
            'success'    => true,
            'is_enabled' => $targetState,
            'message'    => $targetState ? 'Auto-Bidding has been enabled successfully!' : 'Auto-Bidding has been disabled / paused.',
        ]);
    }
}
