<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'ai_bids_balance',
        'role',
        'is_banned',
        'ban_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function freelancerConnection()
    {
        return $this->hasOne(FreelancerConnection::class);
    }

    public function skills()
    {
        return $this->hasMany(Skill::class);
    }

    public function placedBids()
    {
        return $this->hasMany(PlacedBid::class);
    }

    public function skillSets()
    {
        return $this->hasMany(SkillSet::class);
    }

    public function bidTemplates()
    {
        return $this->hasMany(BidTemplate::class);
    }

    public function bidSetting()
    {
        return $this->hasOne(BidSetting::class);
    }

    public function aiBidSetting()
    {
        return $this->hasOne(AiBidSetting::class);
    }

    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest('id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function addAiBids(int $quantity): void
    {
        if ($quantity > 0) {
            $this->increment('ai_bids_balance', $quantity);
        }
    }

    public function deductAiBid(int $quantity = 1): bool
    {
        if ($this->ai_bids_balance >= $quantity) {
            $this->decrement('ai_bids_balance', $quantity);
            return true;
        }
        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get a specific feature quota/permission from user's active subscription plan.
     */
    public function getPlanFeature(string $key, $default = 0)
    {
        $sub = $this->activeSubscription;
        if (!$sub || !$sub->isValid()) {
            return $default;
        }
        return $sub->features_snapshot[$key] ?? $default;
    }

    /**
     * Check if user is allowed to use Auto-Bidding engine.
     */
    public function canUseAutobid(): bool
    {
        return $this->hasActiveSubscription() && (bool) $this->getPlanFeature('has_autobid', false);
    }

    /**
     * Check if user is allowed to use Bid Price Matrix settings.
     */
    public function canUsePriceMatrix(): bool
    {
        return $this->hasActiveSubscription() && (bool) $this->getPlanFeature('has_bid_price_matrix', false);
    }

    /**
     * Check if user has sufficient AI bids balance.
     */
    public function hasAiBidsBalance(int $quantity = 1): bool
    {
        return $this->ai_bids_balance >= $quantity;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'ai_bids_balance'   => 'integer',
        ];
    }
}

