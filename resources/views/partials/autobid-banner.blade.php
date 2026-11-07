@php
    $bannerUser = auth()->user();
    $bannerAiSetting = $bannerUser ? \App\Models\AiBidSetting::where('user_id', $bannerUser->id)->first() : null;
    $isAutobidTurnedOn = $bannerAiSetting && (bool) $bannerAiSetting->is_autobid_enabled;
    $hasActiveSub = $bannerUser && $bannerUser->hasActiveSubscription();
    $canAutobid = $bannerUser && $bannerUser->canUseAutobid();
    $hasBalance = $bannerUser && $bannerUser->ai_bids_balance > 0;
    $isFullyActive = $isAutobidTurnedOn && $hasActiveSub && $canAutobid && $hasBalance;
@endphp

@if($bannerUser && !$bannerUser->isAdmin())
    @if($isFullyActive)
        {{-- 1. FULLY ACTIVE STATE (Green) --}}
        <div class="dynamic-autobid-banner bg-success text-white text-center py-2 px-3 shadow-sm d-flex justify-content-center align-items-center flex-wrap gap-2" style="font-size: 14px; font-weight: 500;">
            <span>
                <span class="spinner-grow spinner-grow-sm text-light me-1" role="status" style="width: 9px; height: 9px; vertical-align: middle;"></span>
                <strong>Bidman</strong> is bidding automatically.
            </span>
            <a href="javascript:void(0)" onclick="toggleAutobidBanner(false)" class="text-white text-decoration-underline fw-bold ms-1" style="cursor: pointer;">
                Click to disable.
            </a>
            @if(isset($showLiveFeedBadge) && $showLiveFeedBadge)
                <span class="ms-3 small opacity-75 d-none d-md-inline">● Live Feed Active</span>
            @endif
        </div>
    @elseif($isAutobidTurnedOn && (!$hasActiveSub || !$canAutobid || !$hasBalance))
        {{-- 2. ENABLED BUT BLOCKED/PAUSED STATE (Amber Warning) --}}
        <div class="dynamic-autobid-banner bg-warning text-dark text-center py-2 px-3 shadow-sm d-flex justify-content-center align-items-center flex-wrap gap-2" style="font-size: 14px; font-weight: 500; background-color: #f59e0b !important; color: #1e293b !important;">
            <span>
                <i class="bi bi-exclamation-triangle-fill me-1 text-dark"></i>
                Auto-Bidding is paused 
                @if(!$hasActiveSub || !$canAutobid)
                    (Active subscription required).
                @elseif(!$hasBalance)
                    (AI Bids balance is 0).
                @endif
            </span>
            <a href="{{ route('purchase.index') }}" class="text-dark text-decoration-underline fw-bold ms-1">
                Top-up AI Bids / Upgrade Plan
            </a>
        </div>
    @else
        {{-- 3. PAUSED / DISABLED STATE (Slate Grey) --}}
        <div class="dynamic-autobid-banner text-white text-center py-2 px-3 shadow-sm d-flex justify-content-center align-items-center flex-wrap gap-2" style="font-size: 14px; font-weight: 500; background-color: #475569;">
            <span>
                <i class="bi bi-pause-circle me-1 opacity-75"></i>
                Auto-Bidding engine is currently disabled.
            </span>
            <a href="javascript:void(0)" onclick="toggleAutobidBanner(true)" class="text-white text-decoration-underline fw-bold ms-1" style="cursor: pointer;">
                Click to enable.
            </a>
        </div>
    @endif

    <script>
    if (typeof window.toggleAutobidBanner === 'undefined') {
        window.toggleAutobidBanner = function(targetState) {
            fetch("{{ route('settings.aiBidSettings.toggleStatus') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ state: targetState })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Action failed.');
                    if (data.message && data.message.includes('0')) {
                        window.location.href = "{{ route('purchase.index') }}";
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred while updating auto-bidding status.');
            });
        };
    }
    </script>
@endif
