@extends('layouts.app')

@section('content')
<style>
    .pricing-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
        padding: 48px 0 64px;
        position: relative;
    }
    .pricing-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .pricing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: #cbd5e1;
    }
    .pricing-card.featured {
        border: 2px solid #ea580c;
        box-shadow: 0 10px 25px -5px rgba(234, 88, 12, 0.15);
    }
    .pricing-badge {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: #ea580c;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 4px 14px;
        border-radius: 9999px;
    }
    .package-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        transition: all 0.2s ease;
    }
    .package-card:hover {
        border-color: #94a3b8;
        transform: translateY(-2px);
    }
    .locked-overlay {
        background: rgba(248, 250, 252, 0.85);
        backdrop-filter: blur(2px);
        border-radius: 12px;
    }
    .feature-item {
        font-size: 13.5px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        color: #334155;
    }
    .feature-item.disabled {
        color: #94a3b8;
        text-decoration: line-through;
    }
    .screenshot-thumb-sm {
        width: 44px;
        height: 44px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        cursor: pointer;
    }
</style>

<div class="pricing-header">
    <div class="container text-center">
        <h1 class="fw-bold display-6 mb-2">Upgrade Your AI Bidding Power</h1>
        <p class="text-slate-300 fs-6 mx-auto mb-0" style="max-width: 650px; color: #cbd5e1;">
            Choose a subscription plan or top-up direct AI bids. Select bank and upload your payment screenshot.
        </p>
    </div>
</div>

<div class="container my-n5 position-relative" style="margin-top: -36px !important;">
    <!-- Active Subscription Status Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden">
        <div class="card-body p-4 bg-white">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle fs-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold text-dark fs-5">Account Status</span>
                                @if($hasActiveSubscription)
                                    <span class="badge bg-success text-white px-2 py-1">Active Subscription</span>
                                @else
                                    <span class="badge bg-secondary text-white px-2 py-1">No Active Plan</span>
                                @endif
                            </div>
                            @if($hasActiveSubscription && $activeSubscription)
                                <p class="text-muted small mb-0">
                                    Current Plan: <strong class="text-dark">{{ $activeSubscription->plan_name }}</strong>
                                    &bull;
                                    Expires: <strong class="text-dark">{{ $activeSubscription->expires_at ? $activeSubscription->expires_at->format('M d, Y') : 'Never' }}</strong>
                                    ({{ $activeSubscription->daysRemaining() }} days left)
                                </p>
                            @else
                                <p class="text-muted small mb-0">
                                    Subscribe to a plan below using Bank Card payment to activate the auto-bidding engine.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 text-lg-end">
                    <div class="d-inline-flex align-items-center gap-3 bg-light p-3 rounded-3 border">
                        <div class="text-start">
                            <span class="text-muted d-block small" style="font-size: 11px;">AVAILABLE BALANCE</span>
                            <span class="fw-bold text-primary fs-4">
                                <i class="bi bi-lightning-charge-fill text-warning me-1"></i>{{ number_format($user->ai_bids_balance ?? 0) }}
                            </span>
                            <span class="text-muted small"> AI Bids</span>
                        </div>
                        <a href="#bid-packages-section" class="btn btn-sm btn-outline-primary fw-semibold">
                            + Add Bids
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix the following errors:</div>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Section 1: Subscription Plans Module -->
    <div class="mb-5">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-dark mb-1">1. Choose a Subscription Plan</h3>
            <p class="text-muted small">Select a duration tier, pay, and upload your payment screenshot.</p>
        </div>

        <div class="row g-4 justify-content-center">
            @forelse($plans as $plan)
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card p-4 {{ $plan->badge ? 'featured' : '' }}">
                        @if($plan->badge)
                            <div class="pricing-badge">{{ $plan->badge }}</div>
                        @endif

                        <div class="mb-3">
                            <h4 class="fw-bold text-dark mb-1">{{ $plan->name }}</h4>
                            <p class="text-muted small mb-0" style="min-height: 38px;">
                                {{ $plan->description ?? 'Full engine access and feature permissions.' }}
                            </p>
                        </div>

                        <div class="d-flex align-items-baseline gap-1 mb-4">
                            <span class="display-6 fw-bold text-dark">Rs{{ number_format($plan->price, 2) }}</span>
                            <span class="text-muted">/ {{ $plan->duration_days }} days</span>
                        </div>

                        <hr class="my-0 mb-4 opacity-50">

                        <!-- Dynamic Feature Checklist -->
                        <div class="flex-grow-1 mb-4">
                            <div class="text-uppercase fw-bold text-muted mb-3" style="font-size: 11px; letter-spacing: 0.5px;">Included Features</div>
                            
                            <div class="feature-item {{ $plan->has_autobid ? '' : 'disabled' }}">
                                <i class="bi {{ $plan->has_autobid ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                <span>Auto bidding included</span>
                            </div>

                            <div class="feature-item {{ $plan->free_ai_bids > 0 ? '' : 'disabled' }}">
                                <i class="bi {{ $plan->free_ai_bids > 0 ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                <span>Free <strong>{{ number_format($plan->free_ai_bids) }}</strong> AI bids</span>
                            </div>

                            <div class="feature-item {{ $plan->place_bids_limit > 0 ? '' : 'disabled' }}">
                                <i class="bi {{ $plan->place_bids_limit > 0 ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                <span>Place upto <strong>{{ number_format($plan->place_bids_limit) }}</strong> bids</span>
                            </div>

                            <div class="feature-item {{ $plan->skills_limit > 0 ? '' : 'disabled' }}">
                                <i class="bi {{ $plan->skills_limit > 0 ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                <span>Upto <strong>{{ number_format($plan->skills_limit) }}</strong> Skills Added</span>
                            </div>

                            <div class="feature-item {{ $plan->has_bid_price_matrix ? '' : 'disabled' }}">
                                <i class="bi {{ $plan->has_bid_price_matrix ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                <span>Bid Price Matrix Access</span>
                            </div>

                            <div class="feature-item {{ $plan->countries_limit > 0 ? '' : 'disabled' }}">
                                <i class="bi {{ $plan->countries_limit > 0 ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                <span>Upto <strong>{{ number_format($plan->countries_limit) }}</strong> countries added</span>
                            </div>

                            <div class="feature-item {{ $plan->languages_limit > 0 ? '' : 'disabled' }}">
                                <i class="bi {{ $plan->languages_limit > 0 ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                <span>Upto <strong>{{ number_format($plan->languages_limit) }}</strong> languages added</span>
                            </div>

                            <div class="feature-item {{ $plan->currencies_limit > 0 ? '' : 'disabled' }}">
                                <i class="bi {{ $plan->currencies_limit > 0 ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                <span>Upto <strong>{{ number_format($plan->currencies_limit) }}</strong> currencies added</span>
                            </div>
                        </div>

                        <!-- Card Checkout Button -->
                        <button type="button" class="btn {{ $plan->badge ? 'btn-primary' : 'btn-outline-primary' }} w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#cardPlanModal{{ $plan->id }}">
                            <i class="bi bi-credit-card-2-front"></i>
                            {{ $hasActiveSubscription ? 'Pay & Switch Plan' : 'Pay via Bank Card' }}
                        </button>
                    </div>

                    <!-- Bank Card Payment Modal (Only: Select Bank, Upload Screenshot, Submit Button) -->
                    <div class="modal fade" id="cardPlanModal{{ $plan->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                <form action="{{ route('purchase.subscription') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                                    <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-credit-card-fill text-primary fs-5"></i>
                                            <h5 class="modal-title fw-bold text-white mb-0">Bank Card Payment</h5>
                                        </div>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body p-4">
                                        <!-- Order Summary Header -->
                                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 mb-4 border">
                                            <div>
                                                <span class="text-muted small d-block">SELECTED PLAN</span>
                                                <h6 class="fw-bold text-dark mb-0">{{ $plan->name }} ({{ $plan->duration_days }} Days)</h6>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-muted small d-block">AMOUNT</span>
                                                <span class="fw-bold text-primary fs-5">Rs{{ number_format($plan->price, 2) }}</span>
                                            </div>
                                        </div>

                                        @if($bankCards->isEmpty())
                                            <div class="alert alert-warning border d-flex align-items-center gap-2 mb-0">
                                                <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                                                <div class="small">No bank cards added by admin yet. Please contact support.</div>
                                            </div>
                                        @else
                                            <!-- 1. Select Bank -->
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary mb-1">Select Bank <span class="text-danger">*</span></label>
                                                <select name="bank_card_id" class="form-select" required>
                                                    <option value="">-- Select Bank --</option>
                                                    @foreach($bankCards as $bCard)
                                                        <option value="{{ $bCard->id }}">
                                                            {{ $bCard->bank_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- 2. Upload Screenshot -->
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary mb-1">Upload Screenshot <span class="text-danger">*</span></label>
                                                <input type="file" name="payment_proof" class="form-control proof-input" accept="image/*" required>
                                                
                                                <!-- Live Preview -->
                                                <div class="preview-box mt-2 p-2 bg-light rounded border text-center d-none">
                                                    <img src="" alt="Screenshot Preview" class="img-fluid rounded preview-img" style="max-height: 160px; object-fit: contain;">
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="modal-footer bg-light border-0 p-3 px-4">
                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                        <!-- 3. Submit Button -->
                                        <button type="submit" class="btn btn-primary fw-semibold px-4" {{ $bankCards->isEmpty() ? 'disabled' : '' }}>
                                            Submit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-12 text-center py-5 text-muted">
                    No active subscription plans available at the moment.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Section 2: AI Bid Packages Module -->
    <div id="bid-packages-section" class="mb-5 pt-4">
        <div class="text-center mb-4">
            <span class="badge bg-warning bg-opacity-25 text-dark fw-semibold px-3 py-1 rounded-pill mb-2">
                <i class="bi bi-plus-circle-fill text-warning me-1"></i> Direct Quantity Add-ons
            </span>
            <h3 class="fw-bold text-dark mb-1">2. Top-Up AI Bid Packages</h3>
            <p class="text-muted small">Purchase direct AI bid quantities using Bank Card payment. Strictly available for active subscribers.</p>
        </div>

        @if(!$hasActiveSubscription)
            <!-- Lock Warning Banner for Unsubscribed Users -->
            <div class="alert alert-warning border-warning border-opacity-50 d-flex align-items-center gap-3 p-3 rounded-3 mb-4">
                <i class="bi bi-lock-fill fs-3 text-warning"></i>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Active Subscription Required</h6>
                    <p class="text-muted small mb-0">
                        AI Bid top-up packages cannot be purchased without an active subscription plan. Please select and activate any subscription plan above to unlock bid packages.
                    </p>
                </div>
            </div>
        @endif

        <div class="row g-4 justify-content-center">
            @forelse($bidPackages as $package)
                <div class="col-lg-4 col-md-6">
                    <div class="package-card p-4 h-100 position-relative">
                        
                        @if(!$hasActiveSubscription)
                            <!-- Lock Overlay -->
                            <div class="locked-overlay position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 text-center" style="z-index: 5;">
                                <div class="p-2 bg-white rounded-circle shadow-sm mb-2 text-warning fs-4">
                                    <i class="bi bi-lock-fill"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Package Locked</h6>
                                <span class="text-muted small" style="font-size: 12px;">Active Subscription Plan Required</span>
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold text-dark mb-1">{{ $package->name }}</h5>
                                <span class="text-muted small">{{ $package->description ?? 'Direct AI Bid boost.' }}</span>
                            </div>
                            @if($package->badge)
                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25">
                                    {{ $package->badge }}
                                </span>
                            @endif
                        </div>

                        <div class="d-flex align-items-baseline gap-2 mb-3">
                            <span class="display-6 fw-bold text-primary">+{{ number_format($package->bid_quantity) }}</span>
                            <span class="text-muted fw-semibold">AI Bids</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded-3 mb-4">
                            <div>
                                <span class="text-muted d-block small" style="font-size: 11px;">PRICE</span>
                                <span class="fw-bold text-dark fs-5">Rs{{ number_format($package->price, 2) }}</span>
                            </div>
                            <div class="text-end">
                                <span class="text-muted d-block small" style="font-size: 11px;">UNIT RATE</span>
                                <span class="text-muted small">
                                    Rs{{ number_format($package->bid_quantity > 0 ? ($package->price / $package->bid_quantity) : 0, 3) }}/bid
                                </span>
                            </div>
                        </div>

                        @if($hasActiveSubscription)
                            <button type="button" class="btn btn-outline-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2"
                                    data-bs-toggle="modal" data-bs-target="#cardPackageModal{{ $package->id }}">
                                <i class="bi bi-credit-card-2-front"></i> Pay & Add +{{ number_format($package->bid_quantity) }} Bids
                            </button>
                        @else
                            <button type="button" class="btn btn-light border text-muted w-100 py-2 fw-semibold" disabled>
                                <i class="bi bi-lock me-1"></i> Subscribe First
                            </button>
                        @endif
                    </div>

                    @if($hasActiveSubscription)
                        <!-- Bank Card Payment Package Modal (Only: Select Bank, Upload Screenshot, Submit Button) -->
                        <div class="modal fade" id="cardPackageModal{{ $package->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                    <form action="{{ route('purchase.bidPackage') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="package_id" value="{{ $package->id }}">

                                        <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-credit-card-fill text-warning fs-5"></i>
                                                <h5 class="modal-title fw-bold text-white mb-0">Bank Card Payment</h5>
                                            </div>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <!-- Top-Up Order Summary -->
                                            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 mb-4 border">
                                                <div>
                                                    <span class="text-muted small d-block">PACKAGE</span>
                                                    <h6 class="fw-bold text-dark mb-0">{{ $package->name }} (+{{ number_format($package->bid_quantity) }} Bids)</h6>
                                                </div>
                                                <div class="text-end">
                                                    <span class="text-muted small d-block">AMOUNT</span>
                                                    <span class="fw-bold text-primary fs-5">Rs{{ number_format($package->price, 2) }}</span>
                                                </div>
                                            </div>

                                            @if($bankCards->isEmpty())
                                                <div class="alert alert-warning border d-flex align-items-center gap-2 mb-0">
                                                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                                                    <div class="small">No bank cards added by admin yet. Please contact support.</div>
                                                </div>
                                            @else
                                                <!-- 1. Select Bank -->
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-secondary mb-1">Select Bank <span class="text-danger">*</span></label>
                                                    <select name="bank_card_id" class="form-select" required>
                                                        <option value="">-- Select Bank --</option>
                                                        @foreach($bankCards as $bCard)
                                                            <option value="{{ $bCard->id }}">
                                                                {{ $bCard->bank_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <!-- 2. Upload Screenshot -->
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-secondary mb-1">Upload Screenshot <span class="text-danger">*</span></label>
                                                    <input type="file" name="payment_proof" class="form-control proof-input" accept="image/*" required>
                                                    
                                                    <!-- Live Preview -->
                                                    <div class="preview-box mt-2 p-2 bg-light rounded border text-center d-none">
                                                        <img src="" alt="Screenshot Preview" class="img-fluid rounded preview-img" style="max-height: 160px; object-fit: contain;">
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="modal-footer bg-light border-0 p-3 px-4">
                                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                            <!-- 3. Submit Button -->
                                            <button type="submit" class="btn btn-primary fw-semibold px-4" {{ $bankCards->isEmpty() ? 'disabled' : '' }}>
                                                Submit
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            @empty
                <div class="col-12 text-center py-5 text-muted">
                    No AI bid packages available at the moment.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Section 3: Recent Order History for User -->
    @if($recentOrders->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-receipt text-primary me-2"></i>My Recent Purchases & Payment Verification
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size: 13.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>Ref Number</th>
                            <th>Type</th>
                            <th>Item Name</th>
                            <th>Bank</th>
                            <th>Screenshot</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $ord)
                            <tr>
                                <td class="font-monospace fw-semibold">{{ $ord->transaction_ref }}</td>
                                <td>
                                    @if($ord->order_type === 'subscription')
                                        <span class="badge bg-primary bg-opacity-10 text-primary">Subscription</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-dark">Bid Package</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $ord->item_name }}</div>
                                    @if($ord->bid_quantity_added > 0)
                                        <span class="text-success small fw-semibold">+{{ number_format($ord->bid_quantity_added) }} Bids</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ord->bankCard)
                                        <span class="fw-semibold text-dark">{{ $ord->bankCard->bank_name }}</span>
                                    @else
                                        <span class="badge bg-light text-dark border">{{ $ord->card_brand ?? 'Bank Card' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ord->screenshot_path)
                                        <button type="button" class="btn btn-sm btn-outline-secondary p-1 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#userReceiptModal{{ $ord->id }}">
                                            <img src="{{ asset('storage/' . $ord->screenshot_path) }}" alt="Receipt" class="screenshot-thumb-sm">
                                            <span class="small px-1">Proof</span>
                                        </button>

                                        <!-- User Receipt Modal -->
                                        <div class="modal fade" id="userReceiptModal{{ $ord->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                                    <div class="modal-header bg-dark text-white py-3 px-4 border-0">
                                                        <h6 class="modal-title fw-bold text-white mb-0">Receipt Ref: {{ $ord->transaction_ref }}</h6>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-3 text-center bg-light">
                                                        <img src="{{ asset('storage/' . $ord->screenshot_path) }}" alt="Receipt" class="img-fluid rounded border shadow-sm" style="max-height: 450px; object-fit: contain;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="fw-bold">Rs{{ number_format($ord->amount, 2) }}</td>
                                <td class="text-muted small">{{ $ord->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    @if($ord->status === 'completed')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> Completed
                                        </span>
                                    @elseif($ord->status === 'pending')
                                        <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-50 px-2 py-1">
                                            <i class="bi bi-hourglass-split text-warning me-1"></i> Pending Verification
                                        </span>
                                    @elseif($ord->status === 'rejected')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                            <i class="bi bi-x-circle-fill me-1"></i> Rejected
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white">{{ ucfirst($ord->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<!-- Interactive JavaScript for Live Screenshot Preview -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.proof-input').forEach(function (input) {
        input.addEventListener('change', function (e) {
            let parent = input.closest('form');
            let previewBox = parent ? parent.querySelector('.preview-box') : null;
            let previewImg = previewBox ? previewBox.querySelector('.preview-img') : null;

            if (!previewBox || !previewImg) return;

            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    previewImg.src = event.target.result;
                    previewBox.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                previewBox.classList.add('d-none');
                previewImg.src = '';
            }
        });
    });
});
</script>
@endsection
