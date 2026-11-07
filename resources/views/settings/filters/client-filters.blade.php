@extends('layouts.settings')

@section('settings_content')
<style>
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 20px;
    }
    .btn-save-custom {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        font-size: 13px;
        font-weight: 500;
        padding: 6px 20px;
        border-radius: 4px;
    }
    .btn-save-custom:hover {
        background-color: #1d4ed8;
        color: #ffffff;
    }
</style>

<div class="filter-card shadow-sm">
    <div class="mb-3 pb-2 border-bottom">
        <h5 class="fw-semibold text-primary mb-0" style="color: #2563eb !important;">Client Filters</h5>
        <p class="text-muted small mb-0">Filter employers based on verification status, star rating, and completed reviews.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 small mb-3 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger py-2 small mb-3 alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('settings.filters.clientFilters.update') }}" method="POST">
        @csrf

        <div class="row g-4">
            <!-- Payment Verification -->
            <div class="col-12">
                <div class="p-3 bg-light border rounded d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold text-dark small mb-1">Payment Method Verified</div>
                        <div class="text-muted" style="font-size: 11.5px;">Only show projects from clients who have verified their payment methods.</div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="require_payment_verified" value="1" {{ $filter->require_payment_verified ? 'checked' : '' }} style="width: 2.5em; height: 1.3em;">
                    </div>
                </div>
            </div>

            <!-- Identity Verification -->
            <div class="col-12">
                <div class="p-3 bg-light border rounded d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold text-dark small mb-1">Identity Verified</div>
                        <div class="text-muted" style="font-size: 11.5px;">Only show projects from clients who completed government ID verification on Freelancer.</div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="require_identity_verified" value="1" {{ $filter->require_identity_verified ? 'checked' : '' }} style="width: 2.5em; height: 1.3em;">
                    </div>
                </div>
            </div>

            <!-- Minimum Rating -->
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Minimum Client Rating</label>
                <select name="min_client_rating" class="form-select form-select-sm">
                    <option value="0.0" {{ $filter->min_client_rating == 0.0 ? 'selected' : '' }}>Any Rating (Include New Clients)</option>
                    <option value="3.0" {{ $filter->min_client_rating == 3.0 ? 'selected' : '' }}>3.0 Stars & Above</option>
                    <option value="3.5" {{ $filter->min_client_rating == 3.5 ? 'selected' : '' }}>3.5 Stars & Above</option>
                    <option value="4.0" {{ $filter->min_client_rating == 4.0 ? 'selected' : '' }}>4.0 Stars & Above (Recommended)</option>
                    <option value="4.5" {{ $filter->min_client_rating == 4.5 ? 'selected' : '' }}>4.5 Stars & Above</option>
                    <option value="4.8" {{ $filter->min_client_rating == 4.8 ? 'selected' : '' }}>4.8+ Stars Only</option>
                </select>
                <div class="text-muted mt-1" style="font-size: 11px;">Clients below this threshold will be excluded.</div>
            </div>

            <!-- Minimum Reviews -->
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Minimum Past Client Reviews</label>
                <input type="number" name="min_client_reviews" class="form-control form-control-sm" value="{{ $filter->min_client_reviews }}" min="0" max="200">
                <div class="text-muted mt-1" style="font-size: 11px;">Set to 0 to allow first-time job posters.</div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
            <button type="submit" class="btn btn-save-custom">Save Client Filters</button>
        </div>
    </form>
</div>
@endsection