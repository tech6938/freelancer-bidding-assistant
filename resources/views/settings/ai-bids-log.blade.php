@extends('layouts.settings')

@section('settings_content')
<div class="container-fluid py-4 px-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Bidding Activity Log</h3>
            <p class="text-muted mb-0">Showing records for: <span class="text-primary fw-semibold">{{ $formattedDateTitle }}</span></p>
        </div>
        <div>
            @if($settings && $settings->is_autobid_enabled)
                <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill fs-6">
                    <i class="bi bi-circle-fill me-1 small"></i> Auto-Bidding Active
                </span>
            @else
                <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 rounded-pill fs-6">
                    <i class="bi bi-pause-circle-fill me-1"></i> Auto-Bidding Paused
                </span>
            @endif
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
    <div class="card shadow-sm border-0 rounded-4 p-3 bg-white">
        <div class="d-flex align-items-center">
            <div class="rounded-3 bg-primary-subtle text-primary p-3 me-3 fs-4">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <span class="text-muted small fw-semibold text-uppercase">Auto Bids</span>
                <h3 class="fw-bold mb-0 text-dark">
                    {{ $autoBidsCount }} 
                    <span class="fs-6 fw-normal text-muted">/ {{ $settings->max_daily_bids ?? 20 }}</span>
                </h3>
            </div>
        </div>
    </div>
</div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-secondary-subtle text-secondary p-3 me-3 fs-4">
                        <i class="bi bi-hand-index-thumb"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Manual Bids</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $manualBidsCount }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-success-subtle text-success p-3 me-3 fs-4">
                        <i class="bi bi-send-check-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Today Placed</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $selectedTotalBids }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-warning-subtle text-warning p-3 me-3 fs-4">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Average Price</span>
                        <h3 class="fw-bold mb-0 text-dark">${{ number_format($avgBidAmount, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar with Date Picker -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('settings.aibidslog') }}" method="GET" class="row g-2 align-items-center">
                <!-- 1. Select Date -->
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-calendar-event"></i></span>
                        <input type="date" name="date" class="form-control bg-light border-0" value="{{ $selectedDate }}">
                    </div>
                </div>

                <!-- 2. Search Box -->
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search Project ID or keywords..." value="{{ request('search') }}">
                    </div>
                </div>

                <!-- 3. Bid Type Filter -->
                <div class="col-md-3">
                    <select name="type" class="form-select bg-light border-0">
                        <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Bids</option>
                        <option value="auto" {{ request('type') == 'auto' ? 'selected' : '' }}>Auto Bids Only</option>
                        <option value="manual" {{ request('type') == 'manual' ? 'selected' : '' }}>Manual Bids Only</option>
                    </select>
                </div>

                <!-- 4. Filter Button & Reset -->
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold rounded-3">Filter</button>
                    @if(request()->hasAny(['search', 'type', 'date']))
                        <a href="{{ route('settings.aibidslog') }}" class="btn btn-light rounded-3 px-3" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Log Table -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Project</th>
                        <th>Amount & Period</th>
                        <th>Proposal Pitch</th>
                        <th>Type</th>
                        <th>Time Placed</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bids as $bid)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">#{{ $bid->freelancer_project_id }}</div>
                                <a href="https://www.freelancer.com/projects/{{ $bid->freelancer_project_id }}" target="_blank" class="text-decoration-none small text-primary">
                                    View on Freelancer <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">${{ number_format($bid->bid_amount, 2) }}</div>
                                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i>{{ $bid->delivery_days }} days</small>
                            </td>
                            <td style="max-width: 320px;">
                                <div class="text-truncate text-muted small" title="{{ $bid->proposal_text }}">
                                    {{ Str::limit($bid->proposal_text, 80) }}
                                </div>
                            </td>
                            <td>
                                @if($bid->bid_type === 'auto')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill">
                                        <i class="bi bi-robot me-1"></i> Auto Bid
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">
                                        <i class="bi bi-person me-1"></i> Manual
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark small">{{ $bid->created_at->format('h:i A') }}</div>
                                <small class="text-muted">{{ $bid->created_at->diffForHumans() }}</small>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#proposalModal{{ $bid->id }}">
                                    View Pitch
                                </button>

                                <!-- Proposal Preview Modal -->
                                <div class="modal fade text-start" id="proposalModal{{ $bid->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold">Submitted Proposal Pitch</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body py-3">
                                                <div class="d-flex justify-content-between p-2 bg-light rounded-3 mb-3 small">
                                                    <div><strong>Project ID:</strong> #{{ $bid->freelancer_project_id }}</div>
                                                    <div><strong>Price:</strong> ${{ number_format($bid->bid_amount, 2) }}</div>
                                                    <div><strong>Delivery:</strong> {{ $bid->delivery_days }} Days</div>
                                                </div>
                                                <label class="form-label text-muted small fw-semibold">Cover Letter Text:</label>
                                                <div class="p-3 bg-light rounded-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">
                                                    {{ $bid->proposal_text }}
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                                <a href="https://www.freelancer.com/projects/{{ $bid->freelancer_project_id }}" target="_blank" class="btn btn-primary rounded-pill px-4">
                                                    Open Project Link
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="fs-1 text-secondary mb-2"><i class="bi bi-calendar-x"></i></div>
                                <h6 class="fw-semibold">No Bids Found for {{ $formattedDateTitle }}</h6>
                                <p class="small mb-0">Select another date or reset filters to see other logs.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bids->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $bids->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection