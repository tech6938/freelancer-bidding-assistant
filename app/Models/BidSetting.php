<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidSetting extends Model
{
    protected $fillable =[
        'user_id',
        'default_delivery_days',
        'milestone_percentage',
        'bid_delay_seconds',
        'max_daily_bids',
        'auto_retry_failed_bids',
    ];
    protected $casts =[
        'auto_retry_failed_bids' => 'boolean'
    ];
public function user()
    {
        return $this->belongsTo(User::class);
    }
}
