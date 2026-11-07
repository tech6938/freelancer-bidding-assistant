@extends('layouts.settings')

@section('settings_content')
<style>
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 20px;
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
            <h5 class="fw-semibold text-primary mb-0" style="color: #2563eb !important;">Language Filters</h5>
            <p class="text-muted small mb-0">Select accepted languages for project requirements and descriptions.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleAllLanguages(true)">Select All</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllLanguages(false)">Clear All</button>
        </div>
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

    <div class="mb-3">
        <input type="search" id="langSearch" class="form-control form-control-sm" placeholder="Search languages...">
    </div>

    <form action="{{ route('settings.filters.languages.update') }}" method="POST">
        @csrf
        <div class="row g-2 border rounded p-3 bg-light" style="max-height: 420px; overflow-y: auto;" id="languagesContainer">
            @forelse($allLanguages as $lang)
                <div class="col-md-3 col-sm-6 lang-item" data-name="{{ strtolower($lang['name']) }} {{ strtolower($lang['code']) }}">
                    <div class="form-check">
                        <input class="form-check-input lang-chk" 
                               type="checkbox" 
                               name="languages[]" 
                               value="{{ $lang['code'] }}" 
                               id="lang_{{ $lang['code'] }}" 
                               {{ in_array($lang['code'], $selectedLanguages) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="lang_{{ $lang['code'] }}">
                            {{ $lang['name'] }} <span class="text-muted" style="font-size: 11px;">({{ strtoupper($lang['code']) }})</span>
                        </label>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted small py-4">
                    Could not load languages from API.
                </div>
            @endforelse
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
            <button type="submit" class="btn btn-save-custom">Save Languages</button>
        </div>
    </form>
</div>

<script>
    function toggleAllLanguages(status) {
        document.querySelectorAll('.lang-chk').forEach(cb => cb.checked = status);
    }

    document.getElementById('langSearch')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        document.querySelectorAll('.lang-item').forEach(item => {
            const name = item.getAttribute('data-name');
            if (!query || name.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>
@endsection