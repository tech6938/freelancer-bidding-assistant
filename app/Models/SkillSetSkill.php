<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillSetSkill extends Model
{
    protected $fillable = [
        'skill_set_id',
        'skill_id'
    ];

    public function skillSet()
    {
        return $this->belongsTo(SkillSet::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
