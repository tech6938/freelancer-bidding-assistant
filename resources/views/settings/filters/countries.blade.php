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
            <h5 class="fw-semibold text-primary mb-0" style="color: #2563eb !important;">Country Filters</h5>
            <p class="text-muted small mb-0">Select countries where you want to accept and bid on projects.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleAllCountries(true)">Select All</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllCountries(false)">Clear All</button>
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

    <div class="mb-3">
        <input type="search" id="countrySearch" class="form-control form-control-sm" placeholder="Search official Freelancer countries...">
    </div>

    <form action="{{ route('settings.filters.countries.update') }}" method="POST">
        @csrf
        <div class="row g-2 border rounded p-3 bg-light" style="max-height: 420px; overflow-y: auto;" id="countriesContainer">
            @forelse($allCountries as $country)
                <div class="col-md-3 col-sm-6 country-item" data-name="{{ strtolower($country['name']) }} {{ strtolower($country['code']) }}">
                    <div class="form-check">
                        <input class="form-check-input country-chk" 
                               type="checkbox" 
                               name="countries[]" 
                               value="{{ $country['code'] }}" 
                               id="c_{{ $country['code'] }}" 
                               {{ in_array($country['code'], $selectedCountries) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="c_{{ $country['code'] }}">
                            {{ $country['name'] }} <span class="text-muted" style="font-size: 11px;">({{ $country['code'] }})</span>
                        </label>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted small py-4">
                    Could not load countries from Freelancer API.
                </div>
            @endforelse
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
            <button type="submit" class="btn btn-save-custom">Save Countries</button>
        </div>
    </form>
</div>

<script>
    function toggleAllCountries(status) {
        document.querySelectorAll('.country-chk').forEach(cb => cb.checked = status);
    }

    document.getElementById('countrySearch').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.country-item').forEach(item => {
            item.style.display = item.dataset.name.includes(query) ? '' : 'none';
        });
    });
</script>
@endsection