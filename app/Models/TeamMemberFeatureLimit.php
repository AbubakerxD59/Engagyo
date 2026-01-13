<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMemberFeatureLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_member_id',
        'feature_id',
        'limit_value',
        'is_unlimited',
    ];

    protected $casts = [
        'is_unlimited' => 'boolean',
    ];

    public function teamMember()
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}

