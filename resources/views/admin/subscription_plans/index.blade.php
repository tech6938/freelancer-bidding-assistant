@extends('layouts.settings')

@section('settings_content')
<style>
    .admin-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
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
</style>

<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-card-checklist text-primary me-2"></i>Subscription Plans
            </h4>
            <p class="text-muted small mb-0">Configure engine access durations, included AI bids, and dynamic feature permissions.</p>
        </div>
        <a href="{{ route('admin.plans.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i> Create New Plan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Plan Name</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Free AI Bids</th>
                        <th>Dynamic Features Snapshot</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr class="{{ $plan->trashed() ? 'table-light opacity-75' : '' }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="fw-semibold text-dark">{{ $plan->name }}</div>
                                    @if($plan->badge)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 10px;">
                                             {{ $plan->badge }}
                                        </span>
                                    @endif
                                </div>
                                @if($plan->description)
                                    <div class="text-muted small" style="font-size: 11.5px;">{{ Str::limit($plan->description, 50) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold text-dark">Rs{{ number_format($plan->price, 2) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-clock me-1 text-muted"></i>{{ $plan->duration_days }} Days
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info fw-bold">
                                    <i class="bi bi-stars me-1"></i>{{ number_format($plan->free_ai_bids) }} AI Bids
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1" style="max-width: 360px;">
                                    @if($plan->has_autobid)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 11px;">
                                            <i class="bi bi-robot me-1"></i>Auto Bid
                                        </span>
                                    @endif
                                    @if($plan->place_bids_limit > 0)
                                        <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                            Place Bids: {{ $plan->place_bids_limit }}
                                        </span>
                                    @endif
                                    @if($plan->skills_limit > 0)
                                        <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                            Skills: {{ $plan->skills_limit }}
                                        </span>
                                    @endif
                                    @if($plan->has_bid_price_matrix)
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" style="font-size: 11px;">
                                            <i class="bi bi-currency-exchange me-1"></i>Price Matrix
                                        </span>
                                    @endif
                                    @if($plan->countries_limit > 0)
                                        <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                            Countries: {{ $plan->countries_limit }}
                                        </span>
                                    @endif
                                    @if($plan->languages_limit > 0)
                                        <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                            Languages: {{ $plan->languages_limit }}
                                        </span>
                                    @endif
                                    @if($plan->currencies_limit > 0)
                                        <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                            Currencies: {{ $plan->currencies_limit }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($plan->trashed())
                                    <span class="badge bg-danger text-white">Archived (Soft Deleted)</span>
                                @elseif($plan->is_active)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Active</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit Plan">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    @if($plan->trashed())
                                        <form action="{{ route('admin.plans.restore', $plan->id) }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Restore Plan">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST" class="m-0" onsubmit="return confirm('Soft delete this plan? Active users will remain safe.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Soft Delete Plan">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No subscription plans configured yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($plans->hasPages())
            <div class="p-3 border-top">
                {{ $plans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
