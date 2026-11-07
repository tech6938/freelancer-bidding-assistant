<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BidSetting;

class BidSettingController extends Controller
{
    public function edit()
    {
        $settings = BidSetting::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'default_delivery_days'  => 7,
                'milestone_percentage'   => 100,
                'bid_delay_seconds'      => 4,
                'max_daily_bids'         => 20,
                'auto_retry_failed_bid' => false,
            ]
        );

        return view('settings.bid-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_delivery_days'  => 'required|integer|min:1|max:90',
            'milestone_percentage'   => 'required|integer|min:10|max:100',
            'bid_delay_seconds'      => 'required|integer|min:0|max:300',
            'max_daily_bids'         => 'required|integer|min:1|max:1000',
            'auto_retry_failed_bid' => 'nullable|boolean',
        ]);

        $validated['auto_retry_failed_bid'] = $request->has('auto_retry_failed_bid');

        BidSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            $validated
        );

        return redirect()->back()->with('success', 'Bid settings updated successfully!');
    }
}
