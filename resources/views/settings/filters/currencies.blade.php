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
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
            <h5 class="fw-semibold text-primary mb-0" style="color: #2563eb !important;">Currency Filters</h5>
            <p class="text-muted small mb-0">Choose which currencies you accept for project bids and payments.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleAllCurrencies(true)">Select All</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllCurrencies(false)">Clear All</button>
        </div>
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

    <form action="{{ route('settings.filters.currencies.update') }}" method="POST">
        @csrf
        <div class="row g-3 border rounded p-3 bg-light" style="max-height: 380px; overflow-y: auto;">
            @forelse($allCurrencies as $curr)
                <div class="col-md-4 col-sm-6">
                    <div class="form-check">
                        <input class="form-check-input curr-chk" 
                               type="checkbox" 
                               name="currencies[]" 
                               value="{{ $curr['code'] }}" 
                               id="curr_{{ $curr['code'] }}" 
                               {{ in_array($curr['code'], $selectedCurrencies) ? 'checked' : '' }}>
                        <label class="form-check-label small fw-medium" for="curr_{{ $curr['code'] }}">
                            {{ $curr['code'] }} - {{ $curr['name'] }} ({{ $curr['sign'] ?? '' }})
                        </label>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted small py-4">
                    Could not load currencies from Freelancer API.
                </div>
            @endforelse
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
            <button type="submit" class="btn btn-save-custom">Save Currencies</button>
        </div>
    </form>
</div>

<script>
    function toggleAllCurrencies(status) {
        document.querySelectorAll('.curr-chk').forEach(cb => cb.checked = status);
    }
</script>
@endsection