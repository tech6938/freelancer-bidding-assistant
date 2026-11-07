@extends('layouts.settings')

@section('settings_content')
<style>
    .templates-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 20px;
    }
    .custom-template-table {
        border-collapse: collapse;
        width: 100%;
    }
    .custom-template-table th {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        padding: 12px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
    }
    .custom-template-table td {
        font-size: 13px;
        color: #334155;
        vertical-align: top;
        padding: 14px 12px;
        border: 1px solid #e2e8f0;
        line-height: 1.6;
    }
    .cat-badge {
        font-size: 11.5px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 4px;
        display: inline-block;
    }
    .cat-greetings { background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .cat-skills    { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .cat-portfolio { background-color: #faf5ff; color: #9333ea; border: 1px solid #e9d5ff; }
    .cat-closing   { background-color: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }

    .match-skills-tag {
        background-color: #2563eb;
        color: #ffffff;
        font-size: 11px;
        font-weight: 500;
        padding: 2px 7px;
        border-radius: 4px;
        display: inline-block;
    }
    .skills-count-pill {
        background-color: #2563eb;
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 12px;
        display: inline-block;
        white-space: nowrap;
        text-decoration: none;
    }
    .add-skills-btn {
        color: #059669;
        font-size: 11.5px;
        text-decoration: none;
        display: block;
        margin-top: 5px;
        font-weight: 500;
        background: none;
        border: none;
        padding: 0;
    }
    .add-skills-btn:hover {
        text-decoration: underline;
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
    .tag-chip-btn {
        font-size: 11.5px;
        font-weight: 500;
        padding: 3px 9px;
        border-radius: 4px;
        border: 1px dashed #2563eb;
        background-color: #eff6ff;
        color: #1d4ed8;
        cursor: pointer;
    }
    .skill-set-select-btn {
        font-size: 11.5px;
        font-weight: 500;
        padding: 3px 10px;
        border-radius: 20px;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        color: #334155;
        cursor: pointer;
    }
    .skill-set-select-btn:hover, .skill-set-select-btn.active {
        background-color: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }
</style>

<div class="templates-box shadow-sm">
    @if(session('success'))
        <div class="alert alert-success py-2 small mb-3 alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger py-2 small mb-3 alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
            <h5 class="fw-semibold text-primary mb-0" style="color: #2563eb !important;">AI Project Match Templates</h5>
            <p class="text-muted small mb-0">Dynamic proposal blocks mapped to your skills.</p>
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-medium" data-bs-toggle="modal" data-bs-target="#templateModal" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Template
        </button>
    </div>

    <!-- Table Controls -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2" style="font-size: 13.5px; color: #475569;">
            <span>Show</span>
            <select class="form-select form-select-sm" style="width: 75px;">
                <option value="30" selected>30</option>
                <option value="50">50</option>
            </select>
            <span>entries</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="search" id="templateSearchInput" class="form-control form-control-sm" style="width: 220px;" placeholder="Search proposals...">
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table custom-template-table mb-0">
            <thead>
                <tr>
                    <th style="width: 14%;">Category</th>
                    <th style="width: 58%;">Content</th>
                    <th style="width: 15%;">Skills</th>
                    <th style="width: 13%; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody id="templateTableBody">
                @forelse($templates as $tmpl)
                    @php
                        $attachedSkillIds = $tmpl->skills->pluck('id')->toJson();
                        $highlightedContent = preg_replace(
                            '/\[Matching Job Skills\]/',
                            '<span class="match-skills-tag">[Matching Job Skills]</span>',
                            e($tmpl->content)
                        );
                    @endphp
                    <tr class="template-row">
                        <td>
                            <span class="cat-badge cat-{{ strtolower($tmpl->category) }}">{{ $tmpl->category }}</span>
                        </td>
                        <td>{!! nl2br($highlightedContent) !!}</td>
                        <td>
                            <span class="skills-count-pill">{{ $tmpl->skills->count() }} of {{ $totalSkillsCount }} skills</span>
                            @if($tmpl->skills->count() < $totalSkillsCount)
                                <form action="{{ route('settings.aiProjectMatch.addAllSkills', $tmpl->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="add-skills-btn">+ Add all skills</button>
                                </form>
                            @else
                                <span class="text-muted fst-italic small d-block mt-1" style="font-size: 11px;">All skills added</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-action-dropdown dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                           onclick='openEditModal(@json($tmpl), {{ $attachedSkillIds }})'>
                                            <i class="bi bi-pencil me-2 text-primary"></i> Edit
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('settings.aiProjectMatch.duplicate', $tmpl->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-copy me-2 text-secondary"></i> Duplicate
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('settings.aiProjectMatch.destroy', $tmpl->id) }}" method="POST" onsubmit="return confirm('Delete this proposal template?');">
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
                        <td colspan="4" class="text-center text-muted py-5">
                            <i class="bi bi-card-text display-6 d-block mb-2 text-secondary"></i>
                            No proposal templates found. Click <strong>"Add Template"</strong> to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ================= MODAL: ADD / EDIT TEMPLATE ================= -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="templateForm" method="POST" action="{{ route('settings.aiProjectMatch.store') }}">
                @csrf
                <div id="methodContainer"></div>

                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="modalTitle">Add Proposal Template</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Category Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category" id="modalCategory" class="form-select form-select-sm" required>
                            <option value="Greetings">Greetings (Intro Block)</option>
                            <option value="Skills">Skills (Expertise & Tech Stack)</option>
                            <option value="Portfolio">Portfolio (Live Links & Past Work)</option>
                            <option value="Closing">Closing (Call to Action / Signoff)</option>
                        </select>
                    </div>

                    <!-- Proposal Content -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Proposal Content <span class="text-danger">*</span></label>
                            <span class="text-muted small" style="font-size: 11px;">Click tags below to insert placeholders</span>
                        </div>

                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <button type="button" class="tag-chip-btn" onclick="insertTag('[Matching Job Skills]')">+ [Matching Job Skills]</button>
                            <button type="button" class="tag-chip-btn" onclick="insertTag('[Client Name]')">+ [Client Name]</button>
                            <button type="button" class="tag-chip-btn" onclick="insertTag('[Project Title]')">+ [Project Title]</button>
                        </div>

                        <textarea name="content" id="templateContentArea" rows="5" class="form-control" placeholder="Hi, I have read the project description and specialize in [Matching Job Skills]..." required></textarea>
                    </div>

                    <!-- Attached Skills Section -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small fw-semibold mb-0">Select Matching Skills</label>
                            <div class="d-flex gap-2">
                                <a href="javascript:void(0)" class="small text-decoration-none" onclick="toggleAllCheckboxes(true)">Select All</a>
                                <span class="text-muted">|</span>
                                <a href="javascript:void(0)" class="small text-decoration-none text-danger" onclick="toggleAllCheckboxes(false)">Clear All</a>
                            </div>
                        </div>

                        <!-- Skill Set Quick Selector Pills -->
                        @if($skillSets->count() > 0)
                            <div class="p-2 mb-2 bg-light border rounded d-flex align-items-center flex-wrap gap-2">
                                <span class="text-muted small fw-semibold" style="font-size: 11.5px;">Quick Select Set:</span>
                                @foreach($skillSets as $set)
                                    @php $setSkillIds = $set->skills->pluck('id')->toJson(); @endphp
                                    <button type="button" class="skill-set-select-btn" onclick="selectSkillSetByArray({{ $setSkillIds }}, this)">
                                        <i class="bi bi-layers me-1"></i> {{ $set->name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <!-- Skills Checkboxes Grid -->
                        <div class="border rounded p-3 bg-light" style="max-height: 180px; overflow-y: auto;">
                            <div class="row g-2">
                                @forelse($skills as $skill)
                                    <div class="col-md-4 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input modal-skill-check" 
                                                   type="checkbox" 
                                                   name="skills[]" 
                                                   value="{{ $skill->id }}" 
                                                   id="skill_check_{{ $skill->id }}">
                                            <label class="form-check-label small" for="skill_check_{{ $skill->id }}">{{ $skill->name }}</label>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted small py-2 text-center">
                                        No active skills found. Please sync skills first.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="modalSubmitBtn" style="background-color: #2563eb;">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function insertTag(tagText) {
        const textarea = document.getElementById('templateContentArea');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        textarea.value = textarea.value.substring(0, start) + tagText + textarea.value.substring(end);
        textarea.focus();
        textarea.selectionEnd = start + tagText.length;
    }

    function toggleAllCheckboxes(status) {
        document.querySelectorAll('.modal-skill-check').forEach(chk => chk.checked = status);
    }

    function selectSkillSetByArray(skillIdsArray, btnElement) {
        document.querySelectorAll('.skill-set-select-btn').forEach(btn => btn.classList.remove('active'));
        if (btnElement) btnElement.classList.add('active');

        document.querySelectorAll('.modal-skill-check').forEach(chk => {
            chk.checked = skillIdsArray.includes(parseInt(chk.value));
        });
    }

    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Add Proposal Template';
        document.getElementById('templateForm').action = "{{ route('settings.aiProjectMatch.store') }}";
        document.getElementById('methodContainer').innerHTML = '';
        document.getElementById('modalCategory').value = 'Greetings';
        document.getElementById('templateContentArea').value = '';
        toggleAllCheckboxes(true);
    }

    function openEditModal(template, attachedSkillIds) {
        document.getElementById('modalTitle').innerText = 'Edit Proposal Template';
        document.getElementById('templateForm').action = `/settings/ai-project-match/${template.id}`;
        document.getElementById('methodContainer').innerHTML = '@method("PUT")';
        document.getElementById('modalCategory').value = template.category;
        document.getElementById('templateContentArea').value = template.content;

        document.querySelectorAll('.modal-skill-check').forEach(chk => {
            chk.checked = attachedSkillIds.includes(parseInt(chk.value));
        });

        const modal = new bootstrap.Modal(document.getElementById('templateModal'));
        modal.show();
    }

    // Client-side search
    document.getElementById('templateSearchInput').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.template-row').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
        });
    });
</script>
@endsection