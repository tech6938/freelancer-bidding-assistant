<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Freelancer Dashboard') }}</title>

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        .custom-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }
        .nav-link {
            font-weight: 500;
            color: #4b5563;
        }
        .nav-link:hover, .nav-link.active-link {
            color: #ea580c;
        }
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
        }
        .bid-badge {
            font-size: 0.8rem;
            color: #2563eb;
            font-weight: 600;
        }
        .dropdown-menu .dropdown-item {
            border-radius: 6px;
            margin: 2px 6px;
            width: auto;
            font-size: 13.5px;
            transition: all 0.15s ease-in-out;
        }
        .dropdown-menu .dropdown-item:hover {
            background-color: #f1f5f9;
            transform: translateX(3px);
        }
        .dropdown-menu .dropdown-item.text-danger:hover {
            background-color: #fee2e2;
            color: #dc2626 !important;
        }
        .dropdown-menu .dropdown-item.text-warning:hover {
            background-color: #fef3c7;
            color: #d97706 !important;
        }
    </style>
</head>
<body class="bg-light">

    @php
        $user = auth()->user();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();
        $activeConnection = $connection ?? (auth()->check() ? \App\Models\FreelancerConnection::where('user_id', auth()->id())->first() : null);
    @endphp

    {{-- Case 1: Admin Navbar --}}
    @if($isAdmin)
        <nav class="navbar navbar-expand-lg custom-navbar py-2 border-bottom shadow-sm bg-white">
            <div class="container-fluid px-4">
                <!-- Left: Admin Brand & Links -->
                <div class="d-flex align-items-center">
                    <a class="navbar-brand me-4 d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-shield-lock-fill fs-3 text-danger"></i>
                        <span class="fw-bold text-dark fs-5">Admin Panel</span>
                    </a>

                    <ul class="navbar-nav d-flex flex-row gap-3 mb-0">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active-link fw-bold' : '' }}" href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        </li>
                        <!-- <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active-link fw-bold' : '' }}" href="{{ route('admin.users') }}">
                                <i class="bi bi-people me-1"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.plans*') ? 'active-link fw-bold' : '' }}" href="{{ route('admin.plans.index') }}">
                                <i class="bi bi-card-checklist me-1"></i> Subscription Plans
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.packages*') ? 'active-link fw-bold' : '' }}" href="{{ route('admin.packages.index') }}">
                                <i class="bi bi-box-seam me-1"></i> Bid Packages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.orders*') ? 'active-link fw-bold' : '' }}" href="{{ route('admin.orders.index') }}">
                                <i class="bi bi-receipt me-1"></i> Orders
                            </a>
                        </li> -->
                    </ul>
                </div>

                <!-- Right: Admin Profile & Logout -->
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end lh-1">
                        <span class="d-block fw-semibold text-dark small">{{ $user->name ?? $user->username ?? 'Administrator' }}</span>
                        <span class="badge bg-danger text-uppercase" style="font-size: 10px;">Super Admin</span>
                    </div>

                    <div class="position-relative">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'Admin') }}&background=dc2626&color=fff"
                             alt="Avatar"
                             class="user-avatar border border-danger">
                    </div>

                    <!-- Logout Action Button -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 py-1 px-3 fw-medium rounded-2" type="submit">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </nav>

    {{-- Case 2: Standard Connected User Navbar --}}
    @elseif($activeConnection)
        <nav class="navbar navbar-expand-lg custom-navbar py-2">
            <div class="container-fluid px-4">

                <!-- Left: Logo & Nav Links -->
                <div class="d-flex align-items-center">
                    <a class="navbar-brand me-4" href="{{ route('freelancer.index') }}">
                        <i class="bi bi-person-workspace fs-2 text-dark"></i>
                    </a>

                    <ul class="navbar-nav d-flex flex-row gap-4 mb-0">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('settings.aibidslog') }}">My Bids</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('freelancer.index') ? 'active-link fw-bold' : '' }}" href="{{ route('freelancer.index') }}">Search Projects</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active-link fw-bold" href="#">Bidman AI</a>
                        </li> 
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('purchase.*') ? 'active-link fw-bold text-primary' : '' }}" href="{{ route('purchase.index') }}">
                                <i class="bi bi-stars text-warning me-1"></i>Purchase
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Right: User Info & Status -->
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end lh-1">
                        <span class="d-block fw-semibold text-dark small">
                            {{ $activeConnection->username ?? 'User' }}
                        </span>
                        <a href="{{ route('purchase.index') }}" class="text-decoration-none">
                            <span class="bid-badge" id="navbar_ai_bids_badge">
                                <i class="bi bi-lightning-charge-fill text-warning me-1"></i><span id="navbar_ai_bids_count">{{ number_format(auth()->user()->ai_bids_balance ?? 0) }}</span> AI bids left
                            </span>
                        </a>
                    </div>

                    <!-- Profile Avatar -->
                    <div class="position-relative">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($activeConnection->username ?? 'User') }}&background=0D8ABC&color=fff"
                             alt="Avatar"
                             class="user-avatar border">
                        <i class="bi bi-shield-fill-check position-absolute bottom-0 end-0 text-primary bg-white rounded-circle" style="font-size: 12px;"></i>
                    </div>

                    <!-- Attractive Modern Settings Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center p-2" 
                                type="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false" 
                                style="width: 36px; height: 36px; transition: all 0.2s ease;">
                            <i class="bi bi-gear-fill text-secondary"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 py-2 mt-2" style="min-width: 230px;">
                            <li class="dropdown-header text-uppercase fw-bold text-muted px-3 py-1" style="font-size: 11px; letter-spacing: 0.5px;">
                                Subscription & Top-up
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-dark" href="{{ route('purchase.index') }}">
                                    <i class="bi bi-cart-check-fill text-primary fs-6"></i> Plans & AI Bids
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-2 opacity-50"></li>
                            <li class="dropdown-header text-uppercase fw-bold text-muted px-3 py-1" style="font-size: 11px; letter-spacing: 0.5px;">
                                Configuration
                            </li>


                            <li>
                                <a class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 fw-medium text-dark" href="{{ route('settings.aiProjectMatch') }}">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-stars text-warning fs-6"></i> AI Project Match
                                    </span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold" style="font-size: 10px;">Smart</span>
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-dark" href="{{ route('settings.skills') }}">
                                    <i class="bi bi-tags text-primary fs-6"></i> Skills
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-dark" href="{{ route('settings.skillSets') }}">
                                    <i class="bi bi-collection text-info fs-6"></i> Skill Sets
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-dark" href="{{ route('settings.bidSettings') }}">
                                    <i class="bi bi-sliders text-success fs-6"></i> Bid Settings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-dark" href="{{ route('settings.aiBidSettings') }}">
                                    <i class="bi bi-gear text-success fs-6"></i> Ai Bid Settings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-dark" href="{{ route('settings.aibidslog') }}">
                                    <i class="bi bi-clock-history menu-icon"></i> Ai Bid logs
                                </a>
                            </li>
                             <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-dark" href="{{ route('settings.contests') }}">
                                    <i class="bi bi-clock-history menu-icon"></i> Contests
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-dark" href="{{ route('settings.profile') }}">
                                    <i class="bi bi-person-fill text-success fs-6"></i> Profile
                                </a>
                            </li>

                            <li><hr class="dropdown-divider my-2 opacity-50"></li>

                            <li class="dropdown-header text-uppercase fw-bold text-muted px-3 py-1" style="font-size: 11px; letter-spacing: 0.5px;">
                                Account Actions
                            </li>

                            <li>
                                <!-- <form action="{{ route('freelancer.disconnect') }}" method="POST" class="m-0">
                                    @csrf
                                    <button class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-warning" type="submit">
                                        <i class="bi bi-link-45deg fs-6"></i> Delete Account
                                    </button>
                                </form> -->
                            </li>

                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 fw-medium text-danger" type="submit">
                                        <i class="bi bi-box-arrow-right fs-6"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </nav>
    @endif

    <!-- Main Page Content -->
    <main>
        @yield('content')
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.updateNavbarAiBids = function(newBalance) {
            if (typeof newBalance !== 'undefined' && newBalance !== null) {
                const countEl = document.getElementById('navbar_ai_bids_count');
                if (countEl) {
                    countEl.textContent = Number(newBalance).toLocaleString();
                }
            }
        };
    </script>
</body>
</html>