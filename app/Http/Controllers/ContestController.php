<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use App\Models\FreelancerConnection;
use App\Models\Skill;

class ContestController extends Controller
{
    /**
     * Display a fast, paginated, filterable list of active contests from Freelancer.com API.
     * Fully optimized for high speed, direct API delegation, and smart caching.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();
        $connection = $userId
            ? FreelancerConnection::where('user_id', $userId)->first()
            : null;

        $page   = max(1, (int) $request->get('page', 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        // 1. Read Filter Parameters
        $searchQuery      = trim((string) $request->get('query', ''));
        $minAvgPrice      = $request->filled('min_prize') ? (float) $request->get('min_prize') : null;
        $maxAvgPrice      = $request->filled('max_prize') ? (float) $request->get('max_prize') : null;
        $selectedUpgrades = array_filter((array) $request->get('upgrades', []));
        $selectedSkills   = array_filter((array) $request->get('skills', []));
        
        $sortField        = $request->get('sort_field', 'time_updated');
        $allowedSorts     = ['time_updated', 'entry_count', 'contest_enddate', 'contest_prize_usd'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'time_updated';
        }

        $reverseSort      = $request->boolean('reverse_sort', false);
        $showUser         = $request->has('show_user') ? $request->boolean('show_user') : true;
        $showEntries      = $request->has('show_entries') ? $request->boolean('show_entries') : true;

        $paginator    = null;
        $errorMessage = null;

        // Fast resolution of selected skill labels
        $selectedSkillItems = [];
        if (!empty($selectedSkills)) {
            foreach ($selectedSkills as $s) {
                if (is_numeric($s)) {
                    $skillName = Cache::remember('skill_name_' . $s, 86400, function () use ($s) {
                        $dbSkill = Skill::where('freelancer_job_id', $s)->first();
                        return $dbSkill ? $dbSkill->name : ('Skill #' . $s);
                    });
                    $selectedSkillItems[] = ['id' => (int) $s, 'name' => $skillName];
                } else {
                    $selectedSkillItems[] = ['id' => $s, 'name' => $s];
                }
            }
        }

        if ($connection && !empty($connection->access_token)) {
            $baseUrl  = rtrim(config('services.freelancer.api_url', 'https://www.freelancer.com/api'), '/');
            $endpoint = $baseUrl . '/contests/0.1/contests/active/';

            // Build optimized single-call API parameters
            $apiParams = [
                'limit'                => $limit,
                'offset'               => $offset,
                'sort_field'           => $sortField,
                'reverse_sort'         => $reverseSort ? 'true' : 'false',
                'entry_counts'         => 'true',
                'job_details'          => 'true',
                'upgrade_details'      => 'true',
                'user_details'         => 'true',
                'user_avatar'          => 'true',
                'user_country_details' => 'true',
                'compact'              => 'true',
            ];

            if (!empty($searchQuery)) {
                $apiParams['query'] = $searchQuery;
            }

            if ($minAvgPrice !== null) {
                $apiParams['min_avg_price'] = $minAvgPrice;
            }
            if ($maxAvgPrice !== null) {
                $apiParams['max_avg_price'] = $maxAvgPrice;
            }

            if (!empty($selectedUpgrades)) {
                $apiParams['contest_upgrades'] = array_values($selectedUpgrades);
            }

            $numericJobIds = array_values(array_filter(array_map('intval', $selectedSkills)));
            if (!empty($numericJobIds)) {
                $apiParams['jobs'] = $numericJobIds;
            }

            $queryString = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($apiParams));
            $fullUrl     = $endpoint . '?' . $queryString;
            $cacheKey    = 'fl_contests_turbo_' . md5($fullUrl . '_' . $connection->access_token);

            try {
                // Smart cache (90 seconds) makes page navigation and re-sorting instant (<10ms)
                $cachedResponse = Cache::remember($cacheKey, 90, function () use ($fullUrl, $connection) {
                    $res = Http::withoutVerifying()
                        ->withHeaders([
                            'freelancer-oauth-v1' => $connection->access_token,
                            'Accept'              => 'application/json',
                        ])
                        ->timeout(10)
                        ->get($fullUrl);

                    return $res->successful() ? $res->json() : null;
                });

                if ($cachedResponse && isset($cachedResponse['result'])) {
                    $result       = $cachedResponse['result'];
                    $rawContests  = $result['contests'] ?? [];
                    $usersDict    = $result['users'] ?? [];
                    $totalFromApi = $result['total_count'] ?? count($rawContests);

                    $processedContests = [];

                    foreach ($rawContests as $contest) {
                        $prizeAmount = (float) ($contest['prize'] ?? 0);

                        // Upgrades info
                        $contestUpgrades = [];
                        $type = strtolower($contest['type'] ?? '');
                        $upg  = $contest['upgrades'] ?? [];

                        if ($type === 'guaranteed' || !empty($upg['guaranteed']) || !empty($contest['is_guaranteed'])) {
                            $contestUpgrades[] = 'Guaranteed';
                        }
                        if (!empty($upg['featured']) || !empty($contest['is_featured'])) {
                            $contestUpgrades[] = 'Featured';
                        }
                        if (!empty($upg['sealed']) || !empty($contest['is_sealed'])) {
                            $contestUpgrades[] = 'Sealed';
                        }
                        if (!empty($upg['urgent']) || !empty($contest['is_urgent'])) {
                            $contestUpgrades[] = 'Urgent';
                        }
                        if (!empty($upg['topcontest']) || !empty($contest['is_top_contest'])) {
                            $contestUpgrades[] = 'Top Contest';
                        }
                        if (!empty($upg['nda']) || !empty($upg['NDA']) || !empty($contest['is_nda'])) {
                            $contestUpgrades[] = 'NDA';
                        }
                        if (!empty($upg['highlight']) || !empty($upg['highlighted'])) {
                            $contestUpgrades[] = 'Highlight';
                        }
                        if (!empty($upg['nonpublic'])) {
                            $contestUpgrades[] = 'Non-Public';
                        }

                        // Jobs / Skills
                        $skills = [];
                        foreach ($contest['jobs'] ?? [] as $job) {
                            if (!empty($job['name'])) {
                                $skills[] = [
                                    'id'   => $job['id'] ?? null,
                                    'name' => $job['name'],
                                ];
                            }
                        }

                        // Owner info
                        $ownerId    = $contest['owner_id'] ?? null;
                        $owner      = $usersDict[$ownerId] ?? ($contest['owner_info'] ?? []);
                        $holderName = $owner['display_name']
                            ?? $owner['public_name']
                            ?? $owner['username']
                            ?? 'Contest Client';

                        // Avatar
                        $avatar = $owner['avatar_large_cdn']
                            ?? $owner['avatar_cdn']
                            ?? $owner['avatar_large']
                            ?? $owner['avatar']
                            ?? null;

                        if ($avatar && str_starts_with($avatar, '//')) {
                            $avatar = 'https:' . $avatar;
                        } elseif ($avatar && str_starts_with($avatar, '/')) {
                            $avatar = 'https://cdn6.f-cdn.com' . $avatar;
                        }

                        if (!$avatar || str_contains($avatar, 'unknown.png')) {
                            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($holderName) . '&background=0d6efd&color=ffffff&size=100';
                        }

                        // Time left calculation
                        $timeEnded = $contest['time_ended'] ?? ($contest['time_closed'] ?? null);
                        $timeLeft  = $this->formatTimeRemaining($timeEnded);

                        // Currency
                        $currencyCode = $contest['currency']['code'] ?? 'USD';
                        $currencySign = $contest['currency']['sign'] ?? '$';

                        // SEO URL / Direct Link
                        $seoUrlPath = $contest['seo_url_new'] ?? $contest['seo_url'] ?? ('contest/' . $contest['id']);
                        $contestUrl = 'https://www.freelancer.com/' . ltrim($seoUrlPath, '/');

                        // Entry Count from entry_counts projection
                        $entryCount = (int) ($contest['entry_count'] ?? ($contest['entry_counts'] ?? 0));

                        $processedContests[] = [
                            'id'             => $contest['id'],
                            'title'          => $contest['title'] ?? 'Untitled Contest',
                            'description'    => $contest['description'] ?? '',
                            'prize'          => number_format($prizeAmount, 2),
                            'raw_prize'      => $prizeAmount,
                            'currency'       => $currencyCode,
                            'currency_sign'  => $currencySign,
                            'time_left'      => $timeLeft,
                            'time_ended'     => (int) $timeEnded,
                            'entries_count'  => $entryCount,
                            'holder_name'    => $holderName,
                            'avatar'         => $avatar,
                            'country'        => $owner['location']['country']['name'] ?? '',
                            'country_code'   => strtolower($owner['location']['country']['code'] ?? ''),
                            'upgrades'       => $contestUpgrades,
                            'skills'         => $skills,
                            'contest_url'    => $contestUrl,
                        ];
                    }

                    $paginator = new LengthAwarePaginator(
                        $processedContests,
                        $totalFromApi,
                        $limit,
                        $page,
                        ['path' => route('settings.contests'), 'query' => $request->query()]
                    );

                } else {
                    $errorMessage = 'Failed to fetch contests from Freelancer API.';
                }
            } catch (\Exception $e) {
                $errorMessage = 'Could not connect to Freelancer Contests API: ' . $e->getMessage();
                logger()->error('Freelancer Contests Exception: ' . $e->getMessage());
            }
        }

        // If no connection or API error, provide empty paginator
        if (!$paginator) {
            $paginator = new LengthAwarePaginator(
                [],
                0,
                $limit,
                $page,
                ['path' => route('settings.contests'), 'query' => $request->query()]
            );
        }

        return view('settings.contests', [
            'connection'         => $connection,
            'paginator'          => $paginator,
            'selectedSkills'     => $selectedSkills,
            'selectedSkillItems' => $selectedSkillItems,
            'selectedUpgrades'   => $selectedUpgrades,
            'searchQuery'        => $searchQuery,
            'minPrize'           => $minAvgPrice,
            'maxPrize'           => $maxAvgPrice,
            'sortField'          => $sortField,
            'reverseSort'        => $reverseSort,
            'showUser'           => $showUser,
            'showEntries'        => $showEntries,
            'errorMessage'       => $errorMessage,
        ]);
    }

    /**
     * Helper to format time left into a human-readable string.
     */
    private function formatTimeRemaining(?int $timeEnded): string
    {
        if (!$timeEnded) {
            return 'Active';
        }

        $diff = $timeEnded - time();
        if ($diff <= 0) {
            return 'Ended';
        }

        $days    = (int) floor($diff / 86400);
        $hours   = (int) floor(($diff % 86400) / 3600);
        $minutes = (int) floor(($diff % 3600) / 60);

        if ($days > 0) {
            return $days . ' ' . Str::plural('day', $days) . ($hours > 0 ? ' ' . $hours . ' ' . Str::plural('hr', $hours) : '') . ' remaining';
        }

        if ($hours > 0) {
            return $hours . ' ' . Str::plural('hour', $hours) . ($minutes > 0 ? ' ' . $minutes . ' ' . Str::plural('min', $minutes) : '') . ' remaining';
        }

        return max(1, $minutes) . ' ' . Str::plural('min', $minutes) . ' remaining';
    }
}