<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'whatsapp_number',
        'phone_number',
        'city',
        'country',
        'address',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'otp_expires_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['full_name'];

    static public $status_array = [
        "0" => "Inactive",
        "1" => "Active",
        "2" => "Pending"
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('first_name', 'like', "%{$value}%")->where('last_name', 'like', "%{$value}%")->orWhere('email', 'like', "%{$value}%");
    }

    public function scopeEmail($query, $value)
    {
        $query->where('email', 'like', "%{$value}%");
    }

    protected function Password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => bcrypt($value),
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $status = in_array($value, self::$status_array) ? self::$status_array[$value] : "Inactive";
                return $status;
            }
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $full_name = $this->first_name.' '.$this->last_name;
                return $full_name;
            }
        );
    }

    public function getRole()
    {
        $role = $this->roles()->first();
        return $role ? $role->name : '';
    }
}
