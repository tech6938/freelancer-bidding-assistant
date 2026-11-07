<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = [
        'freelancer_job_id',
        'user_id',
        'name',
        'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function skillSets()
    {
        return $this->belongsToMany(SkillSet::class, 'skill_set_skills', 'skill_id', 'skill_set_id')
                    ->withTimestamps();
    }
    public function templates()
    {
        return $this->belongsToMany(BidTemplate::class, 'bid_template_skills');
    }

    public function aiBidSetting()
    {
        return $this->hasOne(AiBidSetting::class, 'user_id', 'user_id');
    }
}
