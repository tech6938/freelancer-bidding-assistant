@extends('layouts.app')

@section('content')
<style>
    /* Top Green Notification Banner */
    .bidman-top-banner {
        background-color: #22c55e;
        color: #ffffff;
        font-size: 14.5px;
        font-weight: 500;
        text-align: center;
        padding: 8px 0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .bidman-top-banner a {
        color: #ffffff;
        font-weight: 700;
        text-decoration: underline;
    }

    /* Left Sidebar Styling */
    .settings-sidebar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 0;
    }
    .settings-sidebar .sidebar-heading {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        padding: 10px 18px 4px;
    }
    .settings-sidebar .nav-divider {
        height: 1px;
        background-color: #f1f5f9;
        margin: 8px 0;
    }
    .settings-sidebar .nav-link {
        color: #475569;
        font-size: 13.5px;
        padding: 7px 18px;
        display: flex;
        align-items: center;
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .settings-sidebar .nav-link i.menu-icon {
        font-size: 14px;
        margin-right: 12px;
        width: 16px;
        color: #64748b;
    }
    .settings-sidebar .nav-link:hover {
        background-color: #f8fafc;
        color: #0f172a;
    }
    
    /* Active Link State */
    .settings-sidebar .nav-link.active {
        color: #ea580c !important;
        font-weight: 600;
        background-color: #fff7ed;
        border-left-color: #ea580c;
    }
    .settings-sidebar .nav-link.active i.menu-icon {
        color: #ea580c !important;
    }

    /* Sub-menu styling */
    .settings-sidebar .sub-menu .nav-link {
        padding-left: 38px;
        font-size: 13px;
    }
    .settings-sidebar .sub-menu .nav-link i.menu-icon {
        font-size: 13px;
        margin-right: 10px;
    }
</style>

<!-- Full-width Dynamic Status Banner -->
@include('partials.autobid-banner')

<div class="container-fluid px-4 py-3">
    <div class="row">
        
        <!-- Left Sidebar Navigation -->
        <div class="col-xl-2 col-lg-3 col-md-4 mb-4">
            <div class="settings-sidebar shadow-sm">

                <nav class="nav flex-column" id="settingsSidebarNav">
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <div class="sidebar-heading">Admin</div>
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2 menu-icon"></i> Dashboard
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                            <i class="bi bi-people-fill menu-icon"></i> User Management
                        </a>
                        
                        <div class="sidebar-heading mt-2">Monetization</div>
                        <a class="nav-link {{ request()->routeIs('admin.plans*') ? 'active' : '' }}" href="{{ route('admin.plans.index') }}">
                            <i class="bi bi-card-checklist menu-icon"></i> Subscription Plans
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.packages*') ? 'active' : '' }}" href="{{ route('admin.packages.index') }}">
                            <i class="bi bi-box-seam menu-icon"></i> AI Bid Packages
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.bank-cards*') ? 'active' : '' }}" href="{{ route('admin.bank-cards.index') }}">
                            <i class="bi bi-credit-card-2-front menu-icon"></i> Bank Cards & Accounts
                        </a>
                        <a class="nav-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                            <i class="bi bi-receipt-cutoff menu-icon"></i> Orders & Subscriptions
                        </a>
                    @else

                        {{-- AI Automation Section --}}
                        <div class="sidebar-heading">AI Automation</div>

                        <a class="nav-link {{ request()->routeIs('settings.aiBidSettings') ? 'active' : '' }}" href="{{ route('settings.aiBidSettings') }}">
                            <i class="bi bi-robot menu-icon"></i> AI Bids Settings
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('settings.aiProjectMatch') ? 'active' : '' }}" href="{{ route('settings.aiProjectMatch') }}">
                            <i class="bi bi-bullseye menu-icon"></i> AI Project Match
                        </a>

                        {{-- Project Filters Dropdown --}}
                        @php $isFilterActive = request()->routeIs('settings.filters.*'); @endphp
                        <a class="nav-link justify-content-between {{ $isFilterActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#projectFiltersSubmenu" role="button" aria-expanded="{{ $isFilterActive ? 'true' : 'false' }}">
                            <span><i class="bi bi-funnel menu-icon"></i> Project Filters</span>
                            <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                        </a>
                        <div class="collapse sub-menu {{ $isFilterActive ? 'show' : '' }}" id="projectFiltersSubmenu">
                            <a class="nav-link {{ request()->routeIs('settings.filters.countries') ? 'active' : '' }}" href="{{ route('settings.filters.countries') }}">
                                <i class="bi bi-globe menu-icon"></i> Countries
                            </a>
                            <a class="nav-link {{ request()->routeIs('settings.filters.languages') ? 'active' : '' }}" href="{{ route('settings.filters.languages') }}">
                                <i class="bi bi-translate menu-icon"></i> Languages
                            </a>
                            <a class="nav-link {{ request()->routeIs('settings.filters.currencies') ? 'active' : '' }}" href="{{ route('settings.filters.currencies') }}">
                                <i class="bi bi-currency-dollar menu-icon"></i> Currencies
                            </a>
                        </div>

                        <a class="nav-link {{ request()->routeIs('settings.aibidslog') ? 'active' : '' }}" href="{{ route('settings.aibidslog') }}">
                            <i class="bi bi-clock-history menu-icon"></i> AI Bids Log
                        </a>

                        <div class="nav-divider"></div>

                        {{-- Bidding & Profile Settings Section --}}
                        <div class="sidebar-heading">General & Bidding</div>

                        <a class="nav-link {{ request()->routeIs('settings.skills') ? 'active' : '' }}" href="{{ route('settings.skills') }}">
                            <i class="bi bi-heart menu-icon"></i> Skills
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('settings.skillSets') ? 'active' : '' }}" href="{{ route('settings.skillSets') }}">
                            <i class="bi bi-layers menu-icon"></i> Skill Sets
                        </a>

                        {{-- Bid Settings Dropdown --}}
                        @php $isBidSettingsActive = request()->routeIs('settings.bidSettings', 'bid-price.*'); @endphp
                        <a class="nav-link justify-content-between {{ $isBidSettingsActive ? 'active' : '' }}" data-bs-toggle="collapse" href="#bidSettingsSubmenu" role="button" aria-expanded="{{ $isBidSettingsActive ? 'true' : 'false' }}">
                            <span><i class="bi bi-pencil-square menu-icon"></i> Bid Settings</span>
                            <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                        </a>
                        <div class="collapse sub-menu {{ $isBidSettingsActive ? 'show' : '' }}" id="bidSettingsSubmenu">
                            <a class="nav-link {{ request()->routeIs('settings.bidSettings') ? 'active' : '' }}" href="{{ route('settings.bidSettings') }}">
                                <i class="bi bi-sliders menu-icon"></i> General Settings
                            </a>
                            <a class="nav-link {{ request()->routeIs('bid-price.*') ? 'active' : '' }}" href="{{ route('bid-price.index') }}">
                                <i class="bi bi-tag-fill menu-icon"></i> Bid Price Matrix
                            </a>
                        </div>

                         <a class="nav-link {{ request()->routeIs('settings.contests*') ? 'active' : '' }}" href="{{ route('settings.contests') }}">
                        <i class="bi bi-trophy menu-icon"></i> Filter Contests
                    </a>
                        <a class="nav-link {{ request()->routeIs('settings.profile') ? 'active' : '' }}" href="{{ route('settings.profile') }}">
                            <i class="bi bi-person-fill menu-icon"></i> Freelancer Profiles
                        </a>
                    @endif

                    <div class="nav-divider"></div>

                    {{-- Account Settings Section --}}
                    <div class="sidebar-heading">Account</div>
                   
                    
                    <a class="nav-link {{ request()->routeIs('password.change') ? 'active' : '' }}" href="{{ route('password.change') }}">
                        <i class="bi bi-key menu-icon"></i> Change Password
                    </a>
                </nav>
            </div>
        </div>

        <!-- Right Content Section -->
        <div class="col-xl-10 col-lg-9 col-md-8">
            @yield('settings_content')
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('#settingsSidebarNav .nav-link:not([data-bs-toggle="collapse"])');

    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            navLinks.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>
@endsection