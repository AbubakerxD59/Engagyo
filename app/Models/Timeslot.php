<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    public function getTimeAttribute(){
        $timeslot = $this->timeslot;
        return date("h:i A", strtotime($timeslot));
    }
}
