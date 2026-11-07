@extends('layouts.settings')

@section('settings_content')
<div class="container-fluid py-3 px-2 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">Find Contests <span class="text-muted fw-normal fs-6">- Discover exciting creative challenges.</span></h4>
            <p class="text-muted small mb-0">Browse real-time active contests from Freelancer.com</p>
        </div>
        @if(!$connection)
            <a href="{{ route('freelancer.connect') }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-xs">
                <i class="bi bi-link-45deg me-1"></i> Connect Freelancer Account
            </a>
        @else
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-check-circle-fill me-1"></i> Connected to Freelancer
            </span>
        @endif
    </div>

    @if(isset($errorMessage) && $errorMessage)
        <div class="alert alert-warning border-0 shadow-sm rounded-3 py-2 px-3 mb-4 small d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
            <div>{{ $errorMessage }}</div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Sidebar Filters Column -->
        <aside class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 sticky-top filters-card" style="top: 20px; z-index: 10;">
                <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-funnel me-1 text-primary"></i> Filters</h6>
                        <a href="{{ route('settings.contests') }}" class="text-decoration-none small text-muted">Reset All</a>
                    </div>
                </div>

                <div class="card-body p-4 filters-scrollable-body">
                    <form method="GET" action="{{ route('settings.contests') }}" id="contestFilterForm">
                        <!-- Search Bar -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Search Keywords</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="query" value="{{ $searchQuery }}" class="form-control border-start-0 ps-0" placeholder="e.g. logo design, mobile app, figma">
                            </div>
                        </div>

                        <!-- Budget Range -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Budget Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" name="min_prize" value="{{ $minPrize }}" class="form-control" placeholder="Min Prize">
                                        <span class="input-group-text bg-light text-muted">$</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" name="max_prize" value="{{ $maxPrize }}" class="form-control" placeholder="Max Prize">
                                        <span class="input-group-text bg-light text-muted">$</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contest Upgrades -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Contest Upgrades</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="form-check small mb-1">
                                        <input class="form-check-input" type="checkbox" name="upgrades[]" value="guaranteed" id="chkGuaranteed" {{ in_array('guaranteed', array_map('strtolower', $selectedUpgrades)) ? 'checked' : '' }}>
                                        <label class="form-check-label text-secondary" for="chkGuaranteed">Guaranteed</label>
                                    </div>
                                    <div class="form-check small mb-1">
                                        <input class="form-check-input" type="checkbox" name="upgrades[]" value="sealed" id="chkSealed" {{ in_array('sealed', array_map('strtolower', $selectedUpgrades)) ? 'checked' : '' }}>
                                        <label class="form-check-label text-secondary" for="chkSealed">Sealed</label>
                                    </div>
                                    <div class="form-check small mb-1">
                                        <input class="form-check-input" type="checkbox" name="upgrades[]" value="featured" id="chkFeatured" {{ in_array('featured', array_map('strtolower', $selectedUpgrades)) ? 'checked' : '' }}>
                                        <label class="form-check-label text-secondary" for="chkFeatured">Featured</label>
                                    </div>
                                    <div class="form-check small">
                                        <input class="form-check-input" type="checkbox" name="upgrades[]" value="highlight" id="chkHighlight" {{ in_array('highlight', array_map('strtolower', $selectedUpgrades)) ? 'checked' : '' }}>
                                        <label class="form-check-label text-secondary" for="chkHighlight">Highlight</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check small mb-1">
                                        <input class="form-check-input" type="checkbox" name="upgrades[]" value="urgent" id="chkUrgent" {{ in_array('urgent', array_map('strtolower', $selectedUpgrades)) ? 'checked' : '' }}>
                                        <label class="form-check-label text-secondary" for="chkUrgent">Urgent</label>
                                    </div>
                                    <div class="form-check small mb-1">
                                        <input class="form-check-input" type="checkbox" name="upgrades[]" value="topcontest" id="chkTopContest" {{ in_array('topcontest', array_map('strtolower', $selectedUpgrades)) ? 'checked' : '' }}>
                                        <label class="form-check-label text-secondary" for="chkTopContest">Top Contest</label>
                                    </div>
                                    <div class="form-check small mb-1">
                                        <input class="form-check-input" type="checkbox" name="upgrades[]" value="NDA" id="chkNDA" {{ in_array('nda', array_map('strtolower', $selectedUpgrades)) ? 'checked' : '' }}>
                                        <label class="form-check-label text-secondary" for="chkNDA">NDA</label>
                                    </div>
                                    <div class="form-check small">
                                        <input class="form-check-input" type="checkbox" name="upgrades[]" value="nonpublic" id="chkNonPublic" {{ in_array('nonpublic', array_map('strtolower', $selectedUpgrades)) ? 'checked' : '' }}>
                                        <label class="form-check-label text-secondary" for="chkNonPublic">Non-Public</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Global Required Skills Autocomplete Search -->
                        <div class="mb-3 position-relative">
                            <label class="form-label small fw-semibold text-secondary mb-1 d-flex justify-content-between align-items-center">
                                <span>Required Skills (Global Search)</span>
                                <span class="text-muted fw-normal" style="font-size: 0.72rem;">Any Freelancer skill</span>
                            </label>
                            
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-tag"></i></span>
                                <input type="text" id="globalSkillInput" class="form-control" placeholder="Search & add skill (e.g. PHP, Logo Design, 3D)..." autocomplete="off">
                                <div id="skillSpinner" class="spinner-border spinner-border-sm text-primary position-absolute end-0 top-50 translate-middle-y me-3 d-none" role="status"></div>
                            </div>

                            <!-- Autocomplete Dropdown List -->
                            <div id="skillSuggestionsDropdown" class="autocomplete-dropdown shadow border rounded-3 bg-white d-none"></div>

                            <!-- Selected Skill Badges Container -->
                            <div id="selectedSkillsContainer" class="border rounded-3 p-2 bg-light d-flex flex-wrap gap-1 align-items-center min-vh-25">
                                @forelse($selectedSkillItems as $item)
                                    <span class="badge bg-primary text-white py-1 px-2 d-inline-flex align-items-center gap-1 skill-chip">
                                        <span>{{ $item['name'] }}</span>
                                        <input type="hidden" name="skills[]" value="{{ $item['id'] }}">
                                        <i class="bi bi-x ms-1 cursor-pointer fs-7" onclick="this.closest('.skill-chip').remove();"></i>
                                    </span>
                                @empty
                                    <span id="noSkillsPlaceholder" class="text-muted small px-1 py-1" style="font-size: 0.78rem;">
                                        No skills added. Type above to search any skill.
                                    </span>
                                @endforelse
                            </div>
                        </div>

                        <!-- Sort By Pills & Options -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Sort By</label>
                            <div class="row g-1 mb-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="sort_field" value="time_updated" id="sort_time_updated" {{ $sortField == 'time_updated' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary btn-sm w-100 py-1 text-truncate" for="sort_time_updated">
                                        <i class="bi bi-clock me-1"></i> Recently Updated
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="sort_field" value="contest_enddate" id="sort_enddate" {{ $sortField == 'contest_enddate' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary btn-sm w-100 py-1 text-truncate" for="sort_enddate">
                                        <i class="bi bi-hourglass-split me-1"></i> Ending Date
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="sort_field" value="contest_prize_usd" id="sort_prize_usd" {{ $sortField == 'contest_prize_usd' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary btn-sm w-100 py-1 text-truncate" for="sort_prize_usd">
                                        <i class="bi bi-currency-dollar me-1"></i> Prize (USD)
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="sort_field" value="entry_count" id="sort_entries" {{ $sortField == 'entry_count' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary btn-sm w-100 py-1 text-truncate" for="sort_entries">
                                        <i class="bi bi-people me-1"></i> Entry Count
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Projections / Details Switches -->
                        <div class="mb-4 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-check-label small text-secondary" for="swReverse">Reverse Sort Order</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" name="reverse_sort" value="1" id="swReverse" {{ $reverseSort ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-check-label small text-secondary" for="swUser">Show User Details</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" name="show_user" value="1" id="swUser" {{ $showUser ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-check-label small text-secondary" for="swEntries">Show Entry Counts</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" name="show_entries" value="1" id="swEntries" {{ $showEntries ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Contests Listing Column -->
        <main class="col-xl-8 col-lg-7">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">List of Active Contests</h6>
                @if($paginator->total() > 0)
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-normal">
                        Showing {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} contests
                    </span>
                @endif
            </div>

            <!-- Contest Cards List -->
            @forelse($paginator as $contest)
                <div class="card border-0 shadow-sm rounded-4 mb-3 contest-card transition-all">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h5 class="fw-bold mb-0">
                                <a href="{{ $contest['contest_url'] }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark hover-primary">
                                    {{ $contest['title'] }}
                                    <i class="bi bi-box-arrow-up-right ms-1 text-muted fs-6" style="font-size: 0.8rem;"></i>
                                </a>
                            </h5>
                        </div>

                        <!-- Meta Info Row (Holder, Prize, Time Left) -->
                        <div class="row g-3 py-2 my-3 bg-light rounded-3 px-2 mx-0 align-items-center">
                            @if($showUser)
                                <div class="col-sm-4 d-flex align-items-center gap-2">
                                    <img src="{{ $contest['avatar'] }}" 
                                         alt="Avatar" 
                                         class="rounded-circle object-fit-cover shadow-xs border" 
                                         width="38" 
                                         height="38"
                                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($contest['holder_name']) }}&background=0d6efd&color=fff'">
                                    <div class="lh-sm text-truncate">
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">Contest Holder</small>
                                        <span class="fw-semibold text-dark small text-truncate d-inline-block" style="max-width: 140px;">
                                            {{ $contest['holder_name'] }}
                                        </span>
                                        @if(!empty($contest['country']))
                                            <span class="text-muted small d-block" style="font-size: 0.7rem;">
                                                <i class="bi bi-geo-alt"></i> {{ $contest['country'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="col-sm-4">
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">Contest ID</small>
                                    <span class="fw-semibold text-dark small">#{{ $contest['id'] }}</span>
                                </div>
                            @endif

                            <div class="col-sm-4 text-sm-center border-start border-end border-light-subtle">
                                <small class="text-muted d-block" style="font-size: 0.72rem;">Prize Amount</small>
                                <span class="fw-bold text-success fs-6">
                                    {{ $contest['currency_sign'] }}{{ $contest['prize'] }} <small class="text-muted fw-normal fs-7">{{ $contest['currency'] }}</small>
                                </span>
                            </div>

                            <div class="col-sm-4 text-sm-end">
                                <small class="text-muted d-block" style="font-size: 0.72rem;">Time Remaining</small>
                                <span class="fw-semibold {{ str_contains($contest['time_left'], 'Ended') ? 'text-danger' : 'text-warning-emphasis' }} small">
                                    <i class="bi bi-clock-history me-1"></i>{{ $contest['time_left'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="card-text text-secondary small mb-3 lh-base">
                            {{ Str::limit($contest['description'], 280) }}
                        </p>

                        <!-- Badges & Action Section -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top">
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <!-- Entries Badge -->
                                @if($showEntries)
                                    <span class="badge bg-light text-dark border py-1 px-2 me-1">
                                        <i class="bi bi-people me-1 text-muted"></i>{{ $contest['entries_count'] }} Entries
                                    </span>
                                @endif

                                <!-- Upgrades Badges -->
                                @foreach($contest['upgrades'] as $upgrade)
                                    @php
                                        $badgeColor = match($upgrade) {
                                            'Guaranteed' => 'success',
                                            'Featured' => 'primary',
                                            'Urgent' => 'danger',
                                            'Top Contest' => 'warning',
                                            'NDA' => 'dark',
                                            default => 'info'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle py-1 px-2">
                                        {{ $upgrade }}
                                    </span>
                                @endforeach

                                <!-- Skill Badges -->
                                @foreach($contest['skills'] as $skill)
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis py-1 px-2 cursor-pointer" onclick="addSkillQuick('{{ $skill['id'] ?? '' }}', '{{ addslashes($skill['name'] ?? $skill) }}')">
                                        {{ $skill['name'] ?? $skill }}
                                    </span>
                                @endforeach
                            </div>

                            <a href="{{ $contest['contest_url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-sm px-3 rounded-pill fw-medium">
                                <i class="bi bi-arrow-up-right me-1"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <div class="my-3">
                        <i class="bi bi-trophy text-muted" style="font-size: 3.5rem;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Active Contests Found</h5>
                    <p class="text-muted small mb-4">
                        @if(!$connection)
                            Please connect your Freelancer.com account to load live contests from Freelancer API.
                        @else
                            Try adjusting your search query, budget range, or skill filters to find more active contests.
                        @endif
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        @if(!$connection)
                            <a href="{{ route('freelancer.connect') }}" class="btn btn-primary px-4 py-2 rounded-pill fw-medium">
                                <i class="bi bi-link-45deg me-1"></i> Connect Freelancer Account
                            </a>
                        @else
                            <a href="{{ route('settings.contests') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-medium">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                            </a>
                        @endif
                    </div>
                </div>
            @endforelse

            <!-- Dynamic Pagination -->
            @if($paginator->hasPages())
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 pt-2 gap-2">
                    <small class="text-muted">
                        Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
                    </small>
                    <div class="custom-pagination">
                        {{ $paginator->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </main>
    </div>
</div>

<style>
.contest-card {
    transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
.contest-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.08) !important;
}
.hover-primary:hover {
    color: #0d6efd !important;
}
.cursor-pointer {
    cursor: pointer;
}
.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    max-height: 220px;
    overflow-y: auto;
}
.autocomplete-item {
    padding: 8px 12px;
    font-size: 13px;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
}
.autocomplete-item:hover {
    background-color: #f0f7ff;
    color: #0d6efd;
}
.custom-pagination .pagination {
    margin-bottom: 0;
}
.custom-pagination .page-item .page-link {
    font-size: 0.85rem;
    padding: 0.35rem 0.75rem;
    color: #495057;
}
.custom-pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}
.filters-card {
    max-height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
}
.filters-scrollable-body {
    overflow-y: auto;
    max-height: calc(100vh - 110px);
    padding-right: 14px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f8fafc;
}
.filters-scrollable-body::-webkit-scrollbar {
    width: 6px;
}
.filters-scrollable-body::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 6px;
}
.filters-scrollable-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 6px;
}
.filters-scrollable-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('globalSkillInput');
    const dropdown = document.getElementById('skillSuggestionsDropdown');
    const spinner = document.getElementById('skillSpinner');
    const container = document.getElementById('selectedSkillsContainer');
    const placeholder = document.getElementById('noSkillsPlaceholder');

    let debounceTimer = null;
    const skillSearchCache = {};

    if (input) {
        input.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(debounceTimer);

            if (query.length < 2) {
                dropdown.classList.add('d-none');
                dropdown.innerHTML = '';
                return;
            }

            // If already in client cache, show instantly (0ms)
            if (skillSearchCache[query.toLowerCase()]) {
                renderSkillDropdown(skillSearchCache[query.toLowerCase()], query);
                return;
            }

            if (spinner) spinner.classList.remove('d-none');

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('settings.skills.searchApi') }}?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (spinner) spinner.classList.add('d-none');
                        skillSearchCache[query.toLowerCase()] = data || [];
                        renderSkillDropdown(data || [], query);
                    })
                    .catch(() => {
                        if (spinner) spinner.classList.add('d-none');
                    });
            }, 300);
        });

        function renderSkillDropdown(data, query) {
            if (data && data.length > 0) {
                dropdown.innerHTML = data.map(item => `
                    <div class="autocomplete-item d-flex justify-content-between align-items-center" onclick="addSkillBadge(${item.id}, '${item.name.replace(/'/g, "\\'")}')">
                        <span>${item.name}</span>
                        <small class="text-muted">${item.category ? item.category.name : ''}</small>
                    </div>
                `).join('');
                dropdown.classList.remove('d-none');
            } else {
                dropdown.innerHTML = `<div class="autocomplete-item text-muted" onclick="addSkillBadge('${query.replace(/'/g, "\\'")}', '${query.replace(/'/g, "\\'")}')">Add "${query}"</div>`;
                dropdown.classList.remove('d-none');
            }
        }

        // Hide dropdown on outside click
        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('d-none');
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = input.value.trim();
                if (val) {
                    addSkillBadge(val, val);
                }
            }
        });
    }

    window.addSkillBadge = function (id, name) {
        if (placeholder) placeholder.remove();

        // Check if already added
        const existing = container.querySelector(`input[name="skills[]"][value="${id}"]`);
        if (existing) {
            dropdown.classList.add('d-none');
            input.value = '';
            return;
        }

        const badge = document.createElement('span');
        badge.className = 'badge bg-primary text-white py-1 px-2 d-inline-flex align-items-center gap-1 skill-chip';
        badge.innerHTML = `
            <span>${name}</span>
            <input type="hidden" name="skills[]" value="${id}">
            <i class="bi bi-x ms-1 cursor-pointer fs-7" onclick="this.closest('.skill-chip').remove();"></i>
        `;

        container.appendChild(badge);
        dropdown.classList.add('d-none');
        input.value = '';
    };

    window.addSkillQuick = function(id, name) {
        addSkillBadge(id || name, name);
    };
});
</script>
@endsection