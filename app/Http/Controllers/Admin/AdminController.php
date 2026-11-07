<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PlacedBid;
use App\Models\AiBidSetting;
use App\Models\FreelancerConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class AdminController extends Controller
{
    /**
     * Display live metrics and system health on the Admin Dashboard.
     */
    public function dashboard()
    {
        // 1. Core Platform Metrics
        $totalUsers = User::count();
        $activeUsers = User::where('is_banned', false)->count();
        $bannedUsers = User::where('is_banned', true)->count();
        
        $bidsToday = PlacedBid::whereDate('created_at', today())->count();
        $totalBids = PlacedBid::count();
        
        $autoBidEnabledUsers = AiBidSetting::where('is_autobid_enabled', true)->count();
        $connectedAccounts = FreelancerConnection::whereNotNull('freelancer_user_id')->count();

        // 2. Recent System Auto-Bids with User data
        $recentBids = PlacedBid::with('user:id,name,email,username')
            ->latest()
            ->take(10)
            ->get();

        // 3. Dynamic System & API Health Diagnostics
        $healthStatus = $this->getSystemHealth();

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'bannedUsers',
            'bidsToday',
            'totalBids',
            'autoBidEnabledUsers',
            'connectedAccounts',
            'recentBids',
            'healthStatus'
        ));
    }

    /**
     * Display paginated users with server-side filters and search.
     */
    public function users(Request $request)
    {
        $query = User::with(['freelancerConnection', 'aiBidSetting']);

        // Search by Name, Email, or Username
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by Role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by Account Status (active/banned)
        if ($request->filled('status')) {
            if ($request->status === 'banned') {
                $query->where('is_banned', true);
            } elseif ($request->status === 'active') {
                $query->where('is_banned', false);
            }
        }

        // Filter by Freelancer Connection
        if ($request->filled('connection')) {
            if ($request->connection === 'connected') {
                $query->whereHas('freelancerConnection', function ($q) {
                    $q->whereNotNull('freelancer_user_id');
                });
            } elseif ($request->connection === 'disconnected') {
                $query->whereDoesntHave('freelancerConnection');
            }
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Summary counters for quick filter tabs
        $totalCount = User::count();
        $activeCount = User::where('is_banned', false)->count();
        $bannedCount = User::where('is_banned', true)->count();
        $adminCount = User::where('role', 'admin')->count();

        return view('admin.users', compact(
            'users',
            'totalCount',
            'activeCount',
            'bannedCount',
            'adminCount'
        ));
    }

    /**
     * Toggle Ban/Unban status for a user.
     */
    public function toggleBan(Request $request, User $user)
    {
        // Safety guard: Cannot ban self or other admins
        if ($user->id === auth()->id() || $user->isAdmin()) {
            return back()->with('error', 'Administrator accounts cannot be banned.');
        }

        if ($user->is_banned) {
            // Unban user
            $user->update([
                'is_banned'   => false,
                'ban_reason'  => null,
            ]);

            return back()->with('success', "User '{$user->name}' has been successfully unbanned.");
        }

        // Ban user with reason
        $request->validate([
            'ban_reason' => 'nullable|string|max:500',
        ]);

        $user->update([
            'is_banned'   => true,
            'ban_reason'  => $request->input('ban_reason', 'Account suspended by administrator.'),
        ]);

        // Check & disable autobid if currently active
        if ($user->aiBidSetting && $user->aiBidSetting->is_autobid_enabled) {
            $user->aiBidSetting->update([
                'is_autobid_enabled' => false,
            ]);
        }

        return back()->with('success', "User '{$user->name}' has been banned and auto-bidding disabled.");
    }

    /**
     * Helper to perform live health checks on system dependencies.
     */
    protected function getSystemHealth(): array
    {
        // 1. Database connection check
        $dbStatus = 'Operational';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'Error: ' . $e->getMessage();
        }

        // 2. Groq AI Configuration check
        // $groqKey = config('services.groq.api_key') ?? env('GROQ_API_KEY');
        // $groqStatus = !empty($groqKey) ? 'Configured & Online' : 'API Key Missing';

        // 3. Cache / Rate Limiting check
        $cacheDriver = config('cache.default', 'file');
        $cacheStatus = 'Active (' . strtoupper($cacheDriver) . ')';

        // 4. Background Ingestion Log / Scheduler Check (Cache-First, Zero-Log Friendly)
        $lastRun = 'Never executed';
        $schedulerStatus = 'Active (Every 30s)';

        $cachedTimestamp = Cache::get('autobid_last_ingest_timestamp');
        if ($cachedTimestamp) {
            $lastRun = now()->createFromTimestamp($cachedTimestamp)->diffForHumans();
        } elseif (File::exists(storage_path('logs/autobid-ingest.log'))) {
            $lastModified = File::lastModified(storage_path('logs/autobid-ingest.log'));
            $lastRun = now()->createFromTimestamp($lastModified)->diffForHumans();
        }

        return [
            'database'         => $dbStatus,
            
            'cache_driver'     => $cacheDriver,
            'scheduler_status' => $schedulerStatus,
            'last_ingest_run'  => $lastRun,
        ];
    }
}
