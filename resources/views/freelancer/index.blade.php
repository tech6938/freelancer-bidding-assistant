@extends('layouts.app')

@section('content')

{{-- Case 1: If Not Connected --}}
@if(!$connection)
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4 text-center">
                        <h3 class="mb-2 fw-bold text-dark">Freelancer Integration</h3>
                        <p class="text-muted small mb-4">
                            Connect your Freelancer.com account to manage projects, auto-bids, and Freelancer features from this application.
                        </p>

                        @if(session('success'))
                            <div class="alert alert-success py-2 text-start small">{{ session('success') }}</div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger py-2 text-start small">{{ session('error') }}</div>
                        @endif

                        <div class="py-3">
                            <div class="mb-3">
                                <i class="bi bi-link-45deg text-primary" style="font-size: 55px;"></i>
                            </div>
                            <h5 class="fw-semibold">Connect your Freelancer account</h5>
                            <p class="text-muted small">You will be redirected to Freelancer.com to authorize access.</p>
                            
                            <a href="{{ route('freelancer.connect') }}" class="btn btn-primary px-4 py-2 mt-2 fw-medium">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Connect Freelancer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{-- Case 2: If Connected (Dynamic Bidding Dashboard) --}}
@else
    <style>
        .status-banner { background-color: #22c55e; color: #ffffff; font-weight: 500; font-size: 15px; }
        .info-bar { background-color: #3b4256; color: #ffffff; font-size: 14px; }
        .skills-banner { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f; font-size: 14px; font-weight: 500; }
        .project-card { border-left: 4px solid transparent; transition: all 0.2s ease; }
        .project-card:hover { border-left: 4px solid #0284c7; }
        .meta-text { font-size: 12.5px; color: #64748b; }
        .skill-tag { font-size: 11.5px; padding: 2px 8px; border-radius: 3px; display: inline-block; margin: 1px 2px; text-decoration: none; }
        .skill-tag.matched { background-color: #d1fae5; color: #065f46; font-weight: 600; border: 1px solid #6ee7b7; }
        .skill-tag.unmatched { background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
        .skill-tag:hover { opacity: 0.85; }
        .skill-match-badge { font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
        .skill-match-badge.high { background-color: #d1fae5; color: #065f46; }
        .skill-match-badge.medium { background-color: #fef3c7; color: #92400e; }
        .skill-match-badge.low { background-color: #fee2e2; color: #991b1b; }
        .badge-icon-item { font-size: 12px; color: #10b981; font-weight: 500; }
        .badge-icon-item.gray-out { color: #ef4444; }
        .btn-green { background-color: #10b981; border-color: #10b981; color: #fff; font-weight: 500; }
        .btn-green:hover { background-color: #059669; color: #fff; }
        /* Live Feed Styles */
        .live-dot { display: inline-block; width: 8px; height: 8px; background: #fff; border-radius: 50%; animation: livePulse 1.5s infinite; }
        @keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .project-card.new-project { border-left: 4px solid #22c55e !important; }
        .live-feed-enter { opacity: 0; transform: translateY(-15px); }
        .live-feed-enter-active { opacity: 1; transform: translateY(0); transition: all 0.4s ease; }
        @keyframes fadeNewBadge { 0% { opacity: 1; } 80% { opacity: 1; } 100% { opacity: 0; display: none; } }
        .new-project-toast {
             position: fixed; 
             bottom: 20px; /* top: 80px ko bottom: 20px se replace kiya */
    left: 20px;
             z-index: 9999; 
             background: #065f46; 
             color: #fff;
              padding: 8px 16px;
               border-radius: 8px; 
              font-size: 13px; 
              font-weight: 600;
               box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
               animation: slideIn 0.3s ease; }
        @keyframes slideInLeft { 
    from { transform: translateX(-100%); opacity: 0; } 
    to { transform: translateX(0); opacity: 1; } 
}
    </style>

    <div class="container-fluid px-0">

        <!-- Dynamic Top Notification Banner -->
        @include('partials.autobid-banner', ['showLiveFeedBadge' => true])

        <!-- Sub-banner -->
        <div class="info-bar text-center py-2 mb-3">
            Not sure why freelancer's project didn't get picked on Bidman? 
            <button class="btn btn-sm btn-light py-0 px-2 ms-2 fw-semibold" style="font-size: 12px;">See Why</button>
        </div>

        <div class="container py-2">
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2 small mb-3">
                    {{ session('success') }}
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-2 small mb-3">
                    {{ session('error') }}
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- No Skills Banner --}}
            @if(empty($hasSkills))
                <div class="skills-banner text-center py-2 rounded-2 mb-3 shadow-sm">
                    <i class="bi bi-lightbulb-fill me-1"></i>
                    Showing all active projects. <strong>Set your skills</strong> to see personalized matches!
                    <a href="{{ route('settings.skills') }}" class="btn btn-sm btn-dark py-0 px-3 ms-2 fw-semibold" style="font-size: 12px;">Set Skills</a>
                </div>
            @endif

            <!-- Filter Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-secondary" role="button">
                        Filter Projects <i class="bi bi-chevron-down ms-1"></i>
                    </span>
                    <div class="d-flex align-items-center gap-3">
                        @if(!empty($hasSkills))
                            <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 11px;">
                                <i class="bi bi-bullseye me-1"></i>Skill Matched
                            </span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 11px;">
                                <i class="bi bi-globe me-1"></i>All Projects
                            </span>
                        @endif
                        <span class="text-muted small">
                            Showing {{ isset($paginator) ? $paginator->firstItem() : 0 }} - {{ isset($paginator) ? $paginator->lastItem() : 0 }} of {{ isset($paginator) ? $paginator->total() : 0 }} projects
                        </span>
                    </div>
                </div>
            </div>

            <!-- Dynamic Project Loop -->
            <div id="project-list-container">
            @if(isset($paginator) && $paginator->count() > 0)
                @foreach($paginator as $project)
                    @php
                        $currency = $project['currency']['sign'] ?? '$';
                        $currencyCode = $project['currency']['code'] ?? 'USD';
                        $minBudget = $project['budget']['minimum'] ?? 0;
                        $maxBudget = $project['budget']['maximum'] ?? 0;
                        $calculatedBid = $project['calculated_bid_amount'] ?? $minBudget;
                        $isHourly = ($project['type'] ?? '') === 'hourly';

                        $owner = $project['owner_info'] ?? $project['owner'] ?? [];
                        $ownerStatus = $owner['status'] ?? [];

                        // Real Client Name Extraction
                        $clientRawName = $owner['first_name'] 
                                      ?? $owner['public_name'] 
                                      ?? $owner['display_name'] 
                                      ?? $owner['username'] 
                                      ?? '';

                        $cleanClientName = trim(preg_replace('/[^a-zA-Z\s]/', '', explode(' ', $clientRawName)[0] ?? ''));
                        if (empty($cleanClientName) || strtolower($cleanClientName) === 'client') {
                            $cleanClientName = $owner['username'] ?? 'Client';
                        }

                        $isIdentityVerified = !empty($ownerStatus['id_verified']) || !empty($ownerStatus['identity_verified']);
                        $isPaymentVerified  = !empty($ownerStatus['payment_verified']);
                        $isDepositMade      = !empty($ownerStatus['deposit_verified']) || !empty($ownerStatus['deposit_made']);
                        $isEmailVerified    = !empty($ownerStatus['email_verified']);

                        $empReputation = $owner['employer_reputation'] ?? $owner['reputation'] ?? $owner['user_employer_reputation'] ?? [];
                        $entireHistory = $empReputation['entire_history'] ?? $empReputation['history'] ?? $empReputation;
                        $reviewsCount = $entireHistory['reviews'] ?? 0;
                        $projectsComplete = $entireHistory['complete'] ?? 0;
                        $rawRating = $entireHistory['overall'] ?? $entireHistory['rating'] ?? $owner['rating'] ?? 0;
                        $rating = round((float) $rawRating, 1);

                        $countryData = $owner['country'] ?? ($owner['location']['country'] ?? []);
                        $countryName = is_array($countryData) ? ($countryData['name'] ?? 'Worldwide') : ($countryData ?? 'Worldwide');
                        $countryCode = is_array($countryData) ? strtolower($countryData['code'] ?? 'un') : 'un';
                        
                        $flagUrl = !empty($countryData['flag_url_cdn']) 
                            ? 'https:' . ltrim($countryData['flag_url_cdn'], ':') 
                            : ($countryCode !== 'un' ? "https://flagcdn.com/20x15/{$countryCode}.png" : null);

                        $timestamp = $project['time_updated'] 
                                  
                                  ?? $project['time_submitted'] 
                                  ?? null;

                        $submittedDate = $timestamp 
                            ? \Carbon\Carbon::createFromTimestamp($timestamp)->diffForHumans() 
                            : 'Recently';

                        $isAlreadyBid = in_array($project['id'], $alreadyBidProjectIds ?? []);
                        $projectSkills = array_column($project['jobs'] ?? [], 'name');

                        // Skill Match Highlighting
                        $matchedSkills = [];
                        $unmatchedSkills = [];
                        $userSkillNamesArr = $userSkillNames ?? [];
                        foreach ($projectSkills as $pSkill) {
                            if (!empty($userSkillNamesArr) && in_array(strtolower(trim($pSkill)), $userSkillNamesArr)) {
                                $matchedSkills[] = $pSkill;
                            } else {
                                $unmatchedSkills[] = $pSkill;
                            }
                        }
                        $totalProjectSkills = count($projectSkills);
                        $matchCount = count($matchedSkills);
                        $matchPercent = $totalProjectSkills > 0 ? round(($matchCount / $totalProjectSkills) * 100) : 0;
                    @endphp

                    <div class="card project-card border-0 shadow-sm mb-3">
                        <div class="card-body p-4">
                            <div class="row">
                                <!-- Details Left -->
                                <div class="col-lg-9 col-md-8 border-end">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h5 class="fw-bold text-dark mb-1">
                                            <a href="https://www.freelancer.com/projects/{{ $project['seo_url'] ?? $project['id'] }}" target="_blank" class="text-dark text-decoration-none">
                                                {{ $project['title'] ?? 'Untitled Project' }}
                                            </a>
                                        </h5>
                                        <div class="d-flex gap-2">
                                            <i class="bi bi-shield-fill-check {{ $isIdentityVerified ? 'text-success' : 'text-danger' }}" title="{{ $isIdentityVerified ? 'Identity Verified' : 'Identity Unverified' }}"></i>
                                            <i class="bi bi-credit-card-2-front-fill {{ $isPaymentVerified ? 'text-success' : 'text-danger' }}" title="{{ $isPaymentVerified ? 'Payment Verified' : 'Payment Unverified' }}"></i>
                                            <i class="bi bi-currency-dollar {{ $isDepositMade ? 'text-success' : 'text-danger' }}" title="{{ $isDepositMade ? 'Deposit Made' : 'Deposit Not Made' }}"></i>
                                            <i class="bi bi-envelope-fill {{ $isEmailVerified ? 'text-success' : 'text-danger' }}" title="{{ $isEmailVerified ? 'Email Verified' : 'Email Unverified' }}"></i>
                                        </div>
                                    </div>

                                    <p class="text-muted small mb-2" style="font-size: 13px; line-height: 1.5;">
                                        {{ Str::limit($project['preview_description'] ?? $project['description'] ?? '', 220) }}
                                    </p>

                                    <!-- Meta -->
                                    <div class="d-flex flex-wrap align-items-center gap-3 meta-text mb-2">
                                        <span><i class="bi bi-hourglass-split"></i> {{ $submittedDate }}</span>
                                        <span>— {{ $project['bid_stats']['bid_count'] ?? 0 }} bids</span>
                                        <span>— Avg: {{ $currency }}{{ round($project['bid_stats']['bid_avg'] ?? 0) }}</span>
                                        <span>— ID: {{ $project['id'] }}</span>
                                        
                                        <span class="d-flex align-items-center gap-2">
                                            @if($flagUrl)
                                                <img src="{{ $flagUrl }}" 
                                                     alt="{{ $countryName }}" 
                                                     title="{{ $countryName }}" 
                                                     class="rounded-1 border shadow-sm"
                                                     style="width: 18px; height: 13px; object-fit: cover;">
                                            @else
                                                <span class="badge bg-secondary rounded-0" style="font-size: 8px;">{{ strtoupper($countryCode) }}</span>
                                            @endif

                                            <span class="text-muted small fw-medium">{{ $countryName }}</span>
                                            <span class="text-primary fw-semibold">{{ $cleanClientName }}</span>

                                            <span class="badge {{ $rating > 0 ? 'bg-success' : 'bg-danger' }}" style="font-size: 10px;">
                                                ★ {{ $rating }} rating
                                            </span>
                                        </span>
                                    </div>

                                    <!-- Skills with Match Highlighting -->
                                    <div class="mb-2 d-flex flex-wrap align-items-center gap-1">
                                        <i class="bi bi-tag-fill text-secondary me-1" style="font-size: 12px;"></i>
                                        @if(!empty($userSkillNamesArr) && $matchCount > 0)
                                            <span class="skill-match-badge {{ $matchPercent >= 60 ? 'high' : ($matchPercent >= 30 ? 'medium' : 'low') }} me-1">
                                                <i class="bi bi-bullseye"></i> {{ $matchCount }}/{{ $totalProjectSkills }} skills match
                                            </span>
                                        @endif
                                        @if(!empty($matchedSkills))
                                            @foreach(array_slice($matchedSkills, 0, 7) as $skill)
                                                <span class="skill-tag matched"><i class="bi bi-check-circle-fill" style="font-size: 9px;"></i> {{ $skill }}</span>
                                            @endforeach
                                        @endif
                                        @if(!empty($unmatchedSkills))
                                            @foreach(array_slice($unmatchedSkills, 0, 5) as $skill)
                                                <span class="skill-tag unmatched">{{ $skill }}</span>
                                            @endforeach
                                        @endif
                                        @if(empty($projectSkills))
                                            <span class="text-muted small">No specific skills listed</span>
                                        @endif
                                    </div>

                                    <div class="small">
                                        <i class="bi bi-file-earmark-text text-muted me-1"></i>
                                        <span class="text-muted">View detailed project report.</span> 
                                        <a href="https://www.freelancer.com/projects/{{ $project['seo_url'] ?? $project['id'] }}" target="_blank" class="text-primary text-decoration-none">View Report</a>
                                    </div>
                                </div>

                                <!-- Action Right -->
                                <div class="col-lg-3 col-md-4 ps-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-end gap-3 meta-text mb-2">
                                            <span class="badge-icon-item {{ $reviewsCount > 0 ? '' : 'gray-out' }}">
                                                <i class="bi bi-chat-left-text"></i> {{ $reviewsCount }} reviews
                                            </span>
                                            <span class="badge-icon-item {{ $projectsComplete > 0 ? '' : 'gray-out' }}">
                                                <i class="bi bi-check-circle"></i> {{ $projectsComplete }} projects
                                            </span>
                                        </div>

                                        <div class="text-end">
                                            <h4 class="fw-bold mb-1 text-dark">
                                                {{ $currency }}{{ $minBudget }} - {{ $currency }}{{ $maxBudget }}
                                            </h4>
                                            @if($isHourly)
                                                <span class="meta-text d-block mb-2">{{ $currencyCode }}/hr</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- 1-Click Bid Action Container -->
                                    <div id="bid_container_{{ $project['id'] }}" class="mt-2 text-end">
                                        @if($isAlreadyBid)
                                            <div class="text-end py-2">
                                                <span class="meta-text d-block mb-1">Your Bid: {{ $minBudget }} {{ $currencyCode }}</span>
                                                <div class="text-success small fw-semibold">
                                                    <i class="bi bi-check-circle-fill"></i> You have already bid on this project.
                                                </div>
                                                <a href="https://www.freelancer.com/projects/{{ $project['seo_url'] ?? $project['id'] }}" target="_blank" class="small text-primary text-decoration-none">View bid</a>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center justify-content-end gap-1 mb-2">
                                                <span class="meta-text">Your Bid:</span>
                                                <input type="number" 
                                                       id="bid_amount_{{ $project['id'] }}" 
                                                       class="form-control form-control-sm text-center py-0 px-1" 
                                                       value="{{ $calculatedBid }}" 
                                                       style="width: 70px; height: 26px;">
                                                <span class="meta-text">{{ $currencyCode }}</span>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <button type="button" 
                                                        id="place_bid_btn_{{ $project['id'] }}" 
                                                        class="btn btn-green btn-sm py-1 shadow-sm fw-semibold"
                                                        onclick="submit1ClickBid({{ $project['id'] }}, {{ json_encode($project['title'] ?? '', JSON_HEX_APOS | JSON_HEX_QUOT) }}, {{ json_encode($cleanClientName, JSON_HEX_APOS | JSON_HEX_QUOT) }}, {{ json_encode($projectSkills, JSON_HEX_APOS | JSON_HEX_QUOT) }}, {{ json_encode($currencyCode, JSON_HEX_APOS | JSON_HEX_QUOT) }}, {{ json_encode($project['seo_url'] ?? $project['id'], JSON_HEX_APOS | JSON_HEX_QUOT) }})">
                                                    Place Bid
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-primary btn-sm py-1 shadow-sm" 
                                                        style="background-color: #3b82f6; border-color: #3b82f6;"
                                                        onclick="openManualCustomizeModal({{ $project['id'] }}, {{ json_encode($project['title'] ?? '') }}, {{ json_encode($project['preview_description'] ?? $project['description'] ?? '') }}, {{ json_encode($cleanClientName, JSON_HEX_APOS | JSON_HEX_QUOT) }}, {{ json_encode($projectSkills) }}, '{{ $currencyCode }}', '{{ $currency }}', '{{ $calculatedBid }}', '{{ $project['seo_url'] ?? $project['id'] }}', '{{ $rating }}', '{{ $reviewsCount }}')">
                                                    Customize
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Pagination Links -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $paginator->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="card border-0 shadow-sm p-4 text-center">
                    <p class="text-muted mb-0">No active projects found matching your filters at the moment.</p>
                </div>
            @endif
            </div>{{-- end #project-list-container --}}

        </div>
    </div>
@endif

<!-- Manual Customize Proposal Modal -->
<div class="modal fade" id="customizeBidModal" tabindex="-1" aria-labelledby="customizeBidModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            
            <!-- Modal Header -->
            <div class="modal-header border-bottom py-3 px-4 bg-light d-flex align-items-center justify-content-between">
                <div class="pe-3 w-100">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1" style="font-size: 11px;">CUSTOMIZE PROPOSAL</span>
                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold" id="modal_type_badge" style="font-size: 11px;">USD</span>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" id="modal_project_title" style="max-width: 650px;">Project Title</h5>
                    <small class="text-muted" id="modal_client_details">Client: <strong>Client</strong> (★ 0.0 • 0 Reviews)</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <form id="manualCustomizeForm">
                    <input type="hidden" id="cust_modal_project_id">
                    <input type="hidden" id="cust_modal_seo_url">
                    <input type="hidden" id="cust_modal_client_name">

                    <!-- Proposal Textarea Action Header -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label small fw-semibold text-dark mb-0">Write Your Proposal Pitch</label>
                        
                        <button type="button" 
                                id="modal_ai_btn" 
                                class="btn btn-sm btn-outline-primary shadow-sm px-3 py-1 fw-semibold d-flex align-items-center gap-1" 
                                style="font-size: 12px;" 
                                onclick="generateAIProposal()">
                            <i class="bi bi-stars text-warning"></i>
                            <span>Generate with AI</span>
                        </button>
                    </div>

                    <!-- Proposal Textarea -->
                    <div class="mb-3">
                        <textarea class="form-control font-monospace shadow-none" 
                                  id="cust_modal_proposal_textarea" 
                                  rows="8" 
                                  style="font-size: 13.5px; line-height: 1.6; resize: vertical;" 
                                  placeholder="Type your personalized proposal here or click 'Generate with AI'..." required></textarea>
                        <div class="text-end mt-1">
                            <span class="text-muted small" id="cust_modal_char_count" style="font-size: 11.5px;">0 characters</span>
                        </div>
                    </div>

                    <!-- Bid Parameters Card -->
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="row g-3">
                            <!-- Bid Amount -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark mb-1">Your Bid Amount</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white fw-bold text-secondary" id="cust_modal_curr_sign">$</span>
                                    <input type="number" class="form-control text-center fw-bold shadow-none" id="cust_modal_amount" min="1" step="any" required>
                                    <span class="input-group-text bg-white small text-muted" id="cust_modal_curr_code">USD</span>
                                </div>
                            </div>

                            <!-- Delivery Timeline -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark mb-1">Delivery Period</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control text-center fw-bold shadow-none" id="cust_modal_days" value="7" min="1" max="90" required>
                                    <span class="input-group-text bg-white small text-muted">Days</span>
                                </div>
                            </div>

                            <!-- Milestone Request -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark mb-1">Milestone Request</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control text-center fw-bold shadow-none" id="cust_modal_milestone" value="100" min="10" max="100" required>
                                    <span class="input-group-text bg-white small text-muted">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="cust_modal_submit_btn" class="btn btn-green btn-sm px-4 py-2 fw-semibold shadow-sm d-flex align-items-center gap-1" onclick="submitManualCustomBid()">
                    <i class="bi bi-send-fill"></i> Place Custom Bid
                </button>
            </div>

        </div>
    </div>
</div>

<!-- JavaScript for 1-Click Instant Bidding -->
<script>
function submit1ClickBid(projectId, projectTitle, clientName, skills, currencyCode, seoUrl) {
    const amountInput = document.getElementById('bid_amount_' + projectId);
    const amount = amountInput.value;
    const btn = document.getElementById('place_bid_btn_' + projectId);
    const container = document.getElementById('bid_container_' + projectId);

    if (!amount || amount <= 0) {
        alert('Please enter a valid bid amount.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Placing...';

    fetch("{{ route('freelancer.placeBid') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            project_id: projectId,
            project_title: projectTitle,
            amount: amount,
            client_name: clientName,
            skills: skills
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (window.updateNavbarAiBids && typeof data.new_balance !== 'undefined') {
                window.updateNavbarAiBids(data.new_balance);
            }

            container.innerHTML = `
                <div class="text-end py-2">
                    <span class="meta-text d-block mb-1">Your Bid: ${amount} ${currencyCode}</span>
                    <div class="text-success small fw-semibold">
                        <i class="bi bi-check-circle-fill"></i> You have already bid on this project.
                    </div>
                    <a href="https://www.freelancer.com/projects/${seoUrl}" target="_blank" class="small text-primary text-decoration-none">View bid</a>
                </div>
            `;
        } else {
            btn.disabled = false;
            btn.innerHTML = 'Place Bid';
            alert('Bid Failed: ' + data.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = 'Place Bid';
        alert('Network error while placing bid. Please try again.');
    });
}
</script>

<!-- JavaScript for Live Feed Auto-Polling -->
<script>
(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = parseInt(urlParams.get('page') || '1');
    if (currentPage !== 1) {
        const statusEl = document.getElementById('live-feed-status');
        if (statusEl) statusEl.textContent = '● Page ' + currentPage;
        return;
    }

    const POLL_INTERVAL = 20000;
    const LIVE_FEED_URL = @json(route('freelancer.liveFeed'));
    let knownIds = new Set();
    let isPolling = false;

    document.querySelectorAll('[id^="bid_container_"]').forEach(el => {
        const id = parseInt(el.id.replace('bid_container_', ''));
        if (id) knownIds.add(id);
    });

    function escHtml(str) {
        if (str === null || str === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(str);
        return d.innerHTML;
    }

    function buildProjectCard(p) {
        const o = p.owner || {};
        const vi = (ok, icon, y, n) =>
            `<i class="bi ${icon} ${ok ? 'text-success' : 'text-danger'}" title="${ok ? y : n}"></i>`;

        let skillsHtml = '<i class="bi bi-tag-fill text-secondary me-1" style="font-size:12px;"></i>';
        if (p.match_count > 0) {
            const mc = p.match_percent >= 60 ? 'high' : (p.match_percent >= 30 ? 'medium' : 'low');
            skillsHtml += `<span class="skill-match-badge ${mc} me-1"><i class="bi bi-bullseye"></i> ${p.match_count}/${p.total_skills} skills match</span>`;
        }
        (p.skills || []).forEach(s => {
            if (s.matched) {
                skillsHtml += `<span class="skill-tag matched"><i class="bi bi-check-circle-fill" style="font-size:9px;"></i> ${escHtml(s.name)}</span>`;
            } else {
                skillsHtml += `<span class="skill-tag unmatched">${escHtml(s.name)}</span>`;
            }
        });
        if (!p.skills || p.skills.length === 0) {
            skillsHtml += '<span class="text-muted small">No specific skills listed</span>';
        }

        const flagHtml = o.flag_url
            ? `<img src="${escHtml(o.flag_url)}" alt="${escHtml(o.country)}" title="${escHtml(o.country)}" class="rounded-1 border shadow-sm" style="width:18px;height:13px;object-fit:cover;">`
            : `<span class="badge bg-secondary rounded-0" style="font-size:8px;">${escHtml((o.country_code||'un').toUpperCase())}</span>`;

        let bidHtml = '';
        const skillNames = (p.skills||[]).map(s => s.name);
        const cleanClient = o.username || 'Client';

        if (p.already_bid) {
            bidHtml = `<div class="text-end py-2">
                <span class="meta-text d-block mb-1">Your Bid: ${p.budget_min} ${escHtml(p.currency_code)}</span>
                <div class="text-success small fw-semibold"><i class="bi bi-check-circle-fill"></i> Already bid</div>
                <a href="https://www.freelancer.com/projects/${escHtml(p.seo_url)}" target="_blank" class="small text-primary text-decoration-none">View bid</a>
            </div>`;
        } else {
            const bidVal = p.calculated_bid_amount !== undefined && p.calculated_bid_amount !== null ? p.calculated_bid_amount : p.budget_min;
            bidHtml = `<div class="d-flex align-items-center justify-content-end gap-1 mb-2">
                <span class="meta-text">Your Bid:</span>
                <input type="number" id="bid_amount_${p.id}" class="form-control form-control-sm text-center py-0 px-1" value="${bidVal}" style="width:70px;height:26px;">
                <span class="meta-text">${escHtml(p.currency_code)}</span>
            </div>
            <div class="d-grid gap-2">
                <button type="button" id="place_bid_btn_${p.id}" class="btn btn-green btn-sm py-1 shadow-sm fw-semibold"
                    onclick='submit1ClickBid(${p.id}, ${JSON.stringify(p.title)}, ${JSON.stringify(cleanClient)}, ${JSON.stringify(skillNames)}, ${JSON.stringify(p.currency_code)}, ${JSON.stringify(p.seo_url)})'>
                    Place Bid
                </button>
                <button type="button" class="btn btn-primary btn-sm py-1 shadow-sm" style="background-color:#3b82f6;border-color:#3b82f6;"
                    onclick='openManualCustomizeModal(${p.id}, ${JSON.stringify(p.title)}, ${JSON.stringify(p.description || "")}, ${JSON.stringify(cleanClient)}, ${JSON.stringify(skillNames)}, ${JSON.stringify(p.currency_code)}, ${JSON.stringify(p.currency_sign)}, ${bidVal}, ${JSON.stringify(p.seo_url)}, ${JSON.stringify(o.rating || "0.0")}, ${JSON.stringify(o.reviews || 0)})'>
                    Customize
                </button>   
            </div>`;
        }

        return `<div class="card project-card border-0 shadow-sm mb-3 new-project live-feed-enter" data-project-id="${p.id}">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-lg-9 col-md-8 border-end">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h5 class="fw-bold text-dark mb-1">
                                <span class="badge bg-success me-2" style="font-size:10px;animation:fadeNewBadge 8s forwards;">NEW</span>
                                <a href="https://www.freelancer.com/projects/${escHtml(p.seo_url)}" target="_blank" class="text-dark text-decoration-none">${escHtml(p.title)}</a>
                            </h5>
                            <div class="d-flex gap-2">
                                ${vi(o.identity_verified,'bi-shield-fill-check','Identity Verified','Identity Unverified')}
                                ${vi(o.payment_verified,'bi-credit-card-2-front-fill','Payment Verified','Payment Unverified')}
                                ${vi(o.deposit_made,'bi-currency-dollar','Deposit Made','Deposit Not Made')}
                                ${vi(o.email_verified,'bi-envelope-fill','Email Verified','Email Unverified')}
                            </div>
                        </div>
                        <p class="text-muted small mb-2" style="font-size:13px;line-height:1.5;">${escHtml(p.description)}</p>
                        <div class="d-flex flex-wrap align-items-center gap-3 meta-text mb-2">
                            <span><i class="bi bi-hourglass-split"></i> ${escHtml(p.submitted_ago)}</span>
                            <span>— ${p.bid_count} bids</span>
                            <span>— Avg: ${escHtml(p.currency_sign)}${p.bid_avg}</span>
                            <span>— ID: ${p.id}</span>
                            <span class="d-flex align-items-center gap-2">
                                ${flagHtml}
                                <span class="text-muted small fw-medium">${escHtml(o.country)}</span>
                                <span class="text-primary fw-semibold">${escHtml(cleanClient)}</span>
                                <span class="badge ${o.rating > 0 ? 'bg-success' : 'bg-danger'}" style="font-size:10px;">★ ${o.rating} rating</span>
                            </span>
                        </div>
                        <div class="mb-2 d-flex flex-wrap align-items-center gap-1">${skillsHtml}</div>
                        <div class="small">
                            <i class="bi bi-file-earmark-text text-muted me-1"></i>
                            <span class="text-muted">View detailed project report.</span>
                            <a href="https://www.freelancer.com/projects/${escHtml(p.seo_url)}" target="_blank" class="text-primary text-decoration-none">View Report</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 ps-3 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-end gap-3 meta-text mb-2">
                                <span class="badge-icon-item ${o.reviews > 0 ? '' : 'gray-out'}"><i class="bi bi-chat-left-text"></i> ${o.reviews} reviews</span>
                                <span class="badge-icon-item ${o.projects_complete > 0 ? '' : 'gray-out'}"><i class="bi bi-check-circle"></i> ${o.projects_complete} projects</span>
                            </div>
                            <div class="text-end">
                                <h4 class="fw-bold mb-1 text-dark">${escHtml(p.currency_sign)}${p.budget_min} - ${escHtml(p.currency_sign)}${p.budget_max}</h4>
                                ${p.is_hourly ? `<span class="meta-text d-block mb-2">${escHtml(p.currency_code)}/hr</span>` : ''}
                            </div>
                        </div>
                        <div id="bid_container_${p.id}" class="mt-2 text-end">${bidHtml}</div>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function showToast(count) {
        const existing = document.getElementById('live-feed-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id = 'live-feed-toast';
        toast.className = 'new-project-toast';
        toast.innerHTML = `<i class="bi bi-lightning-charge-fill me-1"></i> ${count} new project${count > 1 ? 's' : ''} found`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    function pollLiveFeed() {
        if (isPolling) return;
        isPolling = true;

        const excludeArr = Array.from(knownIds);
        const params = new URLSearchParams();
        excludeArr.forEach(id => params.append('exclude[]', id));

        const statusEl = document.getElementById('live-feed-status');
        if (statusEl) statusEl.textContent = '⟳ Checking...';

        fetch(LIVE_FEED_URL + '?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const newProjects = (data.projects || []).filter(p => !knownIds.has(p.id));

            if (newProjects.length > 0) {
                const container = document.getElementById('project-list-container');
                const firstCard = container.querySelector('.card.project-card');

                newProjects.reverse().forEach(project => {
                    knownIds.add(project.id);
                    const cardHtml = buildProjectCard(project);
                    if (firstCard) {
                        firstCard.insertAdjacentHTML('beforebegin', cardHtml);
                    } else {
                        container.insertAdjacentHTML('afterbegin', cardHtml);
                    }
                    const newCard = container.querySelector(`[data-project-id="${project.id}"]`);
                    if (newCard) {
                        requestAnimationFrame(() => newCard.classList.add('live-feed-enter-active'));
                    }
                });

                showToast(newProjects.length);
            }

            if (statusEl) statusEl.textContent = '● Live Feed Active';
        })
        .catch(err => {
            console.warn('Live feed poll failed:', err);
            if (statusEl) statusEl.textContent = '● Live Feed Active';
        })
        .finally(() => { isPolling = false; });
    }

    setInterval(pollLiveFeed, POLL_INTERVAL);
})();

let manualCustomizeModal = null;
let currentCustomProject = {};

function openManualCustomizeModal(projectId, title, description, clientName, skills, currencyCode, currencySign, minBudget, seoUrl, rating, reviewsCount) {
    if (!manualCustomizeModal) {
        manualCustomizeModal = new bootstrap.Modal(document.getElementById('customizeBidModal'));
    }

    currentCustomProject = {
        projectId,
        title: title || '',
        description: description || '',
        clientName: clientName || '',
        skills: skills || [],
        currencyCode: currencyCode || 'USD',
        currencySign: currencySign || '$',
        minBudget: minBudget || 0,
        seoUrl: seoUrl || '',
        rating: rating || '0.0',
        reviewsCount: reviewsCount || 0
    };

    // Populate Fields
    document.getElementById('cust_modal_project_id').value = projectId;
    document.getElementById('cust_modal_seo_url').value = seoUrl;
    document.getElementById('cust_modal_client_name').value = currentCustomProject.clientName;
    document.getElementById('modal_project_title').textContent = title;
    document.getElementById('modal_type_badge').textContent = currentCustomProject.currencyCode;
    document.getElementById('modal_client_details').innerHTML = `Client: <strong>${currentCustomProject.clientName || 'Client'}</strong> (★ ${currentCustomProject.rating} • ${currentCustomProject.reviewsCount} Reviews)`;

    const cardInput = document.getElementById('bid_amount_' + projectId);
    document.getElementById('cust_modal_amount').value = cardInput ? cardInput.value : currentCustomProject.minBudget;
    document.getElementById('cust_modal_curr_sign').textContent = currentCustomProject.currencySign;
    document.getElementById('cust_modal_curr_code').textContent = currentCustomProject.currencyCode;
    document.getElementById('cust_modal_days').value = 7;
    document.getElementById('cust_modal_milestone').value = 100;

    const textarea = document.getElementById('cust_modal_proposal_textarea');
    textarea.value = '';
    updateManualCharCount();
    textarea.oninput = updateManualCharCount;

    manualCustomizeModal.show();
}

function generateAIProposal() {
    const aiBtn = document.getElementById('modal_ai_btn');
    const textarea = document.getElementById('cust_modal_proposal_textarea');

    // Show Loading Spinner
    aiBtn.disabled = true;
    aiBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Generating...';

    fetch("{{ route('freelancer.generateAiProposal') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            project_title: currentCustomProject.title,
            project_description: currentCustomProject.description,
            skills: currentCustomProject.skills,
            client_name: currentCustomProject.clientName
        })
    })
    .then(res => res.json())
    .then(data => {
        aiBtn.disabled = false;
        aiBtn.innerHTML = '<i class="bi bi-stars text-warning"></i> <span>Generate with AI</span>';

        if (data.success && data.proposal) {
            textarea.value = data.proposal;
            updateManualCharCount();

            // Real-time update for Navbar AI Bids badge
            if (window.updateNavbarAiBids && typeof data.new_balance !== 'undefined') {
                window.updateNavbarAiBids(data.new_balance);
            }
        } else {
            alert('AI Generation Error: ' + (data.message || 'Unknown response from server.'));
        }
    })
    .catch(err => {
        aiBtn.disabled = false;
        aiBtn.innerHTML = '<i class="bi bi-stars text-warning"></i> <span>Generate with AI</span>';
        alert('Connection Error: ' + err.message);
    });
}

function updateManualCharCount() {
    const length = document.getElementById('cust_modal_proposal_textarea').value.length;
    document.getElementById('cust_modal_char_count').textContent = `${length} characters`;
}

function submitManualCustomBid() {
    const projectId = document.getElementById('cust_modal_project_id').value;
    const projectTitle = currentCustomProject.title;
    const clientName = currentCustomProject.clientName;
    const amount = document.getElementById('cust_modal_amount').value;
    const days = document.getElementById('cust_modal_days').value;
    const milestone = document.getElementById('cust_modal_milestone').value;
    const proposal = document.getElementById('cust_modal_proposal_textarea').value;
    const currencyCode = currentCustomProject.currencyCode;
    const seoUrl = document.getElementById('cust_modal_seo_url').value;
    const submitBtn = document.getElementById('cust_modal_submit_btn');

    if (!proposal.trim()) {
        alert('Please write your proposal pitch before submitting.');
        return;
    }

    if (!amount || amount <= 0) {
        alert('Please enter a valid bid amount.');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Placing...';

    fetch("{{ route('freelancer.placeBid') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            project_id: projectId,
            project_title: projectTitle,
            client_name: clientName,
            amount: amount,
            delivery_days: days,
            milestone_percentage: milestone,
            custom_proposal: proposal
        })
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send-fill"></i> Place Custom Bid';

        if (data.success) {
            manualCustomizeModal.hide();

            if (window.updateNavbarAiBids && typeof data.new_balance !== 'undefined') {
                window.updateNavbarAiBids(data.new_balance);
            }

            const container = document.getElementById('bid_container_' + projectId);
            if (container) {
                container.innerHTML = `
                    <div class="text-end py-2">
                        <span class="meta-text d-block mb-1">Your Bid: ${amount} ${currencyCode}</span>
                        <div class="text-success small fw-semibold">
                            <i class="bi bi-check-circle-fill"></i> You have already bid on this project.
                        </div>
                        <a href="https://www.freelancer.com/projects/${seoUrl}" target="_blank" class="small text-primary text-decoration-none">View bid</a>
                    </div>
                `;
            }
        } else {
            alert('Bid Failed: ' + data.message);
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send-fill"></i> Place Custom Bid';
        alert('Network error while placing custom bid.');
    });
}
</script>

@endsection