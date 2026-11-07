<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFilter extends Model
{
    protected $fillable = [
        'user_id',
        'countries',
        'languages',
        'currencies',
        'min_fixed_budget',
        'max_fixed_budget',
        'min_hourly_rate',
        'max_hourly_rate',
        'project_type',
        'min_client_rating',
        'min_client_reviews',
        'require_payment_verified',
        'require_identity_verified',
        'excluded_keywords',
    ];
    protected $casts = [
        'countries'                 => 'array',
        'languages'                 => 'array',
        'currencies'                => 'array',
        'excluded_keywords'         => 'array',
        'require_payment_verified'  => 'boolean',
        'require_identity_verified' => 'boolean',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
