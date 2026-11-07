<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'duration_days',
        'included_ai_bids',
        'badge',
        'description',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'            => 'decimal:2',
            'duration_days'    => 'integer',
            'included_ai_bids' => 'integer',
            'features'         => 'array',
            'is_active'        => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name) . '-' . Str::random(4);
            }
        });
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Dynamic Feature Helpers
    public function getHasAutobidAttribute(): bool
    {
        return (bool) ($this->features['has_autobid'] ?? false);
    }

    public function getFreeAiBidsAttribute(): int
    {
        return (int) ($this->features['free_ai_bids'] ?? $this->included_ai_bids ?? 0);
    }

    public function getPlaceBidsLimitAttribute(): int
    {
        return (int) ($this->features['place_bids_limit'] ?? 0);
    }

    public function getSkillsLimitAttribute(): int
    {
        return (int) ($this->features['skills_limit'] ?? 0);
    }

    public function getHasBidPriceMatrixAttribute(): bool
    {
        return (bool) ($this->features['has_bid_price_matrix'] ?? false);
    }

    public function getCountriesLimitAttribute(): int
    {
        return (int) ($this->features['countries_limit'] ?? 0);
    }

    public function getLanguagesLimitAttribute(): int
    {
        return (int) ($this->features['languages_limit'] ?? 0);
    }

    public function getCurrenciesLimitAttribute(): int
    {
        return (int) ($this->features['currencies_limit'] ?? 0);
    }
}
