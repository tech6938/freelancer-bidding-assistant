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
        padding: 13px 16px;
        vertical-align: middle;
        font-size: 13.5px;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tbody tr:last-child td {
        border-bottom: none;
    }
    .table-modern tbody tr:hover {
        background-color: #f8fafc;
    }
    .user-avatar-sm {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
    }
    .stat-pill {
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .stat-pill:hover {
        transform: translateY(-1px);
    }
</style>

<div class="user-management">
    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-people-fill text-primary"></i> User Management
            </h4>
            <p class="text-muted small mb-0">Search, filter, monitor account statuses, and manage access restrictions.</p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
            <span class="badge bg-primary bg-opacity-10 text-primary border px-3 py-2 rounded-pill">
                Total: {{ number_format($totalCount) }} Users
            </span>
        </div>
    </div>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small d-flex align-items-center gap-2 mb-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small d-flex align-items-center gap-2 mb-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Quick Filter Stat Pills -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.users') }}" class="text-decoration-none">
                <div class="admin-card p-2 text-center stat-pill {{ !request()->has('status') && !request()->has('role') ? 'border-primary bg-primary bg-opacity-10' : '' }}">
                    <span class="d-block text-muted small" style="font-size: 11.5px;">All Users</span>
                    <span class="fw-bold fs-6 text-dark">{{ $totalCount }}</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.users', ['status' => 'active']) }}" class="text-decoration-none">
                <div class="admin-card p-2 text-center stat-pill {{ request('status') === 'active' ? 'border-success bg-success bg-opacity-10' : '' }}">
                    <span class="d-block text-success small" style="font-size: 11.5px;">Active Accounts</span>
                    <span class="fw-bold fs-6 text-success">{{ $activeCount }}</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.users', ['status' => 'banned']) }}" class="text-decoration-none">
                <div class="admin-card p-2 text-center stat-pill {{ request('status') === 'banned' ? 'border-danger bg-danger bg-opacity-10' : '' }}">
                    <span class="d-block text-danger small" style="font-size: 11.5px;">Banned Accounts</span>
                    <span class="fw-bold fs-6 text-danger">{{ $bannedCount }}</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.users', ['role' => 'admin']) }}" class="text-decoration-none">
                <div class="admin-card p-2 text-center stat-pill {{ request('role') === 'admin' ? 'border-warning bg-warning bg-opacity-10' : '' }}">
                    <span class="d-block text-warning small" style="font-size: 11.5px;">Administrators</span>
                    <span class="fw-bold fs-6 text-dark">{{ $adminCount }}</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Search & Filter Form Toolbar -->
    <div class="admin-card p-3 mb-4">
        <form method="GET" action="{{ route('admin.users') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search by name, email, or username...">
                </div>
            </div>

            <div class="col-md-2">
                <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Banned</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="connection" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Freelancer</option>
                    <option value="connected" {{ request('connection') === 'connected' ? 'selected' : '' }}>Connected</option>
                    <option value="disconnected" {{ request('connection') === 'disconnected' ? 'selected' : '' }}>Disconnected</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-filter"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'role', 'status', 'connection']))
                    <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Paginated Data Table Card -->
    <div class="admin-card p-0 mb-4">
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Freelancer</th>
                        <th>Auto-Bid</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $usr)
                        <tr>
                            <!-- User Details -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($usr->name ?? 'User') }}&background={{ $usr->isAdmin() ? 'dc2626' : '0284c7' }}&color=fff" 
                                         class="user-avatar-sm border" alt="Avatar">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $usr->name ?? 'N/A' }}</div>
                                        <div class="text-muted small" style="font-size: 11.5px;">{{ $usr->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role Badge -->
                            <td>
                                @if($usr->isAdmin())
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                        <i class="bi bi-shield-lock-fill"></i> Admin
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">
                                        <i class="bi bi-person"></i> User
                                    </span>
                                @endif
                            </td>

                            <!-- Account Status (Active vs Banned) -->
                            <td>
                                @if($usr->is_banned)
                                    <span class="badge bg-danger px-2 py-1" title="Reason: {{ $usr->ban_reason ?? 'No reason provided' }}">
                                        <i class="bi bi-slash-circle me-1"></i> Banned
                                    </span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                    </span>
                                @endif
                            </td>

                            <!-- Freelancer Connection -->
                            <td>
                                @if($usr->freelancerConnection && !empty($usr->freelancerConnection->freelancer_user_id))
                                    <span class="text-success small fw-medium d-flex align-items-center gap-1">
                                        <i class="bi bi-link-45deg fs-6"></i> Connected
                                        <span class="text-muted" style="font-size: 11px;">(#{{ $usr->freelancerConnection->freelancer_user_id }})</span>
                                    </span>
                                @else
                                    <span class="text-muted small d-flex align-items-center gap-1">
                                        <i class="bi bi-x-circle text-muted"></i> No
                                    </span>
                                @endif
                            </td>

                            <!-- Auto-Bid Mode -->
                            <td>
                                @if($usr->aiBidSetting && $usr->aiBidSetting->is_autobid_enabled)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        <i class="bi bi-lightning-charge-fill"></i> Enabled
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border">
                                        Disabled
                                    </span>
                                @endif
                            </td>

                            <!-- Joined Date -->
                            <td>
                                <span class="text-dark small">{{ $usr->created_at ? $usr->created_at->format('M d, Y') : 'N/A' }}</span>
                                <small class="text-muted d-block" style="font-size: 11px;">{{ $usr->created_at ? $usr->created_at->diffForHumans() : '' }}</small>
                            </td>

                            <!-- Action Dropdown & Modals -->
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border py-1 px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="font-size: 13px;">
                                        <li>
                                            <a class="dropdown-item py-1" href="#" data-bs-toggle="modal" data-bs-target="#viewUserModal{{ $usr->id }}">
                                                <i class="bi bi-eye text-primary me-2"></i> View Details
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        @if(!$usr->isAdmin() && $usr->id !== auth()->id())
                                            <li>
                                                <button class="dropdown-item py-1 {{ $usr->is_banned ? 'text-success' : 'text-danger' }}" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#banModal{{ $usr->id }}">
                                                    <i class="bi {{ $usr->is_banned ? 'bi-check-circle text-success' : 'bi-slash-circle text-danger' }} me-2"></i>
                                                    {{ $usr->is_banned ? 'Unban User' : 'Ban User' }}
                                                </button>
                                            </li>
                                        @else
                                            <li>
                                                <span class="dropdown-item text-muted disabled py-1">
                                                    <i class="bi bi-shield-lock me-2"></i> Protected
                                                </span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>

                                <!-- View User Details Modal -->
                                <div class="modal fade text-start" id="viewUserModal{{ $usr->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-light">
                                                <h6 class="modal-title fw-bold text-dark">
                                                    <i class="bi bi-person-circle me-1 text-primary"></i> User Details
                                                </h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($usr->name ?? 'User') }}&background=0284c7&color=fff" 
                                                         class="rounded-circle" width="54" height="54" alt="Avatar">
                                                    <div>
                                                        <h5 class="fw-bold mb-0 text-dark">{{ $usr->name }}</h5>
                                                        <span class="text-muted small">{{ $usr->email }}</span>
                                                    </div>
                                                </div>

                                                <div class="row g-2 small">
                                                    <div class="col-6 text-muted">User ID:</div>
                                                    <div class="col-6 fw-bold text-dark">#{{ $usr->id }}</div>

                                                    <div class="col-6 text-muted">Username:</div>
                                                    <div class="col-6 fw-bold text-dark">{{ $usr->username ?? 'Not set' }}</div>

                                                    <div class="col-6 text-muted">Role:</div>
                                                    <div class="col-6 fw-bold text-dark text-capitalize">{{ $usr->role ?? 'user' }}</div>

                                                    <div class="col-6 text-muted">Account Status:</div>
                                                    <div class="col-6 fw-bold {{ $usr->is_banned ? 'text-danger' : 'text-success' }}">
                                                        {{ $usr->is_banned ? 'Banned' : 'Active' }}
                                                    </div>

                                                    @if($usr->is_banned && $usr->ban_reason)
                                                        <div class="col-6 text-muted">Ban Reason:</div>
                                                        <div class="col-6 text-danger fw-medium">{{ $usr->ban_reason }}</div>
                                                    @endif

                                                    <div class="col-6 text-muted">Freelancer Account:</div>
                                                    <div class="col-6 fw-bold text-dark">
                                                        {{ $usr->freelancerConnection ? 'Connected (#' . $usr->freelancerConnection->freelancer_user_id . ')' : 'Not Connected' }}
                                                    </div>

                                                    <div class="col-6 text-muted">Auto-Bidding Engine:</div>
                                                    <div class="col-6 fw-bold text-dark">
                                                        {{ ($usr->aiBidSetting && $usr->aiBidSetting->is_autobid_enabled) ? 'Enabled' : 'Disabled' }}
                                                    </div>

                                                    <div class="col-6 text-muted">Daily Bidding Quota:</div>
                                                    <div class="col-6 fw-bold text-dark">
                                                        {{ $usr->aiBidSetting->max_daily_bids ?? 10 }} bids/day
                                                    </div>

                                                    <div class="col-6 text-muted">Joined Date:</div>
                                                    <div class="col-6 fw-bold text-dark">
                                                        {{ $usr->created_at ? $usr->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light py-2">
                                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dynamic Ban / Unban Form Modal -->
                                @if(!$usr->isAdmin() && $usr->id !== auth()->id())
                                    <div class="modal fade text-start" id="banModal{{ $usr->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form action="{{ route('admin.users.toggleBan', $usr->id) }}" method="POST" class="modal-content border-0 shadow">
                                                @csrf
                                                <div class="modal-header {{ $usr->is_banned ? 'bg-success text-white' : 'bg-danger text-white' }}">
                                                    <h6 class="modal-title fw-bold">
                                                        <i class="bi {{ $usr->is_banned ? 'bi-check-circle' : 'bi-exclamation-triangle' }} me-1"></i>
                                                        {{ $usr->is_banned ? 'Confirm Unban User' : 'Confirm Ban User' }}
                                                    </h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <p class="mb-3 text-dark">
                                                        Are you sure you want to {{ $usr->is_banned ? 'unban' : 'ban' }} 
                                                        <strong>{{ $usr->name }}</strong> (<code>{{ $usr->email }}</code>)?
                                                    </p>

                                                    @if(!$usr->is_banned)
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-muted">Reason for ban (shown to user upon login):</label>
                                                            <textarea name="ban_reason" class="form-control form-control-sm" rows="2" placeholder="e.g. Suspicious automated activity, terms violation..."></textarea>
                                                        </div>
                                                        <div class="alert alert-warning py-2 px-3 small mb-0">
                                                            <i class="bi bi-info-circle me-1"></i> Once banned, the user will be logged out and prevented from accessing the application.
                                                        </div>
                                                    @else
                                                        <div class="alert alert-info py-2 px-3 small mb-0">
                                                            <i class="bi bi-check-circle me-1"></i> Unbanning will restore normal access to the user account immediately.
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer bg-light py-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm {{ $usr->is_banned ? 'btn-success' : 'btn-danger' }}">
                                                        {{ $usr->is_banned ? 'Unban User Now' : 'Confirm Ban' }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                                No users matched your search/filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Server-side Pagination Links -->
        @if($users->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
                </small>
                <div>
                    {{ $users->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
