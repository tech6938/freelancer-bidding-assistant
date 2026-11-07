<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'content',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills(){
        return $this->belongsToMany(Skill::class, 'bid_template_skills');
    }
}
