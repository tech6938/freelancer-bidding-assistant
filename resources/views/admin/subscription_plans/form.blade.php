@extends('layouts.settings')

@section('settings_content')
<style>
    .form-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        padding: 24px;
    }
    .feature-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        transition: all 0.2s ease;
    }
    .feature-box:hover {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }
</style>

<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-sliders text-primary me-2"></i>{{ $plan->exists ? 'Edit Subscription Plan' : 'Create Subscription Plan' }}
            </h4>
            <p class="text-muted small mb-0">Set pricing, engine duration, included AI bids, and toggle dynamic feature permissions.</p>
        </div>
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Back to Plans
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Please fix the following errors:</h6>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ $plan->exists ? route('admin.plans.update', $plan->id) : route('admin.plans.store') }}" method="POST">
        @csrf
        @if($plan->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <!-- Left: Basic Plan Configuration -->
            <div class="col-lg-6">
                <div class="form-card h-100">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="bi bi-info-circle text-primary me-2"></i>General Information
                    </h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" placeholder="e.g. Starter Plan, Professional, Agency Pro" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Price ($ USD) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $plan->price ?? 0) }}" placeholder="29.00" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Duration (Days) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="1" max="3650" name="duration_days" class="form-control" value="{{ old('duration_days', $plan->duration_days ?? 30) }}" required>
                                <span class="input-group-text">Days</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Badge / Tag (Optional)</label>
                        <input type="text" name="badge" class="form-control" value="{{ old('badge', $plan->badge) }}" placeholder="e.g. Popular, Recommended, Best Value">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Short Description / Subtitle</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief tagline or description for users on checkout...">{{ old('description', $plan->description) }}</textarea>
                    </div>

                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveSwitch" {{ old('is_active', $plan->exists ? $plan->is_active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold text-dark" for="isActiveSwitch">
                            Active in Public Catalog
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right: Dynamic Structured Feature Controls -->
            <div class="col-lg-6">
                <div class="form-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-check2-square text-success me-2"></i>Dynamic Feature Permissions
                        </h5>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 11px;">
                            Dynamic Inputs
                        </span>
                    </div>
                    <p class="text-muted small mb-4">Configure permissions and numeric quotas for this subscription plan.</p>

                    <div class="d-flex flex-column gap-3">
                        <!-- 1. Auto bidding included Checkbox -->
                        <div class="feature-box d-flex align-items-center justify-content-between">
                            <div>
                                <label class="fw-bold text-dark mb-0 cursor-pointer d-flex align-items-center gap-2" for="feature_autobid">
                                    <i class="bi bi-robot text-primary fs-5"></i> Auto bidding included
                                </label>
                                <span class="text-muted d-block small" style="font-size: 12px;">Auto Bid engine access included</span>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input fs-5" type="checkbox" name="has_autobid" value="1" id="feature_autobid"
                                    {{ old('has_autobid', $plan->has_autobid) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <!-- 2. Free () AI bids Number Input -->
                        <div class="feature-box">
                            <label class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-stars text-warning fs-5"></i> Free ( ) AI bids
                            </label>
                            <span class="text-muted d-block small mb-2" style="font-size: 12px;">AI generated bids (Custom modal generate & Auto bidding AI generator)</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                <input type="number" min="0" name="free_ai_bids" class="form-control"
                                    value="{{ old('free_ai_bids', $plan->free_ai_bids ?? 100) }}" placeholder="e.g. 100" required>
                                <span class="input-group-text">AI Bids</span>
                            </div>
                        </div>

                        <!-- 3. Place upto () bids Number Input -->
                        <div class="feature-box">
                            <label class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text text-primary fs-5"></i> Place upto ( ) bids
                            </label>
                            <span class="text-muted d-block small mb-2" style="font-size: 12px;">Template & manual placed bids limit</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                <input type="number" min="0" name="place_bids_limit" class="form-control"
                                    value="{{ old('place_bids_limit', $plan->place_bids_limit ?? 100) }}" placeholder="e.g. 100" required>
                                <span class="input-group-text">Placed Bids</span>
                            </div>
                        </div>

                        <!-- 4. Upto () Skills Added Number Input -->
                        <div class="feature-box">
                            <label class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-tags-fill text-success fs-5"></i> Upto ( ) Skills Added
                            </label>
                            <span class="text-muted d-block small mb-2" style="font-size: 12px;">Maximum skill slots user can configure</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                <input type="number" min="0" name="skills_limit" class="form-control"
                                    value="{{ old('skills_limit', $plan->skills_limit ?? 20) }}" placeholder="e.g. 20" required>
                                <span class="input-group-text">Skills</span>
                            </div>
                        </div>

                        <!-- 5. Bid Price Matrix Access Checkbox -->
                        <div class="feature-box d-flex align-items-center justify-content-between">
                            <div>
                                <label class="fw-bold text-dark mb-0 cursor-pointer d-flex align-items-center gap-2" for="feature_matrix">
                                    <i class="bi bi-currency-exchange text-danger fs-5"></i> Bid Price Matrix Access
                                </label>
                                <span class="text-muted d-block small" style="font-size: 12px;">Allow dynamic currency and pricing matrix settings</span>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input fs-5" type="checkbox" name="has_bid_price_matrix" value="1" id="feature_matrix"
                                    {{ old('has_bid_price_matrix', $plan->has_bid_price_matrix) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <!-- 6. Upto () countries added Number Input -->
                        <div class="feature-box">
                            <label class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-globe-americas text-info fs-5"></i> Upto ( ) countries added
                            </label>
                            <span class="text-muted d-block small mb-2" style="font-size: 12px;">Maximum country filters user can configure</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                <input type="number" min="0" name="countries_limit" class="form-control"
                                    value="{{ old('countries_limit', $plan->countries_limit ?? 10) }}" placeholder="e.g. 10" required>
                                <span class="input-group-text">Countries</span>
                            </div>
                        </div>

                        <!-- 7. Upto () languages added Number Input -->
                        <div class="feature-box">
                            <label class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-translate text-secondary fs-5"></i> Upto ( ) languages added
                            </label>
                            <span class="text-muted d-block small mb-2" style="font-size: 12px;">Maximum language filters user can configure</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                <input type="number" min="0" name="languages_limit" class="form-control"
                                    value="{{ old('languages_limit', $plan->languages_limit ?? 5) }}" placeholder="e.g. 5" required>
                                <span class="input-group-text">Languages</span>
                            </div>
                        </div>

                        <!-- 8. Upto () currencies added Number Input -->
                        <div class="feature-box">
                            <label class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-cash-stack text-success fs-5"></i> Upto ( ) currencies added
                            </label>
                            <span class="text-muted d-block small mb-2" style="font-size: 12px;">Maximum currency filters user can configure</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                <input type="number" min="0" name="currencies_limit" class="form-control"
                                    value="{{ old('currencies_limit', $plan->currencies_limit ?? 5) }}" placeholder="e.g. 5" required>
                                <span class="input-group-text">Currencies</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="d-flex align-items-center justify-content-end gap-3 mt-4">
            <a href="{{ route('admin.plans.index') }}" class="btn btn-light border px-4">Cancel</a>
            <button type="submit" class="btn btn-primary px-5 fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-check-circle"></i> {{ $plan->exists ? 'Save Changes' : 'Create Plan' }}
            </button>
        </div>
    </form>
</div>
@endsection
