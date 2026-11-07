@extends('layouts.settings')

@section('settings_content')
<style>
    .admin-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.2s ease;
    }
    .admin-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.07);
    }
    .stat-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }
    .table-modern {
        border-collapse: separate;
        border-spacing: 0;
    }
    .table-modern thead th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 16px;
    }
    .table-modern tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        font-size: 13.5px;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tbody tr:last-child td {
        border-bottom: none;
    }
    .table-modern tbody tr:hover {
        background-color: #f8fafc;
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 rgba(34, 197, 94, 0.4);
        animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
</style>

<div class="admin-dashboard">
    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-danger"></i> Admin Control Center
            </h4>
            <p class="text-muted small mb-0">Live overview of platform performance, auto-bidding health, and user metrics.</p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill d-flex align-items-center gap-2">
                <span class="pulse-dot bg-success"></span> Reverse Match Engine: Active
            </span>
            <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-dark d-flex align-items-center gap-1">
                <i class="bi bi-people"></i> Manage Users
            </a>
        </div>
    </div>

    <!-- 4 Key Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Users -->
        <div class="col-xl-3 col-md-6">
            <div class="admin-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Total Users</span>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ number_format($totalUsers) }}</h3>
                        <small class="text-success fw-medium">
                            <i class="bi bi-check-circle"></i> {{ number_format($activeUsers) }} Active 
                            @if($bannedUsers > 0)
                                <span class="text-danger ms-1">({{ $bannedUsers }} Banned)</span>
                            @endif
                        </small>
                    </div>
                    <div class="stat-icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bids Placed Today -->
        <div class="col-xl-3 col-md-6">
            <div class="admin-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Bids Today</span>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ number_format($bidsToday) }}</h3>
                        <small class="text-muted">Lifetime: {{ number_format($totalBids) }} bids</small>
                    </div>
                    <div class="stat-icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-send-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Auto-Bidders -->
        <div class="col-xl-3 col-md-6">
            <div class="admin-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Auto-Bid Active</span>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ number_format($autoBidEnabledUsers) }}</h3>
                        <small class="text-primary fw-medium">
                            <i class="bi bi-robot"></i> AI Mode Enabled
                        </small>
                    </div>
                    <div class="stat-icon-box bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connected Freelancer Accounts -->
        <div class="col-xl-3 col-md-6">
            <div class="admin-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Connected Accounts</span>
                        <h3 class="fw-bold text-dark mb-0 mt-1">{{ number_format($connectedAccounts) }}</h3>
                        <small class="text-info fw-medium">
                            <i class="bi bi-link-45deg"></i> Freelancer OAuth
                        </small>
                    </div>
                    <div class="stat-icon-box bg-info bg-opacity-10 text-info">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Section: Recent Bids & Health Widget -->
    <div class="row g-3 mb-4">
        <!-- Left: Recent System Auto-Bids Table -->
        <div class="col-xl-8">
            <div class="admin-card p-0 h-100">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light rounded-top">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary"></i> Recent System Auto-Bids
                    </h6>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary">Latest 10</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Project</th>
                                <th>Bid Amount</th>
                                <th>AI Match</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBids as $bid)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($bid->user->name ?? 'User') }}&background=0284c7&color=fff" 
                                                 class="rounded-circle" width="28" height="28" alt="Avatar">
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $bid->user->name ?? 'User #' . $bid->user_id }}</div>
                                                <small class="text-muted" style="font-size: 11px;">#{{ $bid->user_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark text-truncate" style="max-width: 220px;" title="Project ID: {{ $bid->freelancer_project_id }}">
                                            #{{ $bid->freelancer_project_id }}
                                        </div>
                                        <small class="text-muted">{{ $bid->delivery_days }} days delivery</small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">${{ number_format($bid->bid_amount, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 11px;">
                                            {{ round(($bid->skill_match_score ?? 1.0) * 100) }}% Match
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $bid->created_at ? $bid->created_at->diffForHumans() : 'Just now' }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                        No auto-bids placed yet. When reverse matching places bids, they will appear here in real-time.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Live System & API Health Status -->
        <div class="col-xl-4">
            <div class="admin-card p-3 h-100">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-heart-pulse-fill text-danger"></i> System & API Health
                </h6>

                <div class="d-flex flex-column gap-3">
                    <!-- Database -->
                    <div class="p-3 rounded bg-light border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold small text-dark"><i class="bi bi-hdd-rack text-primary me-1"></i> MySQL Database</span>
                            <span class="badge {{ str_contains($healthStatus['database'], 'Operational') ? 'bg-success' : 'bg-danger' }}">
                                {{ $healthStatus['database'] }}
                            </span>
                        </div>
                        <small class="text-muted d-block" style="font-size: 11.5px;">Connection: <code>{{ config('database.default', 'mysql') }}</code></small>
                    </div>

                    <!-- Groq AI Engine -->
                    

                    <!-- Redis / Cache Driver -->
                    <!-- <div class="p-3 rounded bg-light border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold small text-dark"><i class="bi bi-database text-info me-1"></i> Rate Limiter / Cache</span>
                            <span class="badge bg-success">{{ $healthStatus['cache_driver'] }}</span>
                        </div>
                        <small class="text-muted d-block" style="font-size: 11.5px;">Driver: {{ config('cache.default', 'file') }}</small>
                    </div> -->

                    <!-- Scheduler / Background Ingest -->
                    <div class="p-3 rounded bg-light border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold small text-dark"><i class="bi bi-clock-history text-secondary me-1"></i> Project Ingestion Cron</span>
                            <span class="badge bg-primary">{{ $healthStatus['scheduler_status'] }}</span>
                        </div>
                        <small class="text-muted d-block" style="font-size: 11.5px;">Last Activity: <strong>{{ $healthStatus['last_ingest_run'] }}</strong></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection