@extends('layouts.settings')

@section('settings_content')
<style>
    .skill-sets-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 16px 20px;
    }
    .custom-table {
        border-collapse: collapse;
        width: 100%;
    }
    .custom-table th {
        font-size: 13.5px;
        font-weight: 600;
        color: #334155;
        padding: 12px;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
    }
    .custom-table td {
        font-size: 13px;
        color: #334155;
        vertical-align: middle;
        padding: 12px;
        border: 1px solid #e2e8f0;
    }
    .skill-chip {
        background-color: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-size: 11.5px;
        font-weight: 500;
        padding: 2px 8px;
        border-radius: 12px;
        display: inline-block;
        margin: 2px;
    }
    .btn-action-dropdown {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 500;
        padding: 4px 14px;
        border-radius: 4px;
    }
    .btn-action-dropdown:hover {
        background-color: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff;
    }
</style>

<div class="skill-sets-box shadow-sm">
    
    <!-- Flash Messages -->
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
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <h5 class="fw-semibold text-primary mb-0" style="color: #3b82f6 !important;">Skill Sets</h5>
        <a href="javascript:void(0)" class="text-decoration-none fw-semibold" style="color: #3b82f6; font-size: 13.5px;" data-bs-toggle="modal" data-bs-target="#addSkillSetModal">
            + Add New Skill Set
        </a>
    </div>

    <!-- Table Controls -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2" style="font-size: 13.5px; color: #475569;">
            <span>Total Sets:</span>
            <span class="badge bg-light text-primary border fw-bold">{{ $skillSets->count() }}</span>
        </div>
        <div class="d-flex align-items-center gap-2" style="font-size: 13.5px; color: #475569;">
            <span>Search:</span>
            <input type="search" id="setSearchInput" class="form-control form-control-sm" style="width: 220px;" placeholder="Search skill set or skill...">
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table custom-table mb-0" id="skillSetsTable">
            <thead>
                <tr>
                    <th style="width: 25%;">Set Name</th>
                    <th style="width: 50%;">Included Skills</th>
                    <th style="width: 12%; text-align: center;">Total Skills</th>
                    <th style="width: 13%; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($skillSets as $set)
                    @php
                        $setSkillIds = $set->skills->pluck('id')->toJson();
                    @endphp
                    <tr class="set-row">
                        <td class="fw-semibold text-dark set-name">{{ $set->name }}</td>
                        <td class="set-skills-list">
                            @forelse($set->skills as $skill)
                                <span class="skill-chip">{{ $skill->name }}</span>
                            @empty
                                <span class="text-muted fst-italic small">No skills attached</span>
                            @endforelse
                        </td>
                        <td class="text-center fw-medium">{{ $set->skills->count() }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-action-dropdown dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">
                                    <!-- Edit Button -->
                                    <li>
                                        <a class="dropdown-item edit-set-btn" 
                                           href="javascript:void(0)"
                                           data-id="{{ $set->id }}"
                                           data-name="{{ $set->name }}"
                                           data-skills="{{ $setSkillIds }}">
                                            <i class="bi bi-pencil me-2 text-primary"></i> Edit
                                        </a>
                                    </li>

                                    <!-- Duplicate Form -->
                                    <li>
                                        <form action="{{ route('settings.skillSets.duplicate', $set->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-copy me-2 text-secondary"></i> Duplicate
                                            </button>
                                        </form>
                                    </li>

                                    <li><hr class="dropdown-divider"></li>

                                    <!-- Delete Form -->
                                    <li>
                                        <form action="{{ route('settings.skillSets.destroy', $set->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Skill Set?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="bi bi-collection display-6 d-block mb-2 text-secondary"></i>
                            No Skill Sets found. Click <strong>"+ Add New Skill Set"</strong> to create your first bundle.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<!-- Modal: Add New Skill Set -->
<div class="modal fade" id="addSkillSetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('settings.skillSets.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Create New Skill Set Bundle</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Skill Set Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Mobile App Dev, Full-Stack PHP" required>
                    </div>
                    
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Select Included Skills <span class="text-danger">*</span></label>
                            <span class="text-muted small" style="font-size: 11.5px;">Check skills belonging to this group</span>
                        </div>
                        <div class="border rounded p-3 bg-light" style="max-height: 220px; overflow-y: auto;">
                            <div class="row g-2">
                                @forelse($availableSkills as $availSkill)
                                    <div class="col-md-4 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="skills[]" value="{{ $availSkill->id }}" id="add_skill_{{ $availSkill->id }}">
                                            <label class="form-check-label small" for="add_skill_{{ $availSkill->id }}">
                                                {{ $availSkill->name }}
                                            </label>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center text-muted small py-2">
                                        No active skills found. Please sync or add skills in the <a href="{{ route('settings.skills') }}">Skills tab</a> first.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" style="background-color: #2563eb;">Save Skill Set</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Skill Set -->
<div class="modal fade" id="editSkillSetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editSkillSetForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Edit Skill Set Bundle</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Skill Set Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editSetNameInput" class="form-control" required>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Select Included Skills <span class="text-danger">*</span></label>
                        <div class="border rounded p-3 bg-light" style="max-height: 220px; overflow-y: auto;">
                            <div class="row g-2">
                                @foreach($availableSkills as $availSkill)
                                    <div class="col-md-4 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input edit-skill-checkbox" type="checkbox" name="skills[]" value="{{ $availSkill->id }}" id="edit_skill_{{ $availSkill->id }}">
                                            <label class="form-check-label small" for="edit_skill_{{ $availSkill->id }}">
                                                {{ $availSkill->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" style="background-color: #2563eb;">Update Skill Set</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // 1. Live Client-Side Table Search
    document.getElementById('setSearchInput').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.set-row').forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // 2. Open & Populate Edit Modal
    document.querySelectorAll('.edit-set-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const setId = this.dataset.id;
            const setName = this.dataset.name;
            const setSkills = JSON.parse(this.dataset.skills || '[]');

            // Set Form action dynamically
            const form = document.getElementById('editSkillSetForm');
            form.action = `/settings/skill-sets/${setId}`;

            // Populate Set Name
            document.getElementById('editSetNameInput').value = setName;

            // Reset and Check checkboxes for attached skills
            document.querySelectorAll('.edit-skill-checkbox').forEach(chk => {
                chk.checked = setSkills.includes(parseInt(chk.value));
            });

            // Show bootstrap modal
            const editModal = new bootstrap.Modal(document.getElementById('editSkillSetModal'));
            editModal.show();
        });
    });
</script>
@endsection