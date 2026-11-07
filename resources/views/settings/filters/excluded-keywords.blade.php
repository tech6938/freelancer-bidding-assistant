@extends('layouts.settings')

@section('settings_content')
<style>
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 20px;
    }
    .keyword-pill {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
        font-size: 12px;
        font-weight: 500;
        padding: 3px 10px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 3px 2px;
    }
    .keyword-pill .remove-icon {
        cursor: pointer;
        color: #ef4444;
        font-weight: bold;
    }
    .keyword-pill .remove-icon:hover {
        color: #7f1d1d;
    }
    .preset-btn {
        font-size: 11.5px;
        padding: 2px 9px;
        border-radius: 4px;
        border: 1px dashed #cbd5e1;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
    }
    .preset-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .btn-save-custom {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        font-size: 13px;
        font-weight: 500;
        padding: 6px 20px;
        border-radius: 4px;
    }
    .btn-save-custom:hover {
        background-color: #1d4ed8;
        color: #ffffff;
    }
</style>

<div class="filter-card shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
            <h5 class="fw-semibold text-primary mb-0" style="color: #2563eb !important;">Excluded Keywords</h5>
            <p class="text-muted small mb-0">Projects containing these words in their title or description will be filtered out.</p>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllKeywords()">Clear All</button>
    </div>

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

    <form action="{{ route('settings.filters.excludedKeywords.update') }}" method="POST" id="keywordsForm">
        @csrf

        <!-- Add Input -->
        <div class="mb-2">
            <label class="form-label small fw-semibold">Add Excluded Keyword</label>
            <div class="input-group input-group-sm" style="max-width: 450px;">
                <input type="text" id="keywordInputField" class="form-control" placeholder="Type keyword (e.g. deposit, essay, telegram)...">
                <button type="button" class="btn btn-outline-danger fw-medium" onclick="addKeywordFromInput()">+ Add</button>
            </div>
        </div>

        <!-- Preset Suggestions -->
        <div class="d-flex align-items-center flex-wrap gap-1 mb-3">
            <span class="text-muted small" style="font-size: 11px;">Quick presets:</span>
            <button type="button" class="preset-btn" onclick="addTag('telegram')">+ Telegram</button>
            <button type="button" class="preset-btn" onclick="addTag('whatsapp')">+ WhatsApp</button>
            <button type="button" class="preset-btn" onclick="addTag('deposit fee')">+ Deposit Fee</button>
            <button type="button" class="preset-btn" onclick="addTag('academic writing')">+ Academic Writing</button>
            <button type="button" class="preset-btn" onclick="addTag('homework')">+ Homework</button>
            <button type="button" class="preset-btn" onclick="addTag('contact outside')">+ Contact Outside</button>
        </div>

        <!-- Tags Display Box -->
        <div class="border rounded p-3 bg-light" style="min-height: 120px;" id="keywordsBox">
            @foreach($excludedKeywords as $kw)
                <span class="keyword-pill">
                    {{ $kw }}
                    <span class="remove-icon" onclick="removeTag(this)">×</span>
                </span>
            @endforeach
        </div>

        <!-- Hidden Input for JSON -->
        <input type="hidden" name="excluded_keywords" id="excludedKeywordsHidden">

        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
            <button type="submit" class="btn btn-save-custom">Save Excluded Keywords</button>
        </div>
    </form>
</div>

<script>
    function addKeywordFromInput() {
        const input = document.getElementById('keywordInputField');
        const val = input.value.trim().toLowerCase();
        if (val.length > 1) {
            addTag(val);
            input.value = '';
        }
    }

    function addTag(text) {
        const lower = text.trim().toLowerCase();
        const box = document.getElementById('keywordsBox');
        const existing = Array.from(box.querySelectorAll('.keyword-pill')).map(el => el.innerText.replace('×', '').trim().toLowerCase());

        if (!existing.includes(lower)) {
            const span = document.createElement('span');
            span.className = 'keyword-pill';
            span.innerHTML = `${lower} <span class="remove-icon" onclick="removeTag(this)">×</span>`;
            box.appendChild(span);
            syncJson();
        }
    }

    function removeTag(el) {
        el.parentElement.remove();
        syncJson();
    }

    function clearAllKeywords() {
        document.getElementById('keywordsBox').innerHTML = '';
        syncJson();
    }

    function syncJson() {
        const box = document.getElementById('keywordsBox');
        const tags = Array.from(box.querySelectorAll('.keyword-pill')).map(el => el.innerText.replace('×', '').trim().toLowerCase());
        document.getElementById('excludedKeywordsHidden').value = JSON.stringify(tags);
    }

    document.getElementById('keywordInputField').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addKeywordFromInput();
        }
    });

    document.addEventListener('DOMContentLoaded', syncJson);
</script>
@endsection