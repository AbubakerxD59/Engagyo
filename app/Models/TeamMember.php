<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_lead_id',
        'member_id',
        'email',
        'invitation_token',
        'status',
        'invited_at',
        'joined_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    public function teamLead()
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function menus()
    {
        return $this->hasMany(TeamMemberMenu::class);
    }

    public function featureLimits()
    {
        return $this->hasMany(TeamMemberFeatureLimit::class);
    }

    public function accounts()
    {
        return $this->hasMany(TeamMemberAccount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function generateInvitationToken()
    {
        $this->invitation_token = Str::random(64);
        $this->save();
        return $this->invitation_token;
    }
}

