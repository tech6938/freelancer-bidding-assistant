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
    .screenshot-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .screenshot-thumb:hover {
        transform: scale(1.08);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
</style>

<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-receipt-cutoff text-primary me-2"></i>Transactions & Order Approvals
            </h4>
            <p class="text-muted small mb-0">Review payment screenshots uploaded by users, approve/reject orders, and manage active user subscriptions.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Orders & Transaction Verification Card -->
    <div class="admin-card mb-5 overflow-hidden">
        <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-clock-history text-primary me-1"></i>Orders & Payment Proofs Log
                </h6>
            </div>

            <!-- Filter Controls -->
            <form action="{{ route('admin.orders.index') }}" method="GET" class="d-flex align-items-center gap-2 m-0 flex-wrap">
                <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Verification</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>

                <select name="type" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Order Types</option>
                    <option value="subscription" {{ request('type') === 'subscription' ? 'selected' : '' }}>Subscriptions</option>
                    <option value="bid_package" {{ request('type') === 'bid_package' ? 'selected' : '' }}>AI Bid Packages</option>
                </select>

                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Ref / User / Sender..." value="{{ request('search') }}" style="width: 190px;">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                @if(request()->hasAny(['type', 'status', 'search']))
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-link text-muted p-0">Clear</a>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Transaction Ref</th>
                        <th>User</th>
                        <th>Item Purchased</th>
                        <th>Bank Account</th>
                        <th>Payment Screenshot</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end">Verification Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <span class="font-monospace text-dark fw-bold" style="font-size: 12px;">{{ $order->transaction_ref }}</span>
                                <div class="text-muted small" style="font-size: 11px;">{{ $order->created_at->format('M d, Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $order->user->name ?? 'User #' . $order->user_id }}</div>
                                <div class="text-muted small" style="font-size: 11.5px;">{{ $order->user->email ?? '' }}</div>
                                @if($order->sender_name)
                                    <div class="text-muted small" style="font-size: 11px;"><i class="bi bi-person me-1"></i>Sender: {{ $order->sender_name }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    @if($order->order_type === 'subscription')
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 10px;">
                                            Subscription
                                        </span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25" style="font-size: 10px;">
                                            Bid Package
                                        </span>
                                    @endif
                                </div>
                                <div class="fw-medium text-dark">{{ $order->item_name }}</div>
                                @if($order->bid_quantity_added > 0)
                                    <span class="text-success small fw-semibold">+{{ number_format($order->bid_quantity_added) }} AI Bids</span>
                                @endif
                            </td>
                            <td>
                                @if($order->bankCard)
                                    <div class="d-flex align-items-center gap-2">
                                        @if($order->bankCard->logo_url)
                                            <img src="{{ $order->bankCard->logo_url }}" alt="Logo" class="rounded border p-1" style="width: 32px; height: 32px; object-fit: contain;">
                                        @endif
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $order->bankCard->bank_name }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge bg-light text-dark border">
                                        {{ $order->card_brand ?? 'Bank' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($order->screenshot_path)
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('storage/' . $order->screenshot_path) }}" 
                                             alt="Proof" 
                                             class="screenshot-thumb" 
                                             data-bs-toggle="modal" 
                                             data-bs-target="#screenshotModal{{ $order->id }}">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#screenshotModal{{ $order->id }}" style="font-size: 11.5px;">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </div>

                                    <!-- Screenshot Modal -->
                                    <div class="modal fade" id="screenshotModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                                <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                                                    <div>
                                                        <h6 class="modal-title fw-bold text-white mb-0">
                                                            <i class="bi bi-image me-2 text-primary"></i>Payment Screenshot Proof
                                                        </h6>
                                                        <span class="text-slate-300 small" style="font-size: 11.5px;">Ref: {{ $order->transaction_ref }} &bull; User: {{ $order->user->name ?? 'User' }}</span>
                                                    </div>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-3 text-center bg-light">
                                                    <img src="{{ asset('storage/' . $order->screenshot_path) }}" 
                                                         alt="Payment Proof" 
                                                         class="img-fluid rounded border shadow-sm" 
                                                         style="max-height: 550px; object-fit: contain;">
                                                    @if($order->sender_note)
                                                        <div class="alert alert-secondary mt-3 mb-0 text-start small">
                                                            <strong>Sender Notes:</strong> {{ $order->sender_note }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer bg-white border-0 py-2 px-4 justify-content-between">
                                                    <a href="{{ asset('storage/' . $order->screenshot_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Full Tab
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">No screenshot</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold text-dark fs-6">Rs{{ number_format($order->amount, 2) }}</span>
                            </td>
                            <td>
                                @if($order->status === 'completed')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> Completed
                                    </span>
                                @elseif($order->status === 'pending')
                                    <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50 px-2 py-1">
                                        <i class="bi bi-hourglass-split text-warning me-1"></i> Pending Verification
                                    </span>
                                @elseif($order->status === 'rejected')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                        <i class="bi bi-x-circle-fill me-1"></i> Rejected
                                    </span>
                                @else
                                    <span class="badge bg-secondary text-white">{{ ucfirst($order->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($order->status === 'pending')
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        <form action="{{ route('admin.orders.approve', $order->id) }}" method="POST" class="m-0" onsubmit="return confirm('Approve this order and activate benefits for the user?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success fw-semibold px-2 py-1" style="font-size: 12px;" title="Approve and activate">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </button>
                                        </form>

                                        <button class="btn btn-sm btn-outline-danger fw-semibold px-2 py-1" style="font-size: 12px;" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $order->id }}" title="Reject order">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </div>

                                    <!-- Reject Reason Modal -->
                                    <div class="modal fade" id="rejectModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden text-start">
                                                <form action="{{ route('admin.orders.reject', $order->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header bg-danger text-white border-0 py-3 px-4">
                                                        <h6 class="modal-title fw-bold text-white mb-0">
                                                            <i class="bi bi-x-octagon-fill me-2"></i>Reject Order #{{ $order->transaction_ref }}
                                                        </h6>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <p class="text-muted small mb-3">Please specify why this payment proof / order is being rejected:</p>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-secondary">Reason / Note</label>
                                                            <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Screenshot blurry, incorrect amount transferred, or invalid transaction reference..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light border-0 py-3 px-4">
                                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger fw-semibold px-4">Confirm Rejection</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small"><i class="bi bi-check2-all text-muted me-1"></i>Processed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                                No transaction orders recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="p-3 border-top">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- Active User Subscriptions Card -->
    <div class="admin-card mb-4 overflow-hidden">
        <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-person-badge text-primary me-2"></i>Active User Subscriptions
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Plan Name</th>
                        <th>Started At</th>
                        <th>Expires At</th>
                        <th>Days Left</th>
                        <th>Price Paid</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeSubscriptions as $sub)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">{{ $sub->user->name ?? 'User #' . $sub->user_id }}</div>
                                <div class="text-muted small">{{ $sub->user->email ?? '' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                    {{ $sub->plan_name ?? ($sub->plan->name ?? 'Subscription') }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $sub->started_at ? $sub->started_at->format('M d, Y H:i') : 'N/A' }}</td>
                            <td>
                                @if($sub->expires_at)
                                    <span class="text-dark">{{ $sub->expires_at->format('M d, Y') }}</span>
                                @else
                                    <span class="text-muted">Lifetime</span>
                                @endif
                            </td>
                            <td>
                                @if($sub->isValid())
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        {{ $sub->daysRemaining() }} Days
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Expired</span>
                                @endif
                            </td>
                            <td class="fw-bold text-dark">Rs{{ number_format($sub->price_paid, 2) }}</td>
                            <td>
                                @if($sub->isValid())
                                    <span class="badge bg-success text-white">Active</span>
                                @else
                                    <span class="badge bg-secondary text-white">Expired</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No active user subscriptions yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($activeSubscriptions->hasPages())
            <div class="p-3 border-top">
                {{ $activeSubscriptions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
