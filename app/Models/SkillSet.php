<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillSet extends Model
{
    protected $fillable = [
        'user_id',
        'name'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'skill_set_skills', 'skill_set_id', 'skill_id')
                    ->withTimestamps();
    }
}
