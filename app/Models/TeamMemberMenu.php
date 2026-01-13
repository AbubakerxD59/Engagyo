<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMemberMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_member_id',
        'menu_id',
    ];

    public function teamMember()
    {
        return $this->belongsTo(TeamMember::class);
    }
}

