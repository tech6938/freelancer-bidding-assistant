@extends('layouts.settings')

@section('settings_content')
<div class="card shadow-sm border-0">
    <!-- Header: Project Type & Currency Selector -->
    <div class="card-header bg-white py-3 border-bottom">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-0 fw-bold"><i class="bi bi-tag-fill text-primary me-2"></i> Bid Price Matrix</h5>
                <small class="text-muted">Configure default bid quote per budget tier and currency</small>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- 1. PROJECT TYPE TABS -->
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('bid-price.index', ['type' => 'fixed', 'currency' => $selectedCurrency]) }}" 
                       class="btn {{ $projectType === 'fixed' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Fixed Projects
                    </a>
                    <a href="{{ route('bid-price.index', ['type' => 'hourly', 'currency' => $selectedCurrency]) }}" 
                       class="btn {{ $projectType === 'hourly' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        Hourly Projects
                    </a>
                </div>

                <!-- 2. CURRENCY DROPDOWN -->
                <select class="form-select form-select-sm" style="min-width: 170px;" 
                        onchange="window.location.href='{{ route('bid-price.index') }}?type={{ $projectType }}&currency=' + this.value">
                    @foreach($currencies as $curr)
                        <option value="{{ $curr['code'] }}" {{ $selectedCurrency === $curr['code'] ? 'selected' : '' }}>
                            {{ $curr['code'] }} ({{ $curr['sign'] }}) - {{ $curr['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- 3. BUDGETS MATRIX -->
    <div class="card-body p-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('bid-price.store') }}" method="POST">
            @csrf
            <input type="hidden" name="project_type" value="{{ $projectType }}">
            <input type="hidden" name="currency_code" value="{{ $selectedCurrency }}">

            <div class="table-responsive">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 180px;">Budget Tier</th>
                            <th style="min-width: 180px;">Client Budget ({{ $selectedCurrency }})</th>
                            <th style="min-width: 200px;">Pricing Rule</th>
                            <th style="min-width: 200px;">Your Quote ({{ $currencySign }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budgets as $tier)
                            @php
                                $tierId = $tier['id'];
                                $saved = $savedPrices[$tierId] ?? null;
                                $min = (float) $tier['minimum'];
                                $max = isset($tier['maximum']) && $tier['maximum'] !== null ? (float) $tier['maximum'] : null;
                                $rule = $saved ? $saved->rule_type : 'min';
                                $amount = $saved ? $saved->bid_amount : $min;
                                $maxJsParam = $max !== null ? $max : 'null';
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    <span class="d-block">{{ $tier['name'] }}</span>
                                    <input type="hidden" name="tiers[{{ $tierId }}][budget_tier_id]" value="{{ $tierId }}">
                                    <input type="hidden" name="tiers[{{ $tierId }}][tier_name]" value="{{ $tier['name'] }}">
                                    <input type="hidden" name="tiers[{{ $tierId }}][min_budget]" value="{{ $min }}">
                                    <input type="hidden" name="tiers[{{ $tierId }}][max_budget]" value="{{ $max ?? '' }}">
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-6">
                                        @if($max !== null)
                                            {{ $currencySign }}{{ $min }} - {{ $currencySign }}{{ $max }}
                                        @else
                                            {{ $currencySign }}{{ $min }}+
                                        @endif
                                        {{ $projectType === 'hourly' ? '/hr' : '' }}
                                    </span>
                                </td>
                                <td>
                                    <select name="tiers[{{ $tierId }}][rule_type]" 
                                            class="form-select form-select-sm" 
                                            onchange="autoCalculatePrice(this, {{ $min }}, {{ $maxJsParam }})">
                                        <option value="min" {{ $rule === 'min' ? 'selected' : '' }}>Client Min ({{ $currencySign }}{{ $min }})</option>
                                        @if($max !== null)
                                            <option value="avg" {{ $rule === 'avg' ? 'selected' : '' }}>Average ({{ $currencySign }}{{ round(($min + $max) / 2) }})</option>
                                            <option value="max" {{ $rule === 'max' ? 'selected' : '' }}>Client Max ({{ $currencySign }}{{ $max }})</option>
                                        @endif
                                        <option value="custom" {{ $rule === 'custom' ? 'selected' : '' }}>Custom Quote</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">{{ $currencySign }}</span>
                                        <input type="number" 
                                               step="any"
                                               name="tiers[{{ $tierId }}][bid_amount]" 
                                               class="form-control tier-amount-input" 
                                               value="{{ $amount }}" 
                                               required>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    No budget tiers found for {{ $selectedCurrency }} in {{ $projectType }} mode.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(!empty($budgets))
                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Changes will apply to all new automated bids for {{ $selectedCurrency }}.</small>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i> Save Price Rules
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>

<script>
function autoCalculatePrice(selectElement, min, max) {
    const row = selectElement.closest('tr');
    const input = row.querySelector('.tier-amount-input');
    const rule = selectElement.value;

    min = parseFloat(min) || 0;
    max = (max !== null && max !== undefined && max !== '' && !isNaN(max)) ? parseFloat(max) : min;

    if (rule === 'min') {
        input.value = min;
    } else if (rule === 'max') {
        input.value = max;
    } else if (rule === 'avg') {
        input.value = Math.round((min + max) / 2);
    }
}
</script>
@endsection