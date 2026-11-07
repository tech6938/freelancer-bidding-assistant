@extends('layouts.settings')

@section('settings_content')
<style>
    .settings-sidebar .nav-link {
        color: #475569;
        font-size: 13.5px;
        padding: 8px 14px;
        border-radius: 6px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .settings-sidebar .nav-link:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .settings-sidebar .nav-link.active {
        background-color: #eff6ff;
        color: #2563eb;
        font-weight: 600;
    }
    .form-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    .form-section-title {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 8px;
        margin-bottom: 16px;
    }
    .form-hint {
        font-size: 12px;
        color: #64748b;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Settings Sidebar -->
       

        <!-- Main Form Area -->
        <div class="col-lg-9 col-md-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Bid Settings</h5>
                        <p class="text-muted small mb-0">Configure default delivery timelines, milestones, and anti-ban bot limits.</p>
                    </div>
                </div>

                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show py-2 small mb-4">
                            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show py-2 small mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('settings.bidSettings.update') }}" method="POST">
                        @csrf

                        <!-- Section 1: Turnaround & Escrow Milestones -->
                        <div class="form-card p-3 mb-4">
                            <div class="form-section-title">
                                <i class="bi bi-calendar-check me-1 text-primary"></i> Turnaround & Milestones
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Default Delivery Period</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="default_delivery_days" class="form-control" value="{{ $settings->default_delivery_days }}" min="1" max="90" required>
                                        <span class="input-group-text">Days</span>
                                    </div>
                                    <span class="form-hint">The default number of days sent in the bid payload to Freelancer.</span>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Milestone Request Percentage</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="milestone_percentage" class="form-control" value="{{ $settings->milestone_percentage }}" min="10" max="100" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <span class="form-hint">Set to 100% so clients deposit full escrow funds upon project award.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Anti-Ban Delay & Quotas -->
                        <div class="form-card p-3 mb-4">
                            <div class="form-section-title">
                                <i class="bi bi-shield-lock me-1 text-primary"></i> Anti-Ban Protection & Daily Limits
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Bid Delay / Speed</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="bid_delay_seconds" class="form-control" value="{{ $settings->bid_delay_seconds }}" min="0" max="300" required>
                                        <span class="input-group-text">Seconds</span>
                                    </div>
                                    <span class="form-hint">Adds a natural human reading delay to avoid bot flags from Freelancer.</span>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Daily Bid Limit</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="max_daily_bids" class="form-control" value="{{ $settings->max_daily_bids }}" min="1" max="1000" required>
                                        <span class="input-group-text">Bids / Day</span>
                                    </div>
                                    <span class="form-hint">Caps your daily bids to avoid burning through monthly connects.</span>
                                </div>

                                <div class="col-12 mt-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auto_retry_failed_bids" id="auto_retry" value="1" {{ $settings->auto_retry_failed_bids ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-dark" for="auto_retry">
                                            Auto-retry failed bids once if API encounters a network timeout
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                                <i class="bi bi-save me-1"></i> Save Bid Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection