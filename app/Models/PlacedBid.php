<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlacedBid extends Model
{
    protected $fillable = [
        'user_id',
        'freelancer_project_id',
        'bid_amount',
        'delivery_days',
        'proposal_text',
        'freelancer_bid_id',
        'bid_type',
        'matched_template_id',
        'skill_match_score',
        'auto_bid_reason',
    ];

    protected $casts = [
        'bid_amount'        => 'decimal:2',
        'delivery_days'     => 'integer',
        'skill_match_score' => 'decimal:4',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The bid template that was matched for this auto-bid.
     * Null for manual bids or when no template matched.
     */
    public function template()
    {
        return $this->belongsTo(BidTemplate::class, 'matched_template_id');
    }
}

