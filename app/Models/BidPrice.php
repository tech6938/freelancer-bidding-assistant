<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidPrice extends Model
{
    protected $table = 'bid_prices';
    protected $fillable = [
        'user_id',
        'project_type',
        'currency_code',
        'budget_tier_id',
        'tier_name',
        'min_budget',
        'max_budget',
        'rule_type',
        'bid_amount',
    ];
    protected $casts = [
        'min_budget' => 'float',
        'max_budget' => 'float',
        'bid_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate or lookup the user's configured bid price for a given project.
     *
     * @param \Illuminate\Support\Collection|null $userBidPrices
     * @param array $project
     * @return float
     */
    public static function getBidAmountForProject($userBidPrices, array $project): float
    {
        $minBudget = (float) ($project['budget']['minimum'] ?? $project['budget_min'] ?? 0);
        $projectType = (($project['type'] ?? '') === 'hourly' || !empty($project['is_hourly'])) ? 'hourly' : 'fixed';
        $currencyCode = strtoupper($project['currency']['code'] ?? $project['currency_code'] ?? 'USD');

        if ($userBidPrices && $userBidPrices->isNotEmpty()) {
            $matchingRule = $userBidPrices->filter(function ($rule) use ($projectType, $currencyCode, $minBudget) {
                if ($rule->project_type !== $projectType || strtoupper($rule->currency_code) !== $currencyCode) {
                    return false;
                }
                
                $ruleMin = (float) $rule->min_budget;
                $ruleMax = $rule->max_budget !== null && $rule->max_budget !== '' ? (float) $rule->max_budget : null;

                if ($minBudget >= $ruleMin) {
                    if ($ruleMax === null || $minBudget <= $ruleMax) {
                        return true;
                    }
                }
                return false;
            })->sortByDesc('min_budget')->first();

            if ($matchingRule && (float) $matchingRule->bid_amount > 0) {
                return round((float) $matchingRule->bid_amount, 2);
            }
        }

        // Default fallback to minimum budget or platform viable minimum
        if ($minBudget > 0) {
            return round($minBudget, 2);
        }

        return $projectType === 'hourly' ? 5.0 : 10.0;
    }
}
