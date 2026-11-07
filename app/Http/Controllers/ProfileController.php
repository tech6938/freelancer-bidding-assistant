<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\BidSetting;
use App\Models\AiBidSetting;
use App\Models\FreelancerConnection;
use App\Models\PlacedBid;

class ProfileController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $connection = FreelancerConnection::where('user_id', $userId)->first();
        
        // Fetch Manual & AI Settings
        $manualBidSettings = BidSetting::firstOrCreate(['user_id' => $userId]);
        $aiBidSettings     = AiBidSetting::firstOrCreate(['user_id' => $userId]);

        // 1. Data loaded directly from Database
        $totalBidsPlaced = PlacedBid::where('user_id', $userId)->count();

        // Separate Today's Auto and Manual counts
        $todayAutoBids = PlacedBid::where('user_id', $userId)
            ->where('bid_type', 'auto')
            ->whereDate('created_at', today())
            ->count();

        $todayManualBids = PlacedBid::where('user_id', $userId)
            ->where('bid_type', 'manual')
            ->whereDate('created_at', today())
            ->count();

        // 2. Data loaded live from Freelancer API
        $apiAvatar = null;
        $apiRating = '0.0';
        $apiCountry = 'Worldwide';
        $remainingBids = 0;
        $totalBids     = 8;

        if ($connection && !empty($connection->access_token)) {
            try {
                $baseUrl = rtrim(config('services.freelancer.api_url', 'https://www.freelancer.com/api'), '/');
                
                $response = Http::withoutVerifying()
                    ->timeout(4)
                    ->withHeaders([
                        'freelancer-oauth-v1' => $connection->access_token,
                        'Content-Type'        => 'application/json',
                    ])
                    ->get($baseUrl . '/users/0.1/self/', [
                        'avatar'          => 'true',
                        'reputation'      => 'true',
                        'country_details' => 'true',
            'status'             => 'true',
                    ]);

                if ($response->successful()) {
                    $apiData = $response->json()['result'] ?? [];
               
                    // API Avatar
                    if (!empty($apiData['avatar_large_cdn'])) {
                        $apiAvatar = 'https:' . ltrim($apiData['avatar_large_cdn'], ':');
                    } elseif (!empty($apiData['avatar_url'])) {
                        $apiAvatar = $apiData['avatar_url'];
                    }

                    // API Rating
                    $rawRating = $apiData['reputation']['entire_history']['overall'] 
                              ?? $apiData['reputation']['rating'] 
                              ?? 0;
                    $apiRating = number_format((float)$rawRating, 1);

                    // API Country
                    $apiCountry = $apiData['location']['country']['name'] ?? 'Worldwide';
                    $status = $apiData['status'] ?? [];
                    $remainingBids = $status['bid_limit'] ?? ($status['bids_remaining'] ?? 0);
                    $totalBids     = $status['max_bid_limit'] ?? ($status['bids_max'] ?? 8);
                }
            } catch (\Exception $e) {
                // Graceful fallback if API times out
            }
        }

        // Avatar fallback if not set
        if (!$apiAvatar) {
            $apiAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($connection->username ?? 'User') . '&background=0284c7&color=fff&size=128';
        }

        return view('settings.profile', compact(
            'connection',
            'manualBidSettings',
            'aiBidSettings',
            'totalBidsPlaced',
            'todayAutoBids',
            'todayManualBids',
            'apiAvatar',
            'apiRating',
            'apiCountry',
            'remainingBids',
            'totalBids'
        ));
    }
}