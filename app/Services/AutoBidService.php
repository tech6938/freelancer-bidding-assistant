<?php

namespace App\Services;

use App\Models\AiBidSetting;
use App\Models\BidPrice;
use App\Models\BidTemplate;
use App\Models\FreelancerConnection;
use App\Models\PlacedBid;
use App\Models\ProjectFilter;
use App\Models\Skill;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Core auto-bidding service.
 *
 * Handles template matching, bid calculation, proposal generation,
 * and filter evaluation for the automated bidding system.
 */
class AutoBidService
{
    /**
     * Find the best matching BidTemplate for a project based on skill overlap.
     *
     * Uses Jaccard similarity: |intersection| / |union|
     * Tiebreakers: higher total skill count, then more recent updated_at.
     *
     * @param int   $userId            The user's ID
     * @param array $projectSkillNames Lowercased, trimmed project skill names
     * @return array{template: BidTemplate|null, score: float, matched_skills: array}
     */
    public function findBestTemplate(int $userId, array $projectSkillNames): array
    {
        if (empty($projectSkillNames)) {
            return ['template' => null, 'score' => 0.0, 'matched_skills' => []];
        }

        // Cache user templates for 5 minutes to avoid repeated DB hits during batch processing
        $cacheKey = "user_{$userId}_active_templates";
        $templates = Cache::remember($cacheKey, 300, function () use ($userId) {
            return BidTemplate::where('user_id', $userId)
                ->where('is_active', true)
                ->with('skills')
                ->get();
        });

        if ($templates->isEmpty()) {
            return ['template' => null, 'score' => 0.0, 'matched_skills' => []];
        }

        $bestTemplate = null;
        $bestScore = 0.0;
        $bestMatchedSkills = [];

        $projectSkillSet = array_flip($projectSkillNames); // O(1) lookups

        foreach ($templates as $template) {
            $templateSkillNames = $template->skills
                ->pluck('name')
                ->map(fn($name) => strtolower(trim($name)))
                ->filter()
                ->toArray();

            if (empty($templateSkillNames)) {
                continue;
            }

            // Compute intersection
            $intersection = [];
            foreach ($templateSkillNames as $skillName) {
                if (isset($projectSkillSet[$skillName])) {
                    $intersection[] = $skillName;
                }
            }

            $intersectionCount = count($intersection);
            if ($intersectionCount === 0) {
                continue;
            }

            // Jaccard similarity = |A ∩ B| / |A ∪ B|
            $unionCount = count(array_unique(array_merge($projectSkillNames, $templateSkillNames)));
            $jaccardScore = $unionCount > 0 ? $intersectionCount / $unionCount : 0.0;

            // Tiebreaker: prefer templates with more skills (more specific)
            // then prefer more recently updated templates
            $isBetter = false;
            if ($jaccardScore > $bestScore) {
                $isBetter = true;
            } elseif ($jaccardScore === $bestScore && $jaccardScore > 0) {
                // Tiebreaker 1: more template skills = more specific
                $currentTemplateSkillCount = count($templateSkillNames);
                $bestTemplateSkillCount = $bestTemplate
                    ? $bestTemplate->skills->count()
                    : 0;

                if ($currentTemplateSkillCount > $bestTemplateSkillCount) {
                    $isBetter = true;
                } elseif ($currentTemplateSkillCount === $bestTemplateSkillCount) {
                    // Tiebreaker 2: more recent template wins
                    if (!$bestTemplate || $template->updated_at > $bestTemplate->updated_at) {
                        $isBetter = true;
                    }
                }
            }

            if ($isBetter) {
                $bestScore = $jaccardScore;
                $bestTemplate = $template;
                $bestMatchedSkills = $intersection;
            }
        }

        return [
            'template'       => $bestTemplate,
            'score'          => round($bestScore, 4),
            'matched_skills' => $bestMatchedSkills,
        ];
    }

    /**
     * Calculate the bid amount for a project based on user settings, statistics, and budget matrix.
     *
     * @param AiBidSetting $settings  User's auto-bid settings
     * @param array        $bidStats  Project bid statistics from API
     * @param array        $budget    Project budget {minimum, maximum}
     * @param array        $project   Optional full project data
     * @return float The calculated bid amount
     */
    public function calculateBidAmount(AiBidSetting $settings, array $bidStats, array $budget, array $project = []): float
    {
        $budgetMin = (float) ($budget['minimum'] ?? 0);
        $budgetMax = (float) ($budget['maximum'] ?? 0);
        $bidAvg    = (float) ($bidStats['bid_avg'] ?? 0);

        // 1. Check if user has a configured BidPrice matrix rule and active plan feature permission
        if (!empty($settings->user_id)) {
            $user = \App\Models\User::find($settings->user_id);
            if ($user && $user->canUsePriceMatrix()) {
                $userBidPrices = BidPrice::where('user_id', $settings->user_id)->get();
                if ($userBidPrices->isNotEmpty()) {
                    $projectType = ($project['type'] ?? '') === 'hourly' ? 'hourly' : 'fixed';
                    $currencyCode = strtoupper($project['currency']['code'] ?? 'USD');

                    $matrixPrice = BidPrice::getBidAmountForProject($userBidPrices, [
                        'budget'   => ['minimum' => $budgetMin, 'maximum' => $budgetMax],
                        'type'     => $projectType,
                        'currency' => ['code' => $currencyCode],
                    ]);

                    if ($matrixPrice > 0 && $matrixPrice !== $budgetMin) {
                        return max(10.0, round($matrixPrice, 2));
                    }
                }
            }
        }

        $amount = match ($settings->bid_calculation_mode) {
            'minimum' => $budgetMin > 0 ? $budgetMin : ($bidAvg > 0 ? $bidAvg * 0.8 : $settings->min_budget),
            'maximum' => $budgetMax > 0 ? $budgetMax : ($bidAvg > 0 ? $bidAvg * 1.2 : $settings->max_budget),
            default   => $bidAvg > 0 ? $bidAvg : (($budgetMin + $budgetMax) / 2), // 'average'
        };

        // Clamp to user's configured budget range
        $amount = max($settings->min_budget, min($settings->max_budget, $amount));

        // Ensure minimum viable bid (Freelancer.com minimum is $10 for fixed projects)
        return max(10.0, round($amount, 2));
    }

    /**
     * Build the final proposal text from a template and project data.
     *
     * @param BidTemplate $template    The selected template
     * @param array       $projectData Project information
     * @return string The final proposal text
     */
    public function buildProposal(BidTemplate $template, array $projectData): string
    {
        $rawPitch = $template->content;

        // Extract project info
        $title = $projectData['title'] ?? 'your project';
        $rawClientName = trim($projectData['client_name'] ?? '');

        // Validate client name
        $invalidNames = ['client', 'there', 'usd', 'eur', 'gbp', 'aud', 'cad', 'inr', 'pkr', '$', '€', '£', ''];
        $hasValidName = !empty($rawClientName) && !in_array(strtolower($rawClientName), $invalidNames);
        $clientName = $hasValidName ? $rawClientName : '';

        // Build skills display string
        $projectSkills = $projectData['skills'] ?? [];
        $skillsDisplay = !empty($projectSkills)
            ? implode(', ', array_map('ucwords', array_slice($projectSkills, 0, 4)))
            : 'the required technologies';

        // Replace placeholders
        $proposal = str_replace(
            ['[Project Title]', '[Matching Job Skills]'],
            [$title, $skillsDisplay],
            $rawPitch
        );

        // Handle client name placeholder
        if ($hasValidName) {
            $proposal = str_replace('[Client Name]', $clientName, $proposal);
        } else {
            $proposal = preg_replace('/Hi\s+\[Client Name\],?/i', 'Hi there,', $proposal);
            $proposal = preg_replace('/Hello\s+\[Client Name\],?/i', 'Hello,', $proposal);
            $proposal = str_replace('[Client Name]', 'there', $proposal);
        }

        return $proposal;
    }

    /**
     * Generate an AI-powered personalized proposal using Groq API.
     * Fallback to structured proposal template if API fails or key is missing.
     *
     * @param array $projectData Project data: title, description, client_name, skills
     * @return string Generated proposal pitch
     */
    public function generateAiProposal(array $projectData): string
    {
        $title       = trim($projectData['title'] ?? 'your project');
        $description = trim($projectData['description'] ?? 'No description provided');
        $rawClient   = trim($projectData['client_name'] ?? '');

        // Clean Client Name
        $invalidNames = ['client', 'there', 'usd', 'eur', 'gbp', 'aud', 'cad', 'inr', 'pkr', '$', '€', '£', ''];
        $isValidName  = !empty($rawClient) && !in_array(strtolower($rawClient), $invalidNames);
        $clientName   = $isValidName ? $rawClient : '';
        $greeting     = $isValidName ? "Hi {$clientName}," : "Hi there,";

        // Clean Skills
        $rawSkills = (array) ($projectData['skills'] ?? []);
        $skillsList = [];
        foreach ($rawSkills as $item) {
            if (is_array($item) && isset($item['name'])) {
                $skillsList[] = trim($item['name']);
            } elseif (is_string($item) && !in_array($item, ['$', '€', '£', 'USD', ''])) {
                $skillsList[] = trim($item);
            }
        }
        $skillsText = !empty($skillsList) ? implode(', ', array_slice($skillsList, 0, 4)) : 'the required technologies';

        // Groq API Key & Model
        $apiKey = trim(config('services.groq.api_key') ?: env('GROQ_API_KEY', ''));
        $model = config('services.groq.model') ?: env('GROQ_MODEL', 'openai/gpt-oss-20b');

        if (!empty($apiKey)) {
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
                    $generatedText = $result['choices'][0]['message']['content']
                        ?? $result['output_text']
                        ?? $result['output'][0]['content'][0]['text']
                        ?? null;

                    if (!empty($generatedText)) {
                        $clean = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $generatedText);
                        return trim($clean);
                    }
                } else {
                    logger()->warning('AutoBidService AI Generation fallback due to API response: ' . $response->body());
                }
            } catch (\Exception $e) {
                logger()->error('AutoBidService AI Proposal Generation Exception: ' . $e->getMessage());
            }
        }

        // Reliable fallback if AI is unavailable
        return $this->buildFallbackProposal($projectData);
    }

    /**
     * Build a generic fallback proposal when no template matches.
     *
     * @param array $projectData Project information
     * @return string
     */
    public function buildFallbackProposal(array $projectData): string
    {
        $title = $projectData['title'] ?? 'your project';
        $clientName = trim($projectData['client_name'] ?? '');
        $skills = $projectData['skills'] ?? [];

        $invalidNames = ['client', 'there', '', 'usd', 'eur'];
        $greeting = (!empty($clientName) && !in_array(strtolower($clientName), $invalidNames))
            ? "Hi {$clientName},"
            : 'Hi there,';

        $skillsText = !empty($skills)
            ? implode(', ', array_map('ucwords', array_slice($skills, 0, 4)))
            : 'the required technologies';

        return "{$greeting}\n\nI reviewed your project regarding '{$title}' and I am confident I can complete it with top quality using {$skillsText}.\n\nI ensure clean architecture, fast turnaround, and clear communication throughout the milestones.\n\nLet's connect in chat to discuss the details.\n\nBest regards,";
    }

    /**
     * Check if a project passes all user filters.
     *
     * @param AiBidSetting $settings User's auto-bid settings
     * @param array        $project  Project data from Freelancer API
     * @return array{passes: bool, reason: string}
     */
    public function passesFilters(AiBidSetting $settings, array $project): array
    {
        // 1. Check project status
        $status = strtolower($project['status'] ?? 'active');
        if (!empty($status) && $status !== 'active') {
            return ['passes' => false, 'reason' => "Project status is '{$status}', not active"];
        }

        // 2. Fetch User Project Filters (Countries, Languages, Currencies)
        $projectFilter = !empty($settings->user_id) 
            ? ProjectFilter::where('user_id', $settings->user_id)->first() 
            : null;

        // 3. Check Currencies Filter from ProjectFilter
        $projectCurrency = strtoupper($project['currency']['code'] ?? 'USD');
        if ($projectFilter && !empty($projectFilter->currencies)) {
            $allowedCurrencies = array_map('strtoupper', (array) $projectFilter->currencies);
            if (!in_array($projectCurrency, $allowedCurrencies)) {
                return ['passes' => false, 'reason' => "Currency '{$projectCurrency}' is not in allowed currencies filter"];
            }
        }

        // 4. Check Countries Filter from ProjectFilter
        if ($projectFilter && !empty($projectFilter->countries)) {
            $owner = $project['owner_info'] ?? $project['owner'] ?? [];
            $countryData = $owner['country'] ?? ($owner['location']['country'] ?? []);
            $countryName = is_array($countryData) ? ($countryData['name'] ?? '') : ($countryData ?? '');
            $countryCode = is_array($countryData) ? strtolower($countryData['code'] ?? '') : '';

            $allowedCountries = array_map(fn($c) => strtolower(trim($c)), (array) $projectFilter->countries);
            if (!empty($allowedCountries)) {
                $isCountryMatched = (!empty($countryName) && in_array(strtolower($countryName), $allowedCountries)) 
                    || (!empty($countryCode) && in_array($countryCode, $allowedCountries));

                if (!$isCountryMatched) {
                    return ['passes' => false, 'reason' => "Client country '{$countryName}' is not in allowed countries filter"];
                }
            }
        }

        // 5. Check Languages Filter from ProjectFilter
        if ($projectFilter && !empty($projectFilter->languages)) {
            $projectLang = strtolower($project['language'] ?? 'en');
            $allowedLanguages = array_map(fn($l) => strtolower(trim($l)), (array) $projectFilter->languages);

            if (!empty($allowedLanguages) && !in_array($projectLang, $allowedLanguages)) {
                return ['passes' => false, 'reason' => "Project language '{$projectLang}' is not in allowed languages filter"];
            }
        }

        // 6. Check budget range
        $budgetMin = (float) ($project['budget']['minimum'] ?? 0);
        $budgetMax = (float) ($project['budget']['maximum'] ?? 0);
        $projectBudget = $budgetMax > 0 ? $budgetMax : $budgetMin;

        if ($projectBudget > 0) {
            if ($projectBudget < $settings->min_budget) {
                return ['passes' => false, 'reason' => "Budget \${$projectBudget} below minimum \${$settings->min_budget}"];
            }
            if ($projectBudget > $settings->max_budget) {
                return ['passes' => false, 'reason' => "Budget \${$projectBudget} above maximum \${$settings->max_budget}"];
            }
        }

        // 7. Check project type
        if ($settings->project_type !== 'both') {
            $projectType = ($project['type'] ?? '') === 'hourly' ? 'hourly' : 'fixed';
            if ($projectType !== $settings->project_type) {
                return ['passes' => false, 'reason' => "Project type '{$projectType}' does not match filter '{$settings->project_type}'"];
            }
        }

        // 5. Check client payment verification
        if ($settings->require_payment_verified) {
            $ownerStatus = $project['owner_info']['status'] ?? $project['owner']['status'] ?? [];
            $isPaymentVerified = !empty($ownerStatus['payment_verified']);
            if (!$isPaymentVerified) {
                return ['passes' => false, 'reason' => 'Client payment not verified'];
            }
        }

        // 6. Check client rating
        if ($settings->min_client_rating > 0) {
            $empReputation = $project['owner_info']['employer_reputation']
                ?? $project['owner_info']['reputation']
                ?? $project['owner']['employer_reputation']
                ?? [];
            $entireHistory = $empReputation['entire_history'] ?? $empReputation;
            $clientRating = (float) ($entireHistory['overall'] ?? $entireHistory['rating'] ?? 0);

            if ($clientRating > 0 && $clientRating < $settings->min_client_rating) {
                return ['passes' => false, 'reason' => "Client rating {$clientRating} below minimum {$settings->min_client_rating}"];
            }
        }

        // 7. Check client reviews
        if ($settings->min_client_reviews > 0) {
            $empReputation = $project['owner_info']['employer_reputation']
                ?? $project['owner_info']['reputation']
                ?? $project['owner']['employer_reputation']
                ?? [];
            $entireHistory = $empReputation['entire_history'] ?? $empReputation;
            $clientReviews = (int) ($entireHistory['reviews'] ?? 0);

            if ($clientReviews < $settings->min_client_reviews) {
                return ['passes' => false, 'reason' => "Client reviews {$clientReviews} below minimum {$settings->min_client_reviews}"];
            }
        }

        // 8. Check excluded keywords
        $excludedKeywords = $settings->excluded_keywords ?? [];
        if (!empty($excludedKeywords)) {
            $title = strtolower($project['title'] ?? '');
            $description = strtolower($project['preview_description'] ?? $project['description'] ?? '');
            $searchText = $title . ' ' . $description;

            foreach ($excludedKeywords as $keyword) {
                $keyword = strtolower(trim($keyword));
                if (!empty($keyword) && str_contains($searchText, $keyword)) {
                    return ['passes' => false, 'reason' => "Excluded keyword '{$keyword}' found in project"];
                }
            }
        }

        return ['passes' => true, 'reason' => 'All filters passed'];
    }

    /**
     * Get the number of remaining bids for today.
     *
     * @param int $userId   The user's ID
     * @param int $maxDaily Maximum daily bid limit
     * @return int Remaining bids allowed today
     */
    /**
     * Get remaining daily bids for a user.
     *
     * @param int    $userId   The user's ID
     * @param int    $maxDaily Maximum daily bid limit
     * @param string $bidType  'auto' or 'manual'
     * @return int Remaining bids allowed today
     */
    public function getRemainingQuota(int $userId, int $maxDaily, string $bidType = 'auto'): int
    {
        $todayCount = PlacedBid::where('user_id', $userId)
            ->where('bid_type', $bidType)
            ->whereDate('created_at', today())
            ->count();

        return max(0, $maxDaily - $todayCount);
    }

    /**
     * Check if a bid has already been placed on a project.
     *
     * @param int $userId    The user's ID
     * @param int $projectId The Freelancer project ID
     * @return bool
     */
    public function hasAlreadyBid(int $userId, int $projectId): bool
    {
        return PlacedBid::where('user_id', $userId)
            ->where('freelancer_project_id', $projectId)
            ->exists();
    }

    /**
     * Fetch active projects from Freelancer API matching user's skills.
     *
     * @param FreelancerConnection $connection User's Freelancer connection
     * @param array                $skillIds   Freelancer job IDs to filter by
     * @param int                  $limit      Number of projects to fetch
     * @return array Raw project data from API
     */
    public function fetchProjects(FreelancerConnection $connection, array $skillIds, int $limit = 20): array
    {
        $baseUrl = rtrim(config('services.freelancer.api_url', 'https://www.freelancer.com/api'), '/');

        $queryParams = [
            'limit'                          => $limit,
            'offset'                         => 0,
            'sort_field'                     => 'time_updated',
            'job_details'                    => 'true',
            'user_details'                   => 'true',
            'user_reputation'                => 'true',
            'owner_info'                     => 'true',
            'user_status'                    => 'true',
            'user_employer_reputation'       => 'true',
            'user_employer_reputation_extra' => 'true',
            'compact'                        => 'false',
        ];

        if (!empty($skillIds)) {
            $queryParams['jobs'] = $skillIds;
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

            if ($response->successful()) {
                $data = $response->json()['result'] ?? [];
                $projects = $data['projects'] ?? [];
                $usersDict = $data['users'] ?? [];

                // Attach owner info to each project
                foreach ($projects as &$project) {
                    $ownerId = $project['owner_id'] ?? null;
                    if (empty($project['owner_info']) && isset($usersDict[$ownerId])) {
                        $project['owner_info'] = $usersDict[$ownerId];
                    }
                }

                return $projects;
            }

            logger()->error('AutoBid: Freelancer API error', [
                'status' => $response->status(),
                'body'   => Str::limit($response->body(), 500),
            ]);
        } catch (\Exception $e) {
            logger()->error('AutoBid: API exception - ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Place a bid on a project via the Freelancer API.
     *
     * @param FreelancerConnection $connection User's Freelancer connection
     * @param array                $payload    Bid payload
     * @return array{success: bool, data: array, error: string|null}
     */
    public function placeBidViaApi(FreelancerConnection $connection, array $payload): array
    {
        $baseUrl = rtrim(config('services.freelancer.api_url', 'https://www.freelancer.com/api'), '/');

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'freelancer-oauth-v1' => $connection->access_token,
                    'Content-Type'        => 'application/json',
                ])
                ->timeout(15)
                ->post($baseUrl . '/projects/0.1/bids/', $payload);

            if ($response->successful()) {
                return [
                    'success'        => true,
                    'data'           => $response->json()['result'] ?? [],
                    'error'          => null,
                    'is_limit_error' => false,
                ];
            }

            $statusCode = $response->status();
            $apiError = $response->json() ?? [];
            $errorMsg = $apiError['message']
                ?? $apiError['error_description']
                ?? 'Freelancer API rejected this bid.';
            $errorCode = $apiError['error_code'] ?? '';

            $isLimitError = $this->isBidLimitError($statusCode, $errorMsg, $errorCode);

            return [
                'success'        => false,
                'data'           => [],
                'error'          => $errorMsg,
                'is_limit_error' => $isLimitError,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['success' => false, 'data' => [], 'error' => 'Connection failed: ' . $e->getMessage(), 'is_limit_error' => false];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => [], 'error' => 'Exception: ' . $e->getMessage(), 'is_limit_error' => false];
        }
    }

    /**
     * Check if an API error response indicates a bid limit or quota was reached.
     */
    public function isBidLimitError(int $statusCode, string $errorMsg, string $errorCode = ''): bool
    {
        if ($statusCode === 429) {
            return true;
        }

        $lowerMsg = strtolower($errorMsg);
        $lowerCode = strtolower($errorCode);

        $limitKeywords = [
            'used all of your bids',
            'all of your bids',
            'all your bids',
            'used all',
            'bid limit',
            'limit reached',
            'insufficient bids',
            'insufficient bid',
            'no bids remaining',
            'no bids left',
            'no bids',
            'out of bids',
            'membership limit',
            'bids exhausted',
            'exceeded your bid limit',
            'exceeded bid limit',
            'daily limit',
            'monthly limit',
            'rate limit',
            'too many requests',
            'quota exceeded',
            'quota reached',
            'not enough bids',
            'zero bids',
            'buy more bids',
            'upgrade your membership',
        ];

        foreach ($limitKeywords as $keyword) {
            if (str_contains($lowerMsg, $keyword) || str_contains($lowerCode, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract normalized skill names from a project's jobs array.
     *
     * @param array $project Raw project data
     * @return array Lowercased, trimmed, unique skill names
     */
    public function extractProjectSkills(array $project): array
    {
        $jobs = $project['jobs'] ?? [];
        $skills = [];

        foreach ($jobs as $job) {
            $name = strtolower(trim($job['name'] ?? ''));
            if (!empty($name)) {
                $skills[] = $name;
            }
        }

        return array_values(array_unique($skills));
    }

    /**
     * Extract the client/owner username from project data.
     *
     * @param array $project Raw project data
     * @return string
     */
    public function extractClientName(array $project): string
    {
        return $project['owner_info']['username']
            ?? $project['owner']['username']
            ?? '';
    }

    /**
     * Invalidate the cached templates for a user.
     * Call this when templates are created/updated/deleted.
     *
     * @param int $userId
     * @return void
     */
    public function clearTemplateCache(int $userId): void
    {
        Cache::forget("user_{$userId}_active_templates");
    }

    /**
     * Fetch active projects globally across all tracked skills using an available connection.
     *
     * @param array    $skillIds Optional filter of freelancer job IDs
     * @param int      $limit    Number of projects to fetch (max 100)
     * @return array Raw projects list
     */
    public function fetchGlobalProjects(array $skillIds = [], int $limit = 50): array
    {
        // Pick an active connection from the pool
        $connection = FreelancerConnection::whereNotNull('access_token')
            ->where('access_token', '!=', '')
            ->inRandomOrder()
            ->first();

        if (!$connection) {
            logger()->error('AutoBidService: No valid Freelancer connection found for global project ingest.');
            return [];
        }

        return $this->fetchProjects($connection, $skillIds, $limit);
    }
}

