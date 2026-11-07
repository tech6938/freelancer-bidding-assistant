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
                <i class="bi bi-box-seam text-primary me-2"></i>AI Bid Packages
            </h4>
            <p class="text-muted small mb-0">Manage direct AI bid quantity add-on packs. (Purchasable strictly by users with active subscriptions).</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createPackageModal">
            <i class="bi bi-plus-circle"></i> Create Bid Package
        </button>
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Please correct the errors:</h6>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Package Name</th>
                        <th>AI Bid Quantity</th>
                        <th>Price</th>
                        <th>Per-Bid Cost</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                        <tr class="{{ $package->trashed() ? 'table-light opacity-75' : '' }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="fw-semibold text-dark">{{ $package->name }}</div>
                                    @if($package->badge)
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25" style="font-size: 10px;">
                                            {{ $package->badge }}
                                        </span>
                                    @endif
                                </div>
                                @if($package->description)
                                    <div class="text-muted small" style="font-size: 11.5px;">{{ Str::limit($package->description, 50) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold fs-6">
                                    <i class="bi bi-lightning-charge-fill me-1"></i>+{{ number_format($package->bid_quantity) }} AI Bids
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-dark fs-6">${{ number_format($package->price, 2) }}</span>
                            </td>
                            <td>
                                @php
                                    $unitPrice = $package->bid_quantity > 0 ? ($package->price / $package->bid_quantity) : 0;
                                @endphp
                                <span class="text-muted small">${{ number_format($unitPrice, 3) }}/bid</span>
                            </td>
                            <td>
                                @if($package->trashed())
                                    <span class="badge bg-danger text-white">Archived (Soft Deleted)</span>
                                @elseif($package->is_active)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Active</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPackageModal{{ $package->id }}" title="Edit Package">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    @if($package->trashed())
                                        <form action="{{ route('admin.packages.restore', $package->id) }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Restore Package">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.packages.destroy', $package->id) }}" method="POST" class="m-0" onsubmit="return confirm('Soft delete this package? User order history will remain intact.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Soft Delete Package">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Package Modal -->
                        <div class="modal fade" id="editPackageModal{{ $package->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.packages.update', $package->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit AI Bid Package</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Package Name</label>
                                                <input type="text" name="name" class="form-control" value="{{ old('name', $package->name) }}" required>
                                            </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold">AI Bid Quantity</label>
                                                    <div class="input-group">
                                                        <input type="number" min="1" name="bid_quantity" class="form-control" value="{{ old('bid_quantity', $package->bid_quantity) }}" required>
                                                        <span class="input-group-text">Bids</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold">Price ($ USD)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $package->price) }}" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Badge (Optional)</label>
                                                <input type="text" name="badge" class="form-control" value="{{ old('badge', $package->badge) }}" placeholder="e.g. Popular, Best Value">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Description (Optional)</label>
                                                <textarea name="description" class="form-control" rows="2">{{ old('description', $package->description) }}</textarea>
                                            </div>

                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive{{ $package->id }}" {{ $package->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="editActive{{ $package->id }}">Active in Public Catalog</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary fw-semibold">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                No AI bid packages created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($packages->hasPages())
            <div class="p-3 border-top">
                {{ $packages->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Create Package Modal -->
<div class="modal fade" id="createPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.packages.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New AI Bid Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Package Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Starter 50 Bids, Booster 150 Bids" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">AI Bid Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="1" name="bid_quantity" class="form-control" placeholder="100" required>
                                <span class="input-group-text">Bids</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Price ($ USD) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" name="price" class="form-control" placeholder="15.00" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Badge (Optional)</label>
                        <input type="text" name="badge" class="form-control" placeholder="e.g. Most Popular, Best Value">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Instant top-up for active subscribers..."></textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newActive" checked>
                        <label class="form-check-label fw-semibold" for="newActive">Active in Public Catalog</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Create Package</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
