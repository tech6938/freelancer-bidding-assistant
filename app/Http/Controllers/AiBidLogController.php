<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlacedBid;
use App\Models\AiBidSetting;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AiBidLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // 1. Determine Selected Date (Default: Today)
        $selectedDate = $request->filled('date') ? $request->date : today()->toDateString();
        $formattedDateTitle = Carbon::parse($selectedDate)->isToday() 
            ? "Today (" . Carbon::parse($selectedDate)->format('M d, Y') . ")" 
            : Carbon::parse($selectedDate)->format('M d, Y');

        // 2. Stats for the Selected Date
        $selectedTotalBids = PlacedBid::where('user_id', $userId)
            ->whereDate('created_at', $selectedDate)
            ->count();

        $autoBidsCount = PlacedBid::where('user_id', $userId)
            ->where('bid_type', 'auto')
            ->whereDate('created_at', $selectedDate)
            ->count();

        $manualBidsCount = PlacedBid::where('user_id', $userId)
            ->where('bid_type', 'manual')
            ->whereDate('created_at', $selectedDate)
            ->count();

        $avgBidAmount = PlacedBid::where('user_id', $userId)
            ->whereDate('created_at', $selectedDate)
            ->avg('bid_amount') ?? 0;

        $avgMatchScore = PlacedBid::where('user_id', $userId)
            ->where('bid_type', 'auto')
            ->whereDate('created_at', $selectedDate)
            ->avg('skill_match_score') ?? 0;

        $settings = AiBidSetting::where('user_id', $userId)->first();

        // 3. Query Bids for Selected Date
        $query = PlacedBid::where('user_id', $userId)
            ->whereDate('created_at', $selectedDate)
            ->latest();

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('bid_type', $request->type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('freelancer_project_id', 'like', "%{$search}%")
                  ->orWhere('proposal_text', 'like', "%{$search}%");
            });
        }

        $bids = $query->with('template')->paginate(15)->withQueryString();

        return view('settings.ai-bids-log', compact(
            'bids',
            'selectedDate',
            'formattedDateTitle',
            'selectedTotalBids',
            'autoBidsCount',
            'manualBidsCount',
            'avgBidAmount',
            'avgMatchScore',
            'settings'
        ));
    }
}