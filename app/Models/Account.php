<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "acc_id",
        "type",
        "status",
    ];

    static public $status_array = [
        '0' => 'Inactive',
        '1' => 'Active'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
