@extends('layouts.settings')

@section('settings_content')
<style>
    .skills-wrapper {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 22px;
    }
    .skill-card-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 14px 16px;
        transition: all 0.2s ease-in-out;
    }
    .skill-card-item:hover {
        border-color: #cbd5e1;
        background: #ffffff;
        box-shadow: 0 3px 6px rgba(0,0,0,0.04);
    }
    .skill-card-item.disabled {
        opacity: 0.6;
        background: #f1f5f9;
    }
    .btn-sync-action {
        background-color: #2563eb;
        color: #ffffff;
        font-size: 13px;
        font-weight: 500;
        border: none;
    }
    .btn-sync-action:hover {
        background-color: #1d4ed8;
        color: #ffffff;
    }
    .autocomplete-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        max-height: 220px;
        overflow-y: auto;
        display: none;
    }
    .autocomplete-item {
        padding: 8px 14px;
        cursor: pointer;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
    }
    .autocomplete-item:hover {
        background-color: #eff6ff;
        color: #2563eb;
    }
</style>

<div class="skills-wrapper shadow-sm">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="alert alert-success py-2 small mb-3 alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2 small mb-3 alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h5 class="fw-bold text-dark mb-1">Freelancer Skills</h5>
            <p class="text-muted small mb-0">Manage and sync individual skill tags with your Freelancer.com profile.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-light text-dark border px-3 py-2" style="font-size: 13px;">
                <strong class="text-primary" id="activeCountDisplay">{{ $activeCount }}</strong> / <span id="totalCountDisplay">{{ $skills->count() }}</span> Skills Active
            </span>
            <form action="{{ route('settings.skills.sync') }}" method="POST" id="syncForm">
                @csrf
                <button type="submit" class="btn btn-sync-action btn-sm px-3 py-2" id="syncBtn">
                    <i class="bi bi-arrow-repeat me-1" id="syncIcon"></i> Sync from Freelancer
                </button>
            </form>
        </div>
    </div>

    <!-- Search / Filter and Add Bar -->
    <div class="row g-3 mb-4">
        <!-- Live Filter on Page -->
        <div class="col-md-6">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control" id="filterInput" placeholder="Filter skills on this page...">
            </div>
        </div>

        <!-- Add Skill via Search/API -->
        <div class="col-md-6 position-relative">
            <form action="{{ route('settings.skills.store') }}" method="POST" id="addSkillForm">
                @csrf
                <div class="input-group input-group-sm">
                    <input type="text" name="name" id="skillSearchApiInput" class="form-control" placeholder="Search official Freelancer skills to add..." autocomplete="off" required>
                    <input type="hidden" name="freelancer_job_id" id="selectedJobId">
                    <button type="submit" class="btn btn-outline-primary fw-medium px-3">
                        <i class="bi bi-plus-lg me-1"></i> Add Skill
                    </button>
                </div>
            </form>
            <!-- Dropdown autocomplete menu -->
            <div class="autocomplete-results shadow" id="apiSearchResults"></div>
        </div>
    </div>

    <!-- Skills Dynamic Grid -->
    <div class="row g-3" id="skillsGrid">
        @forelse($skills as $skill)
            <div class="col-xl-3 col-lg-4 col-md-6 skill-col" data-skill-name="{{ strtolower($skill->name) }}">
                <div class="skill-card-item d-flex justify-content-between align-items-center {{ !$skill->is_active ? 'disabled' : '' }}" id="card_{{ $skill->id }}">
                    <div>
                        <span class="fw-semibold text-dark skill-title">{{ $skill->name }}</span>
                        <div class="text-muted" style="font-size: 11px;">
                            ID: {{ $skill->freelancer_job_id ?? 'Custom' }}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input skill-switch" 
                                   type="checkbox" 
                                   role="switch" 
                                   data-id="{{ $skill->id }}" 
                                   {{ $skill->is_active ? 'checked' : '' }}>
                        </div>
                        <form action="{{ route('settings.skills.destroy', $skill->id) }}" method="POST" onsubmit="return confirm('Remove skill?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link text-danger p-0 ms-1" style="font-size: 13px;" title="Delete">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted" id="emptyNotice">
                <i class="bi bi-tags display-6 d-block mb-2 text-secondary"></i>
                No skills configured yet. Click <strong>"Sync from Freelancer"</strong> to automatically import your active skills.
            </div>
        @endforelse
    </div>

</div>

<script>
    // 1. Instant Switch Toggle via AJAX
    document.querySelectorAll('.skill-switch').forEach(switchEl => {
        switchEl.addEventListener('change', function() {
            const skillId = this.dataset.id;
            const isChecked = this.checked;
            const card = document.getElementById(`card_${skillId}`);

            fetch(`/settings/skills/${skillId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ is_active: isChecked })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('activeCountDisplay').innerText = data.activeCount;
                    if(isChecked) {
                        card.classList.remove('disabled');
                    } else {
                        card.classList.add('disabled');
                    }
                }
            })
            .catch(() => {
                this.checked = !isChecked; // Revert if failed
            });
        });
    });

    // 2. Client-side Search / Filter
    document.getElementById('filterInput').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.skill-col').forEach(item => {
            const name = item.dataset.skillName;
            if (name.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // 3. Sync button spinner
    document.getElementById('syncForm').addEventListener('submit', function() {
        const btn = document.getElementById('syncBtn');
        const icon = document.getElementById('syncIcon');
        btn.disabled = true;
        icon.classList.add('spin-icon');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Syncing...';
    });

    // 4. Official Freelancer Skills Autocomplete Search
    const searchApiInput = document.getElementById('skillSearchApiInput');
    const resultsBox = document.getElementById('apiSearchResults');
    const selectedJobIdInput = document.getElementById('selectedJobId');

    let debounceTimer;
    searchApiInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if(query.length < 2) {
            resultsBox.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/settings/skills/search-api?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    resultsBox.innerHTML = '';
                    if(data.length > 0) {
                        data.forEach(job => {
                            const div = document.createElement('div');
                            div.className = 'autocomplete-item';
                            div.innerHTML = `<strong>${job.name}</strong> <small class="text-muted">(ID: ${job.id})</small>`;
                            div.onclick = function() {
                                searchApiInput.value = job.name;
                                selectedJobIdInput.value = job.id;
                                resultsBox.style.display = 'none';
                            };
                            resultsBox.appendChild(div);
                        });
                        resultsBox.style.display = 'block';
                    } else {
                        resultsBox.style.display = 'none';
                    }
                });
        }, 300);
    });

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!searchApiInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });
</script>
@endsection