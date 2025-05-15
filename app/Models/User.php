<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PDO;

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
        'rss_filters',
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
        'rss_filters' => 'array'
    ];

    protected $appends = ['full_name'];

    static public $status_array = [
        "0" => "Inactive",
        "1" => "Active",
        "2" => "Pending"
    ];

    public function pinterest()
    {
        return $this->hasMany(Pinterest::class, 'user_id', 'id');
    }

    public function facebook()
    {
        return $this->hasMany(Facebook::class, 'user_id', 'id');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class, 'user_id', 'id');
    }

    public function boards()
    {
        return $this->hasMany(Board::class, 'user_id', 'id');
    }

    public function pages()
    {
        return $this->hasMany(Page::class, 'user_id', 'id');
    }

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
                $full_name = $this->first_name . ' ' . $this->last_name;
                return $full_name;
            }
        );
    }

    protected function profilePic(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $profile_pic = !empty($value) ? $value : no_image();
                return $profile_pic;
            }
        );
    }

    public function getRole()
    {
        $role = $this->roles()->first();
        return $role ? $role->name : '';
    }

    public function getAccounts()
    {
        // Pinterest Boards
        $boards = $this->boards()->get();
        // Facebook Pages
        $pages = $this->pages()->get();

        $accounts = $boards->merge($pages);
        return $accounts;
    }

    public function getDomains($id)
    {
        $domains = $this->domains()->where("account_id", $id)->get();
        return count($domains) > 0 ? $domains : [];
    }
}
