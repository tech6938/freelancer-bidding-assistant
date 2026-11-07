<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'plan_name',
        'started_at',
        'expires_at',
        'status',
        'price_paid',
        'features_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'started_at'        => 'datetime',
            'expires_at'        => 'datetime',
            'price_paid'        => 'decimal:2',
            'features_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id')->withTrashed();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isFuture();
    }

    public function daysRemaining(): int
    {
        if ($this->expires_at === null) {
            return 9999;
        }

        if ($this->expires_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expires_at, false) + 1;
    }
}
