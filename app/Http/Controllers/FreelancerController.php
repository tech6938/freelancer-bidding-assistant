<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FreelancerConnection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use App\Models\Skill;
use App\Models\ProjectFilter;
use App\Models\BidSetting;
use App\Models\BidPrice;
use App\Models\PlacedBid;
use App\Models\BidTemplate;
use App\Services\AutoBidService;

class FreelancerController extends Controller
{
    /**
     * Display the Freelancer dashboard / connection status.
     * Shows skill-matched active live projects (Bidman style).
     * If user has no skills set, shows all active projects.
     */
public function index(Request $request)
{
    $userId = Auth::id();
    $connection = $userId 
        ? FreelancerConnection::where('user_id', $userId)->first() 
        : null;

    $paginator = null;
    $alreadyBidProjectIds = [];
    $userSkillNames = [];
    $hasSkills = false;
    $templates = collect();
    $bidSettings = null;
    $currentUser = Auth::user();
    $userBidPrices = ($userId && $currentUser && $currentUser->canUsePriceMatrix()) ? BidPrice::where('user_id', $userId)->get() : collect();

    if ($connection && !empty($connection->access_token)) {
        // Track projects the user has already bid on
        $alreadyBidProjectIds = PlacedBid::where('user_id', $userId)
            ->pluck('freelancer_project_id')
            ->toArray();

        $page   = max(1, (int) $request->get('page', 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        // 1. Fetch user active skills
        $userSkills = Skill::where('user_id', $userId)
            ->where('is_active', true)
            ->whereNotNull('freelancer_job_id')
            ->get();

        $userSkillIds = $userSkills->pluck('freelancer_job_id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->toArray();

        $userSkillNames = $userSkills->pluck('name')
            ->map(fn($name) => strtolower(trim($name)))
            ->filter()
            ->values()
            ->toArray();

        $hasSkills = !empty($userSkillIds);
        $templates = BidTemplate::where('user_id', $userId)->get();
$bidSettings = BidSetting::firstOrCreate(
    ['user_id' => $userId],
    [
        'default_delivery_days' => 7,
        'milestone_percentage'  => 100,
        'max_daily_bids'        => 20,
    ]
);

        // 2. Setup API Parameters (Natural Latest Sorting)
        $baseUrl  = rtrim(config('services.freelancer.api_url', 'https://www.freelancer.com/api'), '/');
        $endpoint = $baseUrl . '/projects/0.1/projects/active/';

        $queryParams = [
            'limit'                          => $limit,
            'offset'                         => $offset,
            'sort_field'                     => 'time_updated', // Freelancer default: Newest to Oldest
            'job_details'                    => 'true',
            'user_details'                   => 'true',
            'owner_info'                     => 'true',
            'user_country_details'           => 'true',
        ];

        // Pass skills to Freelancer API
        if ($hasSkills) {
            $queryParams['jobs'] = $userSkillIds;
        }

        // Clean query string (jobs[]=3&jobs[]=17)
        $queryString = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($queryParams));
        $fullApiUrl  = $endpoint . '?' . $queryString;

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'freelancer-oauth-v1' => $connection->access_token,
                    'Accept'              => 'application/json',
                ])
                ->timeout(15)
                ->get($fullApiUrl);

            if ($response->successful()) {
                $data         = $response->json()['result'] ?? [];
                $rawProjects  = $data['projects'] ?? [];
                $usersDict    = $data['users'] ?? [];
                $totalFromApi = $data['total_count'] ?? count($rawProjects);

                $processedProjects = [];

                foreach ($rawProjects as $project) {
                    // Check 1: Ensure project is strictly active
                    $status = strtolower($project['status'] ?? '');
                    if (!empty($status) && $status !== 'active') {
                        continue;
                    }

                    // Check 2: Match with user skills
                    if ($hasSkills) {
                        $projectJobs = $project['jobs'] ?? [];
                        $projectJobIds = array_column($projectJobs, 'id');
                        $projectJobNames = array_map(fn($n) => strtolower(trim($n)), array_column($projectJobs, 'name'));

                        $hasIdMatch   = !empty(array_intersect($projectJobIds, $userSkillIds));
                        $hasNameMatch = !empty(array_intersect($projectJobNames, $userSkillNames));

                        if (!$hasIdMatch && !$hasNameMatch) {
                            continue;
                        }
                    }

                    // Attach Client Details
                    $ownerId = $project['owner_id'] ?? null;
                    $owner   = (isset($project['owner_info']) && is_array($project['owner_info'])) 
                                ? $project['owner_info'] 
                                : ($usersDict[$ownerId] ?? []);
                    
                    $project['owner_info'] = $owner;
                    $project['owner']      = $owner;
                    $project['calculated_bid_amount'] = BidPrice::getBidAmountForProject($userBidPrices, $project);

                    $processedProjects[] = $project;
                }

                $paginator = new LengthAwarePaginator(
                    array_slice($processedProjects, 0, $limit),
                    count($processedProjects) > 0 ? $totalFromApi : 0,
                    $limit,
                    $page,
                    ['path' => route('freelancer.index'), 'query' => $request->query()]
                );

            } else {
                session()->flash('error', 'Failed to fetch projects from Freelancer API.');
                logger()->error('Freelancer API Error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Could not connect to Freelancer API.');
            logger()->error('Freelancer Feed Exception: ' . $e->getMessage());
        }
    }

    return view('freelancer.index', compact(
        'connection', 'paginator', 'alreadyBidProjectIds', 'userSkillNames', 'hasSkills','templates', 'bidSettings', 'userBidPrices'
    ));
}

    /**
     * Live Feed API — returns latest projects as JSON for auto-polling.
     * JavaScript polls this endpoint every 20s and prepends new project cards.
     */
   public function liveFeed(Request $request)
{
    $userId = Auth::id();
    $connection = FreelancerConnection::where('user_id', $userId)->first();

    if (!$connection || empty($connection->access_token)) {
        return response()->json(['projects' => []], 401);
    }

    $excludeIds = array_map('intval', (array) $request->input('exclude', []));

    // 1. Fetch User Active Skills
    $userSkills = Skill::where('user_id', $userId)
        ->where('is_active', true)
        ->whereNotNull('freelancer_job_id')
        ->get();

    $userSkillIds = $userSkills->pluck('freelancer_job_id')
        ->map(fn($id) => (int) $id)
        ->filter()
        ->values()
        ->toArray();

    $userSkillNames = $userSkills->pluck('name')
        ->map(fn($name) => strtolower(trim($name)))
        ->filter()
        ->values()
        ->toArray();

    $hasSkills = !empty($userSkillIds);

    // 2. Track Bids Already Placed & User's Bid Price Matrix
    $alreadyBidProjectIds = PlacedBid::where('user_id', $userId)
        ->pluck('freelancer_project_id')
        ->toArray();

    $currentUser = Auth::user();
    $userBidPrices = ($userId && $currentUser && $currentUser->canUsePriceMatrix()) ? BidPrice::where('user_id', $userId)->get() : collect();

    // 3. Setup API Parameters (Natural Newest-First Sorting)
    $baseUrl = rtrim(config('services.freelancer.api_url', 'https://www.freelancer.com/api'), '/');
    $queryParams = [
        'limit'                          => 10,
        'offset'                         => 0,
        'sort_field'                     => 'time_updated', // Latest updated projects first
        'job_details'                    => 'true',
        'user_details'                   => 'true',
        'user_reputation'                => 'true',
        'owner_info'                     => 'true',
        'user_status'                    => 'true',
        'user_location_details'          => 'true',
        'user_country_details'           => 'true',
        'user_employer_reputation'       => 'true',
        'user_employer_reputation_extra' => 'true',
        'compact'                        => 'false',
    ];

    if ($hasSkills) {
        $queryParams['jobs'] = $userSkillIds;
    }

    $queryString = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($queryParams));
    $fullApiUrl = $baseUrl . '/projects/0.1/projects/active/?' . $queryString;

    try {
        $response = Http::withoutVerifying()
            ->withHeaders([
                'freelancer-oauth-v1' => $connection->access_token,
                'Accept'              => 'application/json',
            ])
            ->timeout(20)
            ->get($fullApiUrl);

        if (!$response->successful()) {
            return response()->json(['projects' => []], 500);
        }

        $data = $response->json()['result'] ?? [];
        $rawProjects = $data['projects'] ?? [];
        $usersDict = $data['users'] ?? [];

        $projects = [];

        foreach ($rawProjects as $project) {
            $projectId = (int) $project['id'];

            // Skip projects already displayed on screen
            if (in_array($projectId, $excludeIds)) {
                continue;
            }

            // Ensure project is active
            $status = strtolower($project['status'] ?? '');
            if (!empty($status) && $status !== 'active') {
                continue;
            }

            // Skill Match Verification (Job ID or Skill Name)
            $projectJobs = $project['jobs'] ?? [];
            $projectJobIds = array_column($projectJobs, 'id');
            $projectJobNames = array_map(fn($n) => strtolower(trim($n)), array_column($projectJobs, 'name'));

            $hasIdMatch   = !empty(array_intersect($projectJobIds, $userSkillIds));
            $hasNameMatch = !empty(array_intersect($projectJobNames, $userSkillNames));

            if ($hasSkills && !$hasIdMatch && !$hasNameMatch) {
                continue; // Skip non-matching projects
            }

            // Build skill tags with match indicators
            $projectSkills = [];
            $matchCount = 0;
            foreach ($projectJobs as $job) {
                $isMatched = !empty($userSkillNames) && in_array(strtolower(trim($job['name'])), $userSkillNames);
                if ($isMatched) $matchCount++;
                $projectSkills[] = ['name' => $job['name'], 'matched' => $isMatched];
            }
            $totalSkills = count($projectSkills);
            $matchPercent = $totalSkills > 0 ? round(($matchCount / $totalSkills) * 100) : 0;

            // Attach owner info
            $ownerId = $project['owner_id'] ?? null;
            $owner = (isset($project['owner_info']) && is_array($project['owner_info']))
                ? $project['owner_info']
                : ($usersDict[$ownerId] ?? []);
            $ownerStatus = $owner['status'] ?? [];

            $empReputation = $owner['employer_reputation'] ?? $owner['reputation'] ?? $owner['user_employer_reputation'] ?? [];
            $entireHistory = $empReputation['entire_history'] ?? $empReputation['history'] ?? $empReputation;
            $countryData = $owner['country'] ?? ($owner['location']['country'] ?? []);
            $countryName = is_array($countryData) ? ($countryData['name'] ?? 'Worldwide') : ($countryData ?? 'Worldwide');
            $countryCode = is_array($countryData) ? strtolower($countryData['code'] ?? 'un') : 'un';
            $flagUrl = !empty($countryData['flag_url_cdn'])
                ? 'https:' . ltrim($countryData['flag_url_cdn'], ':')
                : ($countryCode !== 'un' ? "https://flagcdn.com/20x15/{$countryCode}.png" : null);

            // Relative Timestamp
            $timestamp = $project['time_updated'] 
                          ?? $project['time_submitted'] 
                          ?? null;

            $submittedDate = $timestamp
                ? \Carbon\Carbon::createFromTimestamp($timestamp)->diffForHumans()
                : 'Recently';

            $isAlreadyBid = in_array($projectId, $alreadyBidProjectIds);
            $calculatedBidAmount = BidPrice::getBidAmountForProject($userBidPrices, $project);

            $projects[] = [
                'id'                    => $projectId,
                'title'                 => $project['title'] ?? 'Untitled Project',
                'description'           => Str::limit($project['preview_description'] ?? $project['description'] ?? '', 220),
                'seo_url'               => $project['seo_url'] ?? (string) $projectId,
                'budget_min'            => $project['budget']['minimum'] ?? 0,
                'budget_max'            => $project['budget']['maximum'] ?? 0,
                'calculated_bid_amount' => $calculatedBidAmount,
                'currency_sign'         => $project['currency']['sign'] ?? '$',
                'currency_code'         => $project['currency']['code'] ?? 'USD',
                'is_hourly'             => ($project['type'] ?? '') === 'hourly',
                'skills'                => $projectSkills,
                'match_count'           => $matchCount,
                'total_skills'          => $totalSkills,
                'match_percent'         => $matchPercent,
                'bid_count'             => $project['bid_stats']['bid_count'] ?? 0,
                'bid_avg'               => round($project['bid_stats']['bid_avg'] ?? 0),
                'submitted_ago'         => $submittedDate,
                'already_bid'           => $isAlreadyBid,
                'owner' => [
                    'username'          => $owner['username'] ?? 'Client',
                    'country'           => $countryName,
                    'country_code'      => $countryCode,
                    'flag_url'          => $flagUrl,
                    'rating'            => round((float) ($entireHistory['overall'] ?? $entireHistory['rating'] ?? $owner['rating'] ?? 0), 1),
                    'reviews'           => (int) ($entireHistory['reviews'] ?? 0),
                    'projects_complete' => (int) ($entireHistory['complete'] ?? 0),
                    'identity_verified' => !empty($ownerStatus['id_verified']) || !empty($ownerStatus['identity_verified']),
                    'payment_verified'  => !empty($ownerStatus['payment_verified']),
                    'deposit_made'      => !empty($ownerStatus['deposit_verified']) || !empty($ownerStatus['deposit_made']),
                    'email_verified'    => !empty($ownerStatus['email_verified']),
                ],
            ];
        }

        return response()->json(['projects' => $projects]);

    } catch (\Exception $e) {
        logger()->error('Live Feed Exception: ' . $e->getMessage());
        return response()->json(['projects' => []], 500);
    }
}

public function generateAiProposal(Request $request)
{
    $request->validate([
        'project_title'       => 'required|string',
        'project_description' => 'nullable|string',
        'skills'              => 'nullable',
        'client_name'         => 'nullable|string',
    ]);

    $user = Auth::user();

    // Enforce Active Subscription & AI Bids Balance
    if (!$user->hasActiveSubscription()) {
        return response()->json([
            'success' => false,
            'message' => '⚠️ Generating AI proposals requires an active subscription. Please subscribe to a plan.'
        ], 403);
    }

    if ($user->ai_bids_balance <= 0) {
        return response()->json([
            'success' => false,
            'message' => '⚠️ Your AI Bids balance is 0. Please top up your AI Bids to generate AI proposals.'
        ], 403);
    }

    $title       = trim($request->project_title);
    $description = trim($request->project_description ?? 'No description provided');
    $rawClient   = trim($request->client_name ?? '');
    
    // Clean Client Name
    $invalidNames = ['client', 'there', 'usd', 'eur', 'gbp', 'aud', 'cad', 'inr', 'pkr', '$', '€', '£'];
    $isValidName  = !empty($rawClient) && !in_array(strtolower($rawClient), $invalidNames);
    $clientName   = $isValidName ? $rawClient : '';

    $greeting = $isValidName ? "Hi {$clientName}," : "Hi there,";

    // Clean Skills
    $rawSkills = (array) ($request->skills ?? []);
    $skillsList = [];
    foreach ($rawSkills as $item) {
        if (is_array($item) && isset($item['name'])) {
            $skillsList[] = trim($item['name']);
        } elseif (is_string($item) && !in_array($item, ['$', '€', '£', 'USD'])) {
            $skillsList[] = trim($item);
        }
    }
    $skillsText = !empty($skillsList) ? implode(', ', array_slice($skillsList, 0, 5)) : 'the required technologies';

    // 1. Build the AI Prompt
    $prompt = <<<PROMPT
Write a short, persuasive, professional proposal pitch for the following project:
Client Greeting: {$greeting}
Project Title: {$title}
Project Description: {$description}
Required Skills: {$skillsText}

Instructions:
1. Start directly with "{$greeting}".
2. Address their project needs and explain how you will deliver using {$skillsText}.
3. Keep it engaging and concise (around 80-100 words).
4. End with a polite invitation to discuss details in chat.
5. Output ONLY the final proposal pitch directly without drafting notes, word counts, or headers.
PROMPT;

    // 2. Fetch API Key & Model
    $apiKey = trim(config('services.groq.api_key') ?: env('GROQ_API_KEY', ''));
    $model = config('services.groq.model') ?: env('GROQ_MODEL', 'openai/gpt-oss-20b');

    if (empty($apiKey)) {
        return response()->json([
            'success' => false,
            'message' => 'GROQ_API_KEY is missing in your .env or config file.'
        ], 200);
    }

    // 3. Call Groq's Official /v1/responses API
    try {
        $response = Http::withoutVerifying()
            ->timeout(10)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'       => $model,
                'messages'    => [
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ]
                ],
                'max_tokens'  => 800,
                'temperature' => 0.65,
            ]);

        if ($response->successful()) {
            $result = $response->json();
            
            // Extract text from the response format
            $generatedText = $result['choices'][0]['message']['content']
                ?? $result['output_text']
                ?? $result['output'][0]['content'][0]['text']
                ?? null;

            if (!empty($generatedText)) {
                // Strip internal thinking tags if present
                $generatedText = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $generatedText);
                $generatedText = trim($generatedText);

                // Deduct 1 AI bid from user balance
                $user->deductAiBid(1);

                return response()->json([
                    'success'     => true,
                    'proposal'    => $generatedText,
                    'new_balance' => $user->fresh()->ai_bids_balance,
                ]);
            }
        }

        // Return the exact error returned by the API
        $errorData = $response->json();
        $errorMsg = $errorData['error']['message'] ?? $response->body();

        return response()->json([
            'success' => false,
            'message' => 'Groq API Error: ' . $errorMsg
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Server Exception: ' . $e->getMessage()
        ], 200);
    }
}
    /**
     * Production-Ready 1-Click Place Bid Endpoint
     */
   public function placeBid(Request $request, AutoBidService $autoBidService)
{
    $request->validate([
        'project_id'           => 'required|numeric',
        'project_title'        => 'required|string',
        'amount'               => 'required|numeric|min:1',
        'client_name'          => 'nullable|string',
        'skills'               => 'nullable',
        'delivery_days'        => 'nullable|integer|min:1|max:90',
        'milestone_percentage' => 'nullable|integer|min:10|max:100',
        'custom_proposal'      => 'nullable|string',
    ]);

    $user = Auth::user();
    $userId = $user->id;

    // Enforce Active Subscription
    if (!$user->hasActiveSubscription()) {
        return response()->json([
            'success' => false,
            'message' => '⚠️ Placing bids requires an active subscription. Please subscribe to a plan.'
        ], 403);
    }

    // Enforce Plan Place Bids Limit
    $placeBidsLimit = (int) $user->getPlanFeature('place_bids_limit', 0);
    $subStartedAt = $user->activeSubscription?->started_at ?? now()->startOfMonth();
    $currentPlacedCount = PlacedBid::where('user_id', $userId)
        ->where('created_at', '>=', $subStartedAt)
        ->count();

    if ($placeBidsLimit > 0 && $currentPlacedCount >= $placeBidsLimit) {
        return response()->json([
            'success' => false,
            'message' => "⚠️ Your subscription plan's placed bids limit ({$placeBidsLimit}) has been reached. Please upgrade your plan."
        ], 429);
    }
    $connection = FreelancerConnection::where('user_id', $userId)->first();

    if (!$connection || empty($connection->access_token)) {
        return response()->json(['success' => false, 'message' => 'Freelancer account is not connected.'], 401);
    }

    if ($autoBidService->hasAlreadyBid($userId, (int) $request->project_id)) {
        return response()->json(['success' => false, 'message' => 'You have already placed a bid on this project.'], 400);
    }

    $bidSettings = BidSetting::firstOrCreate(['user_id' => $userId]);

    // Check daily bid limits for manual bidding
    $remaining = $autoBidService->getRemainingQuota($userId, $bidSettings->max_daily_bids ?? 20, 'manual');
    if ($remaining <= 0) {
        return response()->json(['success' => false, 'message' => 'Daily bid limit reached.'], 429);
    }

    $deliveryDays = $request->delivery_days ?: ($bidSettings->default_delivery_days ?? 7);
    $milestonePct = $request->milestone_percentage ?: ($bidSettings->milestone_percentage ?? 100);

    // 1. Extract and normalize project skills from the incoming request
    $rawSkills = (array) ($request->skills ?? []);
    $projectSkills = [];
    foreach ($rawSkills as $item) {
        if (is_array($item) && isset($item['name'])) {
            $projectSkills[] = strtolower(trim((string) $item['name']));
        } elseif (is_string($item)) {
            foreach (explode(',', $item) as $s) {
                $cleaned = strtolower(trim($s));
                if (!empty($cleaned)) {
                    $projectSkills[] = $cleaned;
                }
            }
        }
    }
    $projectSkills = array_values(array_unique(array_filter($projectSkills)));

    // 2. Select the final proposal (Custom > Matched Template > AI Generated)
    $matchedTemplateId = null;
    $skillMatchScore = 1.0;

    if (!empty(trim($request->custom_proposal ?? ''))) {
        $finalProposal = trim($request->custom_proposal);
    } else {
        $projectData = [
            'title'       => $request->project_title,
            'description' => $request->project_description ?? '',
            'client_name' => $request->client_name ?? '',
            'skills'      => $projectSkills,
        ];

        // Pehle matching template check karein
        $matchResult = $autoBidService->findBestTemplate($userId, $projectSkills);
        if (!empty($matchResult['template'])) {
            $matchedTemplateId = $matchResult['template']->id;
            $skillMatchScore = $matchResult['score'];
            $finalProposal = $autoBidService->buildProposal($matchResult['template'], $projectData);
        } else {
            // Agar koi template match na ho to AI se proposal generate karein
            if ($user->ai_bids_balance <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => '⚠️ No matching template found and your AI Bids balance is 0. Please top-up AI bids or create a template.'
                ], 403);
            }
            $finalProposal = $autoBidService->generateAiProposal($projectData);
            $user->deductAiBid(1);
        }
    }

    // Apply anti-ban human delay from Bid Settings if configured
    $bidDelaySeconds = (int) ($bidSettings->bid_delay_seconds ?? 0);
    if ($bidDelaySeconds > 0) {
        sleep($bidDelaySeconds);
    }

    // 3. Submit proposal to Freelancer API with atomic DB write
    $payload = [
        'project_id'           => (int) $request->project_id,
        'bidder_id'            => (int) $connection->freelancer_user_id,
        'amount'               => (float) $request->amount,
        'period'               => (int) $deliveryDays,
        'description'          => $finalProposal,
        'milestone_percentage' => (int) $milestonePct,
    ];

    $result = $autoBidService->placeBidViaApi($connection, $payload);
    if (!$result['success'] && !empty($bidSettings->auto_retry_failed_bids)) {
        sleep(1);
        $result = $autoBidService->placeBidViaApi($connection, $payload);
    }
    if ($result['success']) {
        try {
            DB::transaction(function () use ($userId, $request, $deliveryDays, $finalProposal, $result, $matchedTemplateId, $skillMatchScore) {
                PlacedBid::create([
                    'user_id'               => $userId,
                    'freelancer_project_id' => $request->project_id,
                    'bid_amount'            => $request->amount,
                    'delivery_days'         => $deliveryDays,
                    'proposal_text'         => $finalProposal,
                    'freelancer_bid_id'     => $result['data']['id'] ?? null,
                    'bid_type'              => 'manual',
                    'matched_template_id'   => $matchedTemplateId,
                    'skill_match_score'     => $skillMatchScore,
                ]);
            });

            return response()->json([
                'success'     => true,
                'message'     => 'Bid placed successfully!',
                'new_balance' => $user->fresh()->ai_bids_balance,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json(['success' => false, 'message' => 'You have already placed a bid on this project.'], 400);
        }
    }

    return response()->json(['success' => false, 'message' => $result['error'] ?? 'Freelancer API rejected this bid.'], 400);
}
    /**
     * Redirect the user to Freelancer.com OAuth authorization page.
     */
    public function connect(Request $request)
    {
        $state = Str::random(64);

        $request->session()->put('freelancer_oauth_state', $state);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => config('services.freelancer.client_id'),
            'redirect_uri'  => config('services.freelancer.redirect_uri'),
            'scope'         => 'basic',
            'state'         => $state,
            'prompt'        => 'select_account consent',
        ]);

        $url = config('services.freelancer.auth_url') . '?' . $query;

        return redirect()->away($url);
    }

    /**
     * Handle the OAuth callback from Freelancer.com.
     */
    public function callback(Request $request)
    {
        // 1. Check for OAuth Error
        if ($request->filled('error')) {
            return redirect()
                ->route('freelancer.index')
                ->with('error', 'Freelancer authorization was cancelled.');
        }

        // 2. Verify CSRF State
        $sessionState = $request->session()->pull('freelancer_oauth_state');

        if (!$sessionState || !hash_equals($sessionState, $request->state ?? '')) {
            return redirect()
                ->route('freelancer.index')
                ->with('error', 'Invalid session state. Please try connecting again.');
        }

        // 3. Check Authorization Code
        if (!$request->code) {
            return redirect()
                ->route('freelancer.index')
                ->with('error', 'Authorization code missing.');
        }

        // 4. Exchange Code for Access Token
        $response = Http::asForm()->post(
            config('services.freelancer.token_url'),
            [
                'grant_type'    => 'authorization_code',
                'code'          => $request->code,
                'client_id'     => config('services.freelancer.client_id'),
                'client_secret' => config('services.freelancer.client_secret'),
                'redirect_uri'  => config('services.freelancer.redirect_uri'),
            ]
        );

        if ($response->failed()) {
            logger()->error('Freelancer token exchange failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return redirect()
                ->route('freelancer.index')
                ->with('error', 'Unable to connect Freelancer account.');
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'] ?? null;
        $refreshToken = $tokenData['refresh_token'] ?? null;

        if (!$accessToken) {
            return redirect()
                ->route('freelancer.index')
                ->with('error', 'Freelancer did not return an access token.');
        }

        // 5. Fetch Freelancer User Profile
        $profileResponse = Http::withHeaders([
            'freelancer-oauth-v1' => $accessToken,
        ])->get(config('services.freelancer.api_url') . '/users/0.1/self/');

        if ($profileResponse->failed()) {
            logger()->error('Freelancer profile request failed', [
                'status' => $profileResponse->status(),
                'body'   => $profileResponse->body(),
            ]);

            return redirect()
                ->route('freelancer.index')
                ->with('error', 'Freelancer profile could not be loaded.');
        }

        $profile = $profileResponse->json();
        $freelancerUser = $profile['result']['user'] ?? $profile['result'] ?? null;

        if (!$freelancerUser) {
            return redirect()
                ->route('freelancer.index')
                ->with('error', 'Invalid Freelancer profile data.');
        }

        $freelancerUserId = $freelancerUser['id'] ?? null;
        $username = $freelancerUser['username'] ?? $freelancerUser['public_name'] ?? 'FreelancerUser';
        $email = $freelancerUser['email'] ?? $username . '@freelancer.local';

        // 6. Check for Existing Freelancer Connection (Duplicate Prevention)
        $existingConnection = FreelancerConnection::where('freelancer_user_id', $freelancerUserId)->first();

        if ($existingConnection) {
            $linkedUser = $existingConnection->user;

            // Check if the linked user is banned
            if ($linkedUser && $linkedUser->is_banned) {
                if (Auth::check()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return redirect()
                    ->route('login')
                    ->withErrors(['login' => 'Your account has been banned. Reason: ' . ($linkedUser->ban_reason ?? 'Contact support.')]);
            }

            // This Freelancer account is already linked to a user
            if (Auth::check() && Auth::id() !== $existingConnection->user_id) {
                // Logged-in user is trying to link a Freelancer account that belongs to another user
                return redirect()
                    ->route('freelancer.index')
                    ->with('error', 'This Freelancer account is already connected to another user account.');
            }

            // Update tokens on the existing connection
            $existingConnection->update([
                'access_token'     => $accessToken,
                'refresh_token'    => $refreshToken,
                'token_expires_at' => isset($tokenData['expires_in'])
                                      ? now()->addSeconds($tokenData['expires_in'])
                                      : null,
            ]);

            // Log in as the existing linked user (if not already logged in)
            if (!Auth::check()) {
                Auth::login($existingConnection->user, true);
                $request->session()->regenerate();
            }

            return redirect()
                ->route('freelancer.index')
                ->with('success', 'Freelancer account connected and logged in successfully.');
        }

        // 7. New Freelancer Connection — Find or Create Local User
        $user = Auth::user();
        $isNewUser = false;

        if (!$user) {
            // Check if a user with this email already exists
            $user = User::where('email', $email)->first();

            if ($user && $user->is_banned) {
                return redirect()
                    ->route('login')
                    ->withErrors(['login' => 'Your account has been banned. Reason: ' . ($user->ban_reason ?? 'Contact support.')]);
            }

            if (!$user) {
                // Create new user account (registration via Freelancer API)
                $user = User::create([
                    'name'     => $username,
                    'username' => $username,
                    'email'    => $email,
                    'password' => Hash::make(Str::random(64)), // Temporary random password
                ]);
                $isNewUser = true;
            }

            Auth::login($user, true);
            $request->session()->regenerate();
        }

        // 8. Store Freelancer Connection
        FreelancerConnection::create([
            'user_id'            => $user->id,
            'freelancer_user_id' => $freelancerUserId,
            'username'           => $username,
            'access_token'       => $accessToken,
            'refresh_token'      => $refreshToken,
            'token_expires_at'   => isset($tokenData['expires_in'])
                                    ? now()->addSeconds($tokenData['expires_in'])
                                    : null,
        ]);

        // 9. New users must set their password
        if ($isNewUser) {
            $request->session()->put('needs_password_setup', true);

            return redirect()
                ->route('password.set')
                ->with('success', 'Freelancer account connected! Please set your password to log in directly next time.');
        }

        return redirect()
            ->route('freelancer.index')
            ->with('success', 'Freelancer account connected successfully.');
    }

    /**
     * Disconnect the Freelancer account.
     */
    public function disconnect(Request $request)
    {
        if (Auth::check()) {
            FreelancerConnection::where('user_id', Auth::id())->delete();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('freelancer.index')
            ->with('success', 'Freelancer account disconnected successfully.');
    }
}