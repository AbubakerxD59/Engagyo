<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMemberAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_member_id',
        'account_type',
        'account_id',
    ];

    public function teamMember()
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function getAccountAttribute()
    {
        switch ($this->account_type) {
            case 'page':
                return Page::find($this->account_id);
            case 'board':
                return Board::find($this->account_id);
            case 'tiktok':
                return Tiktok::find($this->account_id);
            default:
                return null;
        }
    }
}

