<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiBidSetting extends Model
{
    use HasFactory;
    protected $table = 'ai_bid_settings';

    protected $fillable = [
        'user_id',
        'is_autobid_enabled',
        'status',
        'max_daily_bids',
        'min_budget',
        'max_budget',
        'preferred_currency',
        'project_type',
        'bid_calculation_mode',
        'delivery_days',
        'bidding_delay_seconds',
        'require_payment_verified',
        'min_client_rating',
        'min_client_reviews',
        'excluded_keywords',
        'last_scanned_at',
    ];

    protected $casts = [
        'is_autobid_enabled'       => 'boolean',
        'max_daily_bids'           => 'integer',
        'require_payment_verified' => 'boolean',
        'min_client_rating'        => 'float',
        'min_client_reviews'       => 'integer',
        'min_budget'               => 'integer',
        'max_budget'               => 'integer',
        'delivery_days'            => 'integer',
        'bidding_delay_seconds'    => 'integer',
        'excluded_keywords'        => 'array',
        'last_scanned_at'          => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
