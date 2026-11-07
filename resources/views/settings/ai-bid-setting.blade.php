@extends('layouts.settings')

@section('settings_content')
<style>
    .settings-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 24px;
    }
    .section-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-desc {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 16px;
    }
    .form-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 5px;
    }
    .form-text-help {
        font-size: 11px;
        color: #64748b;
        margin-top: 4px;
    }
    .keyword-tag {
        background-color: #fee2e2;
        color: #dc2626;
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
    .keyword-tag .remove-tag {
        cursor: pointer;
        color: #ef4444;
        font-weight: bold;
    }
    .keyword-tag .remove-tag:hover {
        color: #991b1b;
    }
    .quick-keyword-btn {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 4px;
        border: 1px dashed #cbd5e1;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
    }
    .quick-keyword-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .btn-save-settings {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        font-size: 13.5px;
        font-weight: 600;
        padding: 8px 24px;
        border-radius: 6px;
    }
    .btn-save-settings:hover {
        background-color: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff;
    }
    .status-banner {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 16px 20px;
    }
</style>

<div class="settings-container shadow-sm">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 small mb-3 border-0 shadow-sm">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h5 class="fw-bold text-dark mb-1">AI Bid Settings & Automation Rules</h5>
            <p class="text-muted small mb-0">Manage project criteria, anti-ban delay, blacklist keywords, and auto-bidding quota.</p>
        </div>
        <button type="button" class="btn btn-save-settings shadow-sm" onclick="document.getElementById('bidSettingsForm').submit();">
            <i class="bi bi-check2-circle me-1"></i> Save Changes
        </button>
    </div>

    <form id="bidSettingsForm" action="{{ route('settings.aiBidSettings.update') }}" method="POST">
        @csrf
        @method('PUT')

        @php
            $authUser = auth()->user();
            $hasActiveSub = $authUser && $authUser->hasActiveSubscription();
            $canAutobid = $authUser && $authUser->canUseAutobid();
            $aiBalance = $authUser ? $authUser->ai_bids_balance : 0;
        @endphp

        <!-- 1. Master Auto-Bid Status Banner -->
        <div class="status-banner mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-robot display-6 text-primary"></i>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold text-dark mb-0">Auto-Bidding Engine Status</h6>
                            @if($aiBalance <= 0)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11px;">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i> 0 AI Bids Balance
                                </span>
                            @elseif(!$hasActiveSub)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" style="font-size: 11px;">
                                    <i class="bi bi-exclamation-triangle me-1"></i> No Active Subscription
                                </span>
                            @endif
                        </div>
                        <p class="text-muted small mb-0 mt-1">When enabled, the automated engine checks live projects against these parameters.</p>
                    </div>
                </div>
                <div class="form-check form-switch form-switch-md">
                    <input class="form-check-input" 
                           type="checkbox" 
                           id="masterSwitch" 
                           name="is_autobid_enabled" 
                           value="1" 
                           data-balance="{{ $aiBalance }}"
                           data-has-sub="{{ $hasActiveSub ? '1' : '0' }}"
                           data-can-autobid="{{ $canAutobid ? '1' : '0' }}"
                           style="width: 2.8em; height: 1.4em; cursor: pointer;" 
                           {{ old('is_autobid_enabled', $settings->is_autobid_enabled ?? false) ? 'checked' : '' }}>
                </div>
            </div>

            @if($aiBalance <= 0)
                <div class="mt-2 pt-2 border-top border-primary-subtle text-danger small d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-info-circle-fill me-1"></i> AI Bids balance is required to enable Auto-Bidding. Current balance: <strong>0</strong></span>
                    <a href="{{ route('purchase.index') }}" class="btn btn-sm btn-outline-danger py-0 px-2 fw-medium" style="font-size: 11px;">Top-up AI Bids</a>
                </div>
            @endif
        </div>

        <div class="row g-4">
            
            <!-- LEFT COLUMN: Budget, Project Type & Bidding Strategy -->
            <div class="col-lg-6">
                
                <!-- Section: Budget & Currency -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-cash-stack text-primary"></i> Budget & Currency Limits
                    </div>
                    <div class="section-desc">Only bid on projects matching your target pricing tier.</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Minimum Budget ($)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">$</span>
                                <input type="number" 
                                       class="form-control" 
                                       name="min_budget" 
                                       value="{{ old('min_budget', $settings->min_budget ?? 30) }}" 
                                       min="1" 
                                       step="1" 
                                       required>
                            </div>
                            <div class="form-text-help">Ignore jobs below this price.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Maximum Budget ($)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white">$</span>
                                <input type="number" 
                                       class="form-control" 
                                       name="max_budget" 
                                       value="{{ old('max_budget', $settings->max_budget ?? 2500) }}" 
                                       min="1" 
                                       step="1" 
                                       required>
                            </div>
                            <div class="form-text-help">Ignore jobs above this price.</div>
                        </div>
                    </div>
                </div>

                <!-- Section: Project Type & Offer Strategy -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-sliders text-primary"></i> Project Types & Bid Pricing
                    </div>
                    <div class="section-desc">Decide how proposal pricing and timelines are generated.</div>

                    <div class="mb-3">
                        <label class="form-label d-block">Allowed Project Types</label>
                        <div class="d-flex gap-4 mt-1">
                            @php $pType = old('project_type', $settings->project_type ?? 'both'); @endphp
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="project_type" id="type_both" value="both" {{ $pType === 'both' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="type_both">Both (Fixed & Hourly)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="project_type" id="type_fixed" value="fixed" {{ $pType === 'fixed' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="type_fixed">Fixed Price Only</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="project_type" id="type_hourly" value="hourly" {{ $pType === 'hourly' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="type_hourly">Hourly Only</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Bid Price Formula</label>
                            @php $calcMode = old('bid_calculation_mode', $settings->bid_calculation_mode ?? 'average'); @endphp
                            <select class="form-select form-select-sm" name="bid_calculation_mode">
                                <option value="minimum" {{ $calcMode === 'minimum' ? 'selected' : '' }}>Client's Minimum Budget</option>
                                <option value="average" {{ $calcMode === 'average' ? 'selected' : '' }}>Average of Budget (Recommended)</option>
                                <option value="maximum" {{ $calcMode === 'maximum' ? 'selected' : '' }}>Client's Maximum Budget</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Default Delivery (Days)</label>
                            <input type="number" 
                                   class="form-control form-control-sm" 
                                   name="delivery_days" 
                                   value="{{ old('delivery_days', $settings->delivery_days ?? 7) }}" 
                                   min="1" 
                                   max="90" 
                                   required>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: Quota, Delay & Safety Filters -->
            <div class="col-lg-6">

                <!-- Section: Bidding Speed & Daily Quota -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-stopwatch text-primary"></i> Anti-Ban Delay & Daily Quota
                    </div>
                    <div class="section-desc">Add a human-like delay and limit daily automated bids.</div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Delay Before Submitting Bid:</label>
                            <span class="badge bg-primary px-2" id="delayValueBadge">{{ old('bidding_delay_seconds', $settings->bidding_delay_seconds ?? 12) }} Seconds</span>
                        </div>
                        <input type="range" 
                               class="form-range" 
                               min="3" 
                               max="120" 
                               step="1" 
                               value="{{ old('bidding_delay_seconds', $settings->bidding_delay_seconds ?? 12) }}" 
                               id="delaySlider" 
                               name="bidding_delay_seconds" 
                               oninput="updateDelayDisplay(this.value)">
                        <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                            <span>3s (Fast)</span>
                            <span>15s (Optimal)</span>
                            <span>120s (Slow)</span>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <label class="form-label">Max Daily Auto-Bids</label>
                        <div class="input-group input-group-sm">
                            <input type="number" 
                                   class="form-control" 
                                   name="max_daily_bids" 
                                   value="{{ old('max_daily_bids', $settings->max_daily_bids ?? 20) }}" 
                                   min="1" 
                                   max="200" 
                                   required>
                            <span class="input-group-text bg-white text-muted">Bids / Day</span>
                        </div>
                        <div class="form-text-help">Auto-bidding halts once this daily quota is reached.</div>
                    </div>
                </div>

                <!-- Section: Client Safety & Reputation -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-shield-check text-primary"></i> Client Trust & Reputation
                    </div>
                    <div class="section-desc">Filter out unverified accounts or low-rated employers.</div>

                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <div class="fw-semibold text-dark small">Require Verified Payment Method</div>
                            <div class="text-muted" style="font-size: 11px;">Skip employers without verified billing on Freelancer.</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="require_payment_verified" 
                                   value="1" 
                                   style="cursor: pointer;"
                                   {{ old('require_payment_verified', $settings->require_payment_verified ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Minimum Client Rating</label>
                            @php $minRating = (string) old('min_client_rating', $settings->min_client_rating ?? '4.0'); @endphp
                            <select class="form-select form-select-sm" name="min_client_rating">
                                <option value="0" {{ $minRating == '0' || $minRating == '0.0' ? 'selected' : '' }}>Any Rating (Include New)</option>
                                <option value="3.5" {{ $minRating == '3.5' ? 'selected' : '' }}>3.5 Stars & Above</option>
                                <option value="4.0" {{ $minRating == '4.0' ? 'selected' : '' }}>4.0 Stars & Above</option>
                                <option value="4.5" {{ $minRating == '4.5' ? 'selected' : '' }}>4.5 Stars & Above</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Minimum Reviews</label>
                            <input type="number" 
                                   class="form-control form-control-sm" 
                                   name="min_client_reviews" 
                                   value="{{ old('min_client_reviews', $settings->min_client_reviews ?? 0) }}" 
                                   min="0" 
                                   max="100">
                            <div class="form-text-help">Set to 0 to allow first-time job posters.</div>
                        </div>
                    </div>
                </div>

                <!-- Section: Excluded / Scam Keywords -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-ban text-danger"></i> Excluded Scam Keywords
                    </div>
                    <div class="section-desc">Projects containing these phrases in title/description are skipped.</div>

                    <div class="input-group input-group-sm mb-2">
                        <input type="text" id="keywordInput" class="form-control" placeholder="Type keyword (e.g. telegram, deposit)...">
                        <button type="button" class="btn btn-outline-danger" onclick="addKeywordTag()">+ Add</button>
                    </div>

                    <!-- Preset Suggestions -->
                    <div class="d-flex align-items-center flex-wrap gap-1 mb-2">
                        <span class="text-muted small" style="font-size: 11px;">Presets:</span>
                        <button type="button" class="quick-keyword-btn" onclick="addSpecificTag('telegram')">+ Telegram</button>
                        <button type="button" class="quick-keyword-btn" onclick="addSpecificTag('whatsapp')">+ WhatsApp</button>
                        <button type="button" class="quick-keyword-btn" onclick="addSpecificTag('academic writing')">+ Academic</button>
                        <button type="button" class="quick-keyword-btn" onclick="addSpecificTag('deposit fee')">+ Deposit</button>
                        <button type="button" class="quick-keyword-btn" onclick="addSpecificTag('typing job')">+ Typing Job</button>
                    </div>

                    <!-- Tags Display Area -->
                    <div class="border rounded p-2 bg-white" style="min-height: 75px;" id="keywordsContainer">
                        @php
                            $tags = $settings->excluded_keywords ?? ['telegram', 'whatsapp', 'academic writing', 'deposit fee', 'contact me outside'];
                            if (is_string($tags)) {
                                $tags = json_decode($tags, true) ?? [];
                            }
                        @endphp
                        @foreach($tags as $tag)
                            <span class="keyword-tag">{{ strtolower($tag) }} <span class="remove-tag" onclick="removeTag(this)">×</span></span>
                        @endforeach
                    </div>

                    <!-- Hidden JSON field -->
                    <input type="hidden" name="excluded_keywords" id="excludedKeywordsJson">
                </div>

            </div>

        </div>

        <!-- Bottom Actions -->
        <div class="d-flex justify-content-end align-items-center gap-3 mt-4 pt-3 border-top">
            <button type="reset" class="btn btn-light btn-sm text-muted">Reset Defaults</button>
            <button type="submit" class="btn btn-save-settings shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Save Changes
            </button>
        </div>

    </form>
</div>

<script>
    function updateDelayDisplay(val) {
        document.getElementById('delayValueBadge').innerText = val + ' Seconds';
    }

    function addKeywordTag() {
        const input = document.getElementById('keywordInput');
        const value = input.value.trim().toLowerCase();
        if (value.length > 1) {
            addSpecificTag(value);
            input.value = '';
        }
    }

    function addSpecificTag(tagText) {
        const container = document.getElementById('keywordsContainer');
        const lowerText = tagText.trim().toLowerCase();

        const existingTags = Array.from(container.querySelectorAll('.keyword-tag'))
            .map(el => el.innerText.replace('×', '').trim().toLowerCase());

        if (!existingTags.includes(lowerText)) {
            const span = document.createElement('span');
            span.className = 'keyword-tag';
            span.innerHTML = `${lowerText} <span class="remove-tag" onclick="removeTag(this)">×</span>`;
            container.appendChild(span);
            syncKeywordsHiddenInput();
        }
    }

    function removeTag(element) {
        element.parentElement.remove();
        syncKeywordsHiddenInput();
    }

    function syncKeywordsHiddenInput() {
        const container = document.getElementById('keywordsContainer');
        const tags = Array.from(container.querySelectorAll('.keyword-tag'))
            .map(el => el.innerText.replace('×', '').trim().toLowerCase());
        document.getElementById('excludedKeywordsJson').value = JSON.stringify(tags);
    }

    document.getElementById('keywordInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addKeywordTag();
        }
    });

    document.addEventListener('DOMContentLoaded', syncKeywordsHiddenInput);
</script>
@endsection