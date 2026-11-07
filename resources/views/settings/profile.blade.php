@extends('layouts.settings')

@section('settings_content')
<style>
    .profile-avatar-wrapper {
        position: relative;
        width: 88px;
        height: 88px;
    }
    .profile-avatar-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #ffffff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .online-indicator {
        position: absolute;
        bottom: 4px;
        right: 4px;
        width: 16px;
        height: 16px;
        background-color: #10b981;
        border: 2px solid #ffffff;
        border-radius: 50%;
    }
    .stat-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 16px;
        min-width: 130px;
        transition: all 0.2s ease;
    }
    .stat-box:hover {
        background: #ffffff;
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
</style>

<div class="container-fluid px-0 py-2">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$connection)
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <div class="py-4">
                <i class="bi bi-link-45deg text-primary" style="font-size: 55px;"></i>
                <h4 class="fw-bold mt-2">Connect Freelancer Account</h4>
                <p class="text-muted small mx-auto" style="max-width: 450px;">
                    Link your Freelancer.com account to manage automated proposals, real-time bid balances, and custom safety filters.
                </p>
                <a href="{{ route('freelancer.connect') }}" class="btn btn-primary px-4 py-2 mt-2 fw-medium shadow-sm">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Connect Account
                </a>
            </div>
        </div>
    @else
        <!-- Header Profile Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-4">
                    
                    <!-- Avatar, Username, Country, Rating -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="profile-avatar-wrapper">
                            <img src="{{ $apiAvatar }}" alt="Profile Avatar">
                            <span class="online-indicator" title="Connected"></span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h4 class="fw-bold mb-0 text-dark">
                                    {{ $connection->display_name ?: $connection->username }}
                                </h4>
                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-2 py-1 rounded-pill" style="font-size: 11px;">
                                    <i class="bi bi-patch-check-fill me-1"></i>Connected
                                </span>
                            </div>
                            <p class="text-muted small mb-2">
                                <span>&#64;{{ $connection->username }}</span> • 
                                <span class="text-secondary"><i class="bi bi-geo-alt"></i> {{ $apiCountry }}</span>
                            </p>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning bg-opacity-15 text-dark fw-medium px-2 py-1" style="font-size: 11.5px;">
                                    ★ {{ $apiRating }} Rating
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Clean Categorized Stats (No Confusion) -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        
                        <!-- 1. Freelancer Real Connects Balance -->
                        <div class="stat-box text-center">
    <span class="d-block text-muted small fw-medium">Remaining Bids</span>
    <h5 class="fw-bold text-primary mb-0 mt-1">
        {{ $remainingBids }}
        <span class="fs-6 text-muted fw-normal">/ {{ $totalBids }}</span>
    </h5>
</div>

                        <!-- 2. AI Auto Bids Today -->
                        <div class="stat-box text-center">
                            <span class="d-block text-muted small fw-medium">
                                <i class="bi bi-robot text-primary me-1"></i>Auto Today
                            </span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">
                                {{ $todayAutoBids }} 
                                <span class="fs-6 text-muted fw-normal">/ {{ $aiBidSettings->max_daily_bids ?? 20 }}</span>
                            </h5>
                        </div>

                        <!-- 3. Manual Bids Today -->
                        <div class="stat-box text-center">
                            <span class="d-block text-muted small fw-medium">
                                <i class="bi bi-hand-index-thumb text-secondary me-1"></i>Manual Today
                            </span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">
                                {{ $todayManualBids }} 
                                <span class="fs-6 text-muted fw-normal">/ {{ $manualBidSettings->max_daily_bids ?? 10 }}</span>
                            </h5>
                        </div>

                        <!-- 4. Lifetime Total Placed -->
                        <div class="stat-box text-center">
                            <span class="d-block text-muted small fw-medium">Total Placed</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">{{ $totalBidsPlaced }}</h5>
                        </div>

                    </div>

                    <!-- Disconnect Action Button -->
                    <!-- <div class="d-flex flex-xl-column">
                        <form action="{{ route('freelancer.disconnect') }}" method="POST" class="m-0" onsubmit="return confirm('Disconnect Freelancer account? Auto-bidding will stop.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-3 px-3 py-2 fw-medium w-100 d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-power"></i> Disconnect
                            </button>
                        </form>
                    </div> -->

                </div>
            </div>
        </div>
    @endif

</div>
@endsection