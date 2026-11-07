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
        <h5 class="fw-semibold text-primary mb-0" style="color: #2563eb !important;">Project Budget</h5>
        <p class="text-muted small mb-0">Set minimum and maximum price ranges for fixed-price and hourly projects.</p>
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

    <form action="{{ route('settings.filters.projectBudget.update') }}" method="POST">
        @csrf

        <!-- Project Type Radio -->
        <div class="mb-4 pb-3 border-bottom">
            <label class="form-label small fw-semibold d-block mb-2">Accepted Project Types</label>
            <div class="d-flex gap-4">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="project_type" id="typeBoth" value="both" {{ $filter->project_type === 'both' ? 'checked' : '' }}>
                    <label class="form-check-label small" for="typeBoth">Both (Fixed & Hourly)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="project_type" id="typeFixed" value="fixed" {{ $filter->project_type === 'fixed' ? 'checked' : '' }}>
                    <label class="form-check-label small" for="typeFixed">Fixed-Price Only</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="project_type" id="typeHourly" value="hourly" {{ $filter->project_type === 'hourly' ? 'checked' : '' }}>
                    <label class="form-check-label small" for="typeHourly">Hourly Projects Only</label>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Fixed Budget -->
            <div class="col-md-6">
                <div class="p-3 bg-light border rounded h-100">
                    <h6 class="fw-semibold text-dark small mb-3"><i class="bi bi-briefcase me-1 text-primary"></i> Fixed Price Range ($)</h6>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Minimum Fixed Budget ($)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="min_fixed_budget" class="form-control" value="{{ (int)$filter->min_fixed_budget }}" min="5" step="5" required>
                        </div>
                    </div>

                    <div>
                        <label class="form-label small fw-semibold">Maximum Fixed Budget ($)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="max_fixed_budget" class="form-control" value="{{ (int)$filter->max_fixed_budget }}" min="50" step="50" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hourly Rates -->
            <div class="col-md-6">
                <div class="p-3 bg-light border rounded h-100">
                    <h6 class="fw-semibold text-dark small mb-3"><i class="bi bi-clock-history me-1 text-primary"></i> Hourly Rate Range ($/hr)</h6>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Minimum Hourly Rate ($/hr)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$/hr</span>
                            <input type="number" name="min_hourly_rate" class="form-control" value="{{ (int)$filter->min_hourly_rate }}" min="2" step="1" required>
                        </div>
                    </div>

                    <div>
                        <label class="form-label small fw-semibold">Maximum Hourly Rate ($/hr)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$/hr</span>
                            <input type="number" name="max_hourly_rate" class="form-control" value="{{ (int)$filter->max_hourly_rate }}" min="10" step="5" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
            <button type="submit" class="btn btn-save-custom">Save Budget Limits</button>
        </div>
    </form>
</div>
@endsection