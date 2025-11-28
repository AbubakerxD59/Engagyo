<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timeslot extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "account_id",
        "account_type",
        "timeslot",
        "type",
    ];
    public function getTimeAttribute()
    {
        $timeslot = $this->timeslot;
        return date("h:i A", strtotime($timeslot));
    }

    public function page()
    {
        return $this->belongsTo(Page::class, "account_id", "id");
    }

    public function board()
    {
        return $this->belongsTo(Board::class, "account_id", "id");
    }

    protected static function booted()
    {
        static::addGlobalScope(new UserScope);
    }
}
