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
    .bank-logo-preview {
        width: 44px;
        height: 44px;
        object-fit: contain;
        border-radius: 8px;
        background: #f8fafc;
        padding: 4px;
        border: 1px solid #e2e8f0;
    }
</style>

<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-credit-card-2-front text-primary me-2"></i>Bank Cards & Accounts
            </h4>
            <p class="text-muted small mb-0">Manage bank options available for user checkout.</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createBankCardModal">
            <i class="bi bi-plus-circle"></i> Add Bank / Card
        </button>
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Please correct the following errors:</h6>
            <ul class="mb-0 small ps-3">
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
                        <th>Logo</th>
                        <th>Bank / Card Provider Name</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards as $card)
                        <tr class="{{ $card->trashed() ? 'table-light opacity-75' : '' }}">
                            <td style="width: 70px;">
                                @if($card->logo_url)
                                    <img src="{{ $card->logo_url }}" alt="{{ $card->bank_name }}" class="bank-logo-preview">
                                @else
                                    <div class="bank-logo-preview d-flex align-items-center justify-content-center text-secondary">
                                        <i class="bi bi-bank fs-5"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold text-dark fs-6">{{ $card->bank_name }}</span>
                            </td>
                            <td>
                                @if($card->trashed())
                                    <span class="badge bg-danger text-white">Archived</span>
                                @elseif($card->is_active)
                                    <form action="{{ route('admin.bank-cards.toggle', $card->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 border-0 p-2" title="Click to Deactivate">
                                            <i class="bi bi-check-circle-fill me-1"></i> Active
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.bank-cards.toggle', $card->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="badge bg-secondary bg-opacity-10 text-secondary border border-0 p-2" title="Click to Activate">
                                            <i class="bi bi-pause-circle me-1"></i> Inactive
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editBankCardModal{{ $card->id }}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    @if($card->trashed())
                                        <form action="{{ route('admin.bank-cards.restore', $card->id) }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Restore">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.bank-cards.destroy', $card->id) }}" method="POST" class="m-0" onsubmit="return confirm('Archive this bank?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Archive / Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Bank Card Modal -->
                        <div class="modal fade" id="editBankCardModal{{ $card->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                    <form action="{{ route('admin.bank-cards.update', $card->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                                            <h5 class="modal-title fw-bold text-white mb-0">
                                                <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Bank / Card
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary mb-1">Bank / Card Provider Name <span class="text-danger">*</span></label>
                                                <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $card->bank_name) }}" placeholder="e.g. Meezan Bank, HBL, Visa Card, SadaPay" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary mb-1">Logo Image (Optional)</label>
                                                @if($card->logo_url)
                                                    <div class="mb-2 d-flex align-items-center gap-2">
                                                        <img src="{{ $card->logo_url }}" alt="Logo" class="bank-logo-preview">
                                                        <span class="text-muted small">Current Logo</span>
                                                    </div>
                                                @endif
                                                <input type="file" name="logo_image" class="form-control" accept="image/*">
                                                <span class="text-muted small" style="font-size: 11px;">Upload image/icon (PNG, JPG, SVG up to 2MB).</span>
                                            </div>

                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive{{ $card->id }}" {{ $card->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="editActive{{ $card->id }}">Active for User Checkout</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light border-0 py-3 px-4">
                                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary fw-semibold px-4">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-credit-card-2-front fs-1 d-block mb-2 text-secondary"></i>
                                No bank cards or accounts configured yet.
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createBankCardModal">
                                        <i class="bi bi-plus-circle me-1"></i> Add Bank / Card
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cards->hasPages())
            <div class="p-3 border-top">
                {{ $cards->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Create Bank Card Modal -->
<div class="modal fade" id="createBankCardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="{{ route('admin.bank-cards.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                    <h5 class="modal-title fw-bold text-white mb-0">
                        <i class="bi bi-plus-circle-fill me-2 text-primary"></i>Add Bank / Card
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Bank / Card Provider Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" placeholder="e.g. Meezan Bank, HBL, Visa Card, SadaPay" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Logo Image (Optional)</label>
                        <input type="file" name="logo_image" class="form-control" accept="image/*">
                        <span class="text-muted small" style="font-size: 11px;">Optional bank logo/icon (PNG, JPG, SVG up to 2MB).</span>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newActiveCard" checked>
                        <label class="form-check-label fw-semibold" for="newActiveCard">Active for User Checkout</label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">Save & Add Bank</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
