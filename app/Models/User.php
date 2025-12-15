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
        'phone_number',
        'city',
        'country',
        'address',
        'profile_pic',
        'status',
        'timezone_id',
        'rss_filters',
        'package_id',
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

    public function tiktok()
    {
        return $this->hasMany(Tiktok::class, 'user_id', 'id');
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

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'id');
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

    protected function nameLink(): Attribute
    {
        return Attribute::make(
            get: fn() => view('admin.users.dataTable.name_link', ['user' => $this])->render()
        );
    }

    /**
     * Get package status information for DataTable display
     */
    public function getPackageStatusInfo()
    {
        $info = [
            'package_name' => null,
            'expires_at' => null,
            'status' => null, // 'expired', 'expiring_soon', 'active', 'full_access'
            'badge_class' => null,
            'badge_text' => null,
        ];
        if ($this->activeUserPackage && $this->activeUserPackage->package) {
            $info['package_name'] = $this->activeUserPackage->package->name;

            if (!empty($this->activeUserPackage->expires_at)) {
                $expiresAt = $this->activeUserPackage->expires_at;
                $info['expires_at'] = $expiresAt;

                if ($expiresAt->isPast()) {
                    $info['status'] = 'expired';
                    $info['badge_class'] = 'badge-danger';
                    $info['badge_text'] = 'Expired';
                } elseif ($expiresAt->diffInDays(now()) <= 7) {
                    $info['status'] = 'expiring_soon';
                    $info['badge_class'] = 'badge-warning';
                    $info['badge_text'] = 'Expiring Soon';
                } else {
                    $info['status'] = 'active';
                    $info['badge_class'] = 'badge-success';
                    $info['badge_text'] = 'Active';
                }
            } else {
                $info['status'] = 'full_access';
                $info['badge_class'] = 'badge-info';
                $info['badge_text'] = 'Full Access';
            }
        } elseif ($this->package) {
            $info['package_name'] = $this->package->name;
        }

        return $info;
    }

    protected function packageHtml(): Attribute
    {
        return Attribute::make(
            get: fn() => view('admin.users.dataTable.package', ['user' => $this])->render()
        );
    }

    protected function statusSpan(): Attribute
    {
        return Attribute::make(
            get: fn() => view('admin.users.dataTable.status', ['user' => $this])->render()
        );
    }

    protected function roleName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getRole()
        );
    }
    public function getActionAttribute()
    {
        return view("admin.users.dataTable.action", ['user' => $this])->render();
    }

    public function getAccounts()
    {
        // Pinterest Boards
        $boards = $this->boards()->with("pinterest", "timeslots")->get();
        // Facebook Pages
        $pages = $this->pages()->with("facebook", "timeslots")->get();
        // TikTok Accounts
        $tiktoks = $this->tiktok()->with("timeslots")->get();
        $accounts = collect();
        $accounts = $boards->concat($pages)->concat($tiktoks);
        return $accounts;
    }

    public function getDomains($id)
    {
        $domains = $this->domains()->where("account_id", $id)->get();
        return count($domains) > 0 ? $domains : [];
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class, 'user_id', 'id');
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }

    public function getActiveUserPackageAttribute()
    {
        // Check if relationship is already loaded
        if ($this->relationLoaded('userPackages')) {
            $package = $this->userPackages->where('is_active', true)->sortByDesc('id')->first();
            if (!$package) {
                $package = $this->userPackages->sortByDesc('id')->first();
            }
            // Ensure package relationship is loaded
            if ($package && !$package->relationLoaded('package')) {
                $package->load('package');
            }
            return $package;
        }

        // If not loaded, query the database
        $package = $this->userPackages()->with('package')->where('is_active', true)->latest()->first();
        if (!$package) {
            $package = $this->userPackages()->with('package')->latest()->first();
        }
        return $package;
    }

    /**
     * Get the system notifications that this user has read
     */
    public function readSystemNotifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_user')
            ->withPivot('is_read', 'read_at')
            ->withTimestamps()
            ->wherePivot('is_read', true);
    }

    public function getScheduledActiveAccounts()
    {
        // Pinterest Boards
        $boards = $this->boards()->with("pinterest")->whereScheduledActive()->get();
        // Facebook Pages
        $pages = $this->pages()->with("facebook")->whereScheduledActive()->get();
        // TikTok Accounts
        $tiktoks = $this->tiktok()->whereScheduledActive()->get();
        $accounts = collect();
        $accounts = $boards->concat($pages)->concat($tiktoks);
        return $accounts;
    }

    /**
     * Get features available to the user from their active package
     */
    public function getPackageFeatures()
    {
        $features = collect();

        if ($this->activeUserPackage && $this->activeUserPackage->package) {
            $package = $this->activeUserPackage->package;

            // Query features with pivot data
            $features = $package->features()
                ->wherePivot('is_enabled', true)
                ->get()
                ->map(function ($feature) {
                    return [
                        'id' => $feature->id,
                        'key' => $feature->key ?? null,
                        'name' => $feature->name ?? 'Unknown',
                        'type' => $feature->type ?? 'boolean',
                        'description' => $feature->description ?? '',
                        'limit_value' => $feature->pivot->limit_value ?? null,
                        'is_enabled' => $feature->pivot->is_enabled ?? false,
                        'is_unlimited' => $feature->pivot->is_unlimited ?? false,
                    ];
                })
                ->filter(function ($feature) {
                    // Filter out features without a key
                    return !empty($feature['key']);
                })
                ->values();
        }

        return $features;
    }

    /**
     * Get usage count for a specific feature by key
     */
    public function getFeatureUsage($featureKey)
    {
        if (empty($featureKey)) {
            return 0;
        }

        switch ($featureKey) {
            case 'social_accounts':
                // Count all social accounts: Facebook pages + Pinterest boards + TikTok accounts
                try {
                    $facebookCount = $this->pages()->count();
                    $pinterestCount = $this->boards()->count();
                    $tiktokCount = $this->tiktok()->count();
                    return $facebookCount + $pinterestCount + $tiktokCount;
                } catch (\Exception $e) {
                    return 0;
                }

            case 'scheduled_posts_per_account':
                // Count scheduled posts (posts with scheduled = 1)
                try {
                    return Post::where('user_id', $this->id)
                        ->where('scheduled', 1)
                        ->count();
                } catch (\Exception $e) {
                    return 0;
                }

            case 'rss_feed_automation':
                // Count RSS feed automations (domains)
                try {
                    return $this->domains()->count();
                } catch (\Exception $e) {
                    return 0;
                }

            case 'video_publishing':
                // Boolean feature - return 1 if user has any video posts capability
                // Check if user has any video posts
                try {
                    return \App\Models\Post::where('user_id', $this->id)
                        ->where('type', 'video')
                        ->count() > 0 ? 1 : 0;
                } catch (\Exception $e) {
                    return 0;
                }

            case 'api_keys':
                // Count API keys
                try {
                    return $this->apiKeys()->count();
                } catch (\Exception $e) {
                    return 0;
                }

            case 'api_access':
                // Boolean feature - return 1 if user has any active API keys
                try {
                    return $this->apiKeys()->where('is_active', true)->count() > 0 ? 1 : 0;
                } catch (\Exception $e) {
                    return 0;
                }

            default:
                return 0;
        }
    }

    /**
     * Get feature usage information with limits
     */
    public function getFeaturesWithUsage()
    {
        $packageFeatures = $this->getPackageFeatures();

        return $packageFeatures->map(function ($feature) {
            // Get usage count for the feature
            $featureKey = $feature['key'] ?? null;
            $usage = 0;

            if (!empty($featureKey)) {
                $usage = $this->getFeatureUsage($featureKey);
            }

            $limit = $feature['limit_value'] ?? null;
            $isUnlimited = $feature['is_unlimited'] ?? false;

            // Determine if feature is truly unlimited
            $isUnlimitedFeature = $isUnlimited || $feature['type'] === 'unlimited' || ($feature['type'] === 'numeric' && $limit === null);

            // Calculate usage percentage (only for limited features)
            $usagePercentage = 0;
            if (!$isUnlimitedFeature && $limit && $limit > 0) {
                $usagePercentage = round(($usage / $limit) * 100, 2);
            }

            // Check if over limit (only for limited features)
            $isOverLimit = false;
            if (!$isUnlimitedFeature && $limit && $limit > 0) {
                $isOverLimit = $usage > $limit;
            }

            return [
                'id' => $feature['id'] ?? null,
                'key' => $featureKey,
                'name' => $feature['name'] ?? 'Unknown',
                'type' => $feature['type'] ?? 'boolean',
                'description' => $feature['description'] ?? '',
                'limit' => $limit,
                'usage' => $usage,
                'is_unlimited' => $isUnlimitedFeature,
                'is_boolean' => ($feature['type'] ?? 'boolean') === 'boolean',
                'usage_percentage' => $usagePercentage,
                'is_over_limit' => $isOverLimit,
            ];
        });
    }
}
