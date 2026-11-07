<?php

namespace App\Http\Controllers;

use App\Models\BidPrice;
use App\Models\FreelancerConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BidPriceController extends Controller
{
    /**
     * Display the Bid Price settings layout.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->canUsePriceMatrix()) {
            return redirect()->route('purchase.index')
                ->with('error', '⚠️ Bid Price Matrix access requires an active subscription plan with Price Matrix enabled.');
        }
 
        $userId = $user->id;
        $connection = FreelancerConnection::where('user_id', $userId)->first();
        $baseUrl = rtrim(config('services.freelancer.api_url', 'https://www.freelancer.com/api'), '/');

        // 1. PROJECT TYPE (Default: fixed)
        $projectType = $request->get('type', 'fixed');
        if (!in_array($projectType, ['fixed', 'hourly'])) {
            $projectType = 'fixed';
        }

        // 2. SELECT CURRENCY (Dynamic via Freelancer API with 24h Cache)
        $currencies = Cache::remember('freelancer_api_currencies', 86400, function () use ($baseUrl, $connection) {
            try {
                if ($connection && !empty($connection->access_token)) {
                    $response = Http::withoutVerifying()
                        ->withHeaders([
                            'freelancer-oauth-v1' => $connection->access_token,
                            'Accept'              => 'application/json',
                        ])
                        ->timeout(10)
                        ->get($baseUrl . '/projects/0.1/currencies/');

                    if ($response->successful()) {
                        $raw = $response->json()['result']['currencies'] ?? [];
                        if (!empty($raw)) {
                            return collect($raw)->map(fn($c) => [
                                'id'   => $c['id'],
                                'code' => strtoupper($c['code']),
                                'name' => $c['name'],
                                'sign' => $c['sign'] ?? '$',
                            ])->sortBy('code')->values()->toArray();
                        }
                    }
                }
            } catch (\Exception $e) {
                logger()->error('Freelancer Currencies API Failed: ' . $e->getMessage());
            }

            // Fallback currencies if API fails
            return [
                ['id' => 1, 'code' => 'USD', 'name' => 'US Dollar', 'sign' => '$'],
                ['id' => 2, 'code' => 'EUR', 'name' => 'Euro', 'sign' => '€'],
                ['id' => 3, 'code' => 'GBP', 'name' => 'British Pound', 'sign' => '£'],
                ['id' => 4, 'code' => 'AUD', 'name' => 'Australian Dollar', 'sign' => 'A$'],
                ['id' => 5, 'code' => 'CAD', 'name' => 'Canadian Dollar', 'sign' => 'C$'],
                ['id' => 6, 'code' => 'INR', 'name' => 'Indian Rupee', 'sign' => '₹'],
            ];
        });

        // Current selected currency (Default: USD)
        $selectedCurrency = strtoupper($request->get('currency', 'USD'));
        
        // Find currency symbol for UI display
        $currencyMeta = collect($currencies)->firstWhere('code', $selectedCurrency);
        $currencySign = $currencyMeta['sign'] ?? '$';

        // 3. BUDGETS (Fetch tiers based on selected Project Type + Currency)
        $rawBudgets = [];
        $cacheKey = "freelancer_budgets_{$selectedCurrency}";

        $rawBudgets = Cache::remember($cacheKey, 86400, function () use ($baseUrl, $connection, $selectedCurrency) {
            try {
                if ($connection && !empty($connection->access_token)) {
                    $budgetResponse = Http::withoutVerifying()
                        ->withHeaders([
                            'freelancer-oauth-v1' => $connection->access_token,
                            'Accept'              => 'application/json',
                        ])
                        ->timeout(10)
                        ->get($baseUrl . '/projects/0.1/budgets/', [
                            'currency_codes[]' => $selectedCurrency,
                        ]);

                    if ($budgetResponse->successful()) {
                        $budgets = $budgetResponse->json()['result']['budgets'] ?? [];
                        if (!empty($budgets)) {
                            return $budgets;
                        }
                    }
                }
            } catch (\Exception $e) {
                logger()->error('Freelancer Budgets API Failed: ' . $e->getMessage());
            }

            return [];
        });

        // Fallback default tiers if API returned empty
        if (empty($rawBudgets)) {
            $rawBudgets = $this->getDefaultBudgets();
        }

        // Filter by selected project_type and attach deterministic ID
        $budgets = collect($rawBudgets)
            ->filter(fn($tier) => ($tier['project_type'] ?? 'fixed') === $projectType)
            ->values()
            ->map(function ($tier, $idx) use ($projectType) {
                $min = (float) ($tier['minimum'] ?? 0);
                $max = isset($tier['maximum']) && $tier['maximum'] !== '' && $tier['maximum'] !== null 
                    ? (float) $tier['maximum'] 
                    : null;
                
                // Deterministic integer tier ID for storage & lookup
                $tierId = (int) ($tier['id'] ?? ($projectType === 'hourly' ? ($min * 100 + 50000) : ($min > 0 ? (int) $min : ($idx + 1))));

                return [
                    'id'           => $tierId,
                    'name'         => $tier['name'] ?? 'Tier ' . ($idx + 1),
                    'minimum'      => $min,
                    'maximum'      => $max,
                    'project_type' => $projectType,
                ];
            })
            ->toArray();

        // 4. USER'S SAVED PRICES (Match with DB records)
        $savedPrices = BidPrice::where('user_id', $userId)
            ->where('project_type', $projectType)
            ->where('currency_code', $selectedCurrency)
            ->get()
            ->keyBy('budget_tier_id');

        return view('settings.bidprice', compact(
            'projectType',
            'currencies',
            'selectedCurrency',
            'currencySign',
            'budgets',
            'savedPrices'
        ));
    }

    /**
     * Save/Update user defined budget tier prices.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_type'           => 'required|in:fixed,hourly',
            'currency_code'          => 'required|string|max:10',
            'tiers'                  => 'required|array',
            'tiers.*.budget_tier_id' => 'required|numeric',
            'tiers.*.tier_name'      => 'nullable|string',
            'tiers.*.min_budget'     => 'required|numeric',
            'tiers.*.max_budget'     => 'nullable|numeric',
            'tiers.*.rule_type'      => 'required|in:min,avg,max,custom',
            'tiers.*.bid_amount'     => 'required|numeric|min:0.01',
        ]);

        $user        = Auth::user();
        if (!$user->canUsePriceMatrix()) {
            return redirect()->route('purchase.index')
                ->with('error', '⚠️ Bid Price Matrix access requires an active subscription plan with Price Matrix enabled.');
        }

        $userId      = $user->id;
        $projectType = $request->project_type;
        $currency    = strtoupper($request->currency_code);

        foreach ($request->tiers as $tier) {
            $minBudget = (float) $tier['min_budget'];
            $maxBudget = isset($tier['max_budget']) && $tier['max_budget'] !== '' ? (float) $tier['max_budget'] : null;

            BidPrice::updateOrCreate(
                [
                    'user_id'        => $userId,
                    'project_type'   => $projectType,
                    'currency_code'  => $currency,
                    'budget_tier_id' => $tier['budget_tier_id'],
                ],
                [
                    'tier_name'   => $tier['tier_name'] ?? null,
                    'min_budget'  => $minBudget,
                    'max_budget'  => $maxBudget,
                    'rule_type'   => $tier['rule_type'],
                    'bid_amount'  => (float) $tier['bid_amount'],
                ]
            );
        }

        return back()->with('success', "Price rules saved for {$currency} (" . ucfirst($projectType) . ')!');
    }

    /**
     * Fallback standard Freelancer budget tiers when API is unreachable.
     */
    private function getDefaultBudgets(): array
    {
        return [
            // Fixed
            ['minimum' => 10, 'maximum' => 30, 'name' => 'Micro Project', 'project_type' => 'fixed'],
            ['minimum' => 30, 'maximum' => 250, 'name' => 'Simple project', 'project_type' => 'fixed'],
            ['minimum' => 250, 'maximum' => 750, 'name' => 'Very small project', 'project_type' => 'fixed'],
            ['minimum' => 750, 'maximum' => 1500, 'name' => 'Small project', 'project_type' => 'fixed'],
            ['minimum' => 1500, 'maximum' => 3000, 'name' => 'Medium project', 'project_type' => 'fixed'],
            ['minimum' => 3000, 'maximum' => 5000, 'name' => 'Large project', 'project_type' => 'fixed'],
            ['minimum' => 5000, 'maximum' => 10000, 'name' => 'Larger project', 'project_type' => 'fixed'],
            ['minimum' => 10000, 'maximum' => 20000, 'name' => 'Very Large project', 'project_type' => 'fixed'],
            ['minimum' => 20000, 'maximum' => 50000, 'name' => 'Complex project', 'project_type' => 'fixed'],
            ['minimum' => 50000, 'maximum' => 100000, 'name' => 'Huge project', 'project_type' => 'fixed'],
            ['minimum' => 100000, 'maximum' => null, 'name' => 'Major project', 'project_type' => 'fixed'],

            // Hourly
            ['minimum' => 2, 'maximum' => 8, 'name' => 'Basic', 'project_type' => 'hourly'],
            ['minimum' => 8, 'maximum' => 15, 'name' => 'Moderate', 'project_type' => 'hourly'],
            ['minimum' => 15, 'maximum' => 25, 'name' => 'Standard', 'project_type' => 'hourly'],
            ['minimum' => 25, 'maximum' => 50, 'name' => 'Skilled', 'project_type' => 'hourly'],
            ['minimum' => 50, 'maximum' => null, 'name' => 'Expert', 'project_type' => 'hourly'],
        ];
    }
}
