<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreelancerConnection extends Model
{
    protected $fillable = [
        'user_id',
        'freelancer_user_id',
        'username',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $casts = [
         'access_token' => 'encrypted',
    'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
