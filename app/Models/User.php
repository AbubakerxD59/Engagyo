<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;
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
        'stripe_id',
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
        $boards = Board::with("pinterest", "timeslots")->get();
        // Facebook Pages
        $pages = Page::with("facebook", "timeslots")->get();
        // TikTok Accounts
        $tiktoks = Tiktok::with("timeslots")->get();
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

    /**
     * Get the user feature usages
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The user feature usages
     */
    public function userFeatureUsages()
    {
        return $this->hasMany(UserFeatureUsage::class);
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

    /**
     * Get the scheduled active accounts
     * 
     * @return \Illuminate\Database\Eloquent\Collection The scheduled active accounts
     */
    public function getScheduledActiveAccounts()
    {
        // Pinterest Boards
        $boards = Board::with("pinterest")->whereScheduledActive()->get();
        // Facebook Pages
        $pages = Page::with("facebook")->whereScheduledActive()->get();
        // TikTok Accounts
        $tiktoks = Tiktok::whereScheduledActive()->get();
        $accounts = collect();
        $accounts = $boards->concat($pages)->concat($tiktoks);
        return $accounts;
    }

    /**
     * Get the active user package
     * 
     * @return \App\Models\UserPackage The active user package
     */
    public function getActiveUserPackageAttribute()
    {
        // Check if relationship is already loaded
        if ($this->relationLoaded('userPackages')) {
            $package = $this->userPackages->where('is_active', true)->sortByDesc('id')->first();
            // Ensure package relationship is loaded
            if ($package && !$package->relationLoaded('package')) {
                $package->load('package');
            }
            return $package;
        }
        // If not loaded, query the database
        $package = $this->userPackages()->with('package')->active()->latest()->first();
        return $package;
    }

    /**
     * Get all features available for the user based on their package as an array
     * 
     * @return array Array of features with their details
     */
    public function getAvailableFeaturesArray()
    {
        $features = [];
        // Get the active user package
        $activePackage = $this->activeUserPackage;
        if (!$activePackage || !$activePackage->package) {
            return $features;
        }
        $package = $activePackage->package;
        // Get all enabled features from the package
        $packageFeatures = $package->features()
            ->wherePivot('is_enabled', true)
            ->where('is_active', true)
            ->get();
        foreach ($packageFeatures as $feature) {
            $features[] = [
                'id' => $feature->id,
                'key' => $feature->key,
                'name' => $feature->name,
                'type' => $feature->type,
                'description' => $feature->description ?? '',
                'default_value' => $feature->default_value,
                'limit_value' => $feature->pivot->limit_value,
                'is_enabled' => $feature->pivot->is_enabled,
                'is_unlimited' => $feature->pivot->is_unlimited ?? false,
                'usage_count' => $this->getFeatureUsage($feature->key),
            ];
        }
        return $features;
    }

    /**
     * Get feature usage count for a user by feature key
     * 
     * @param string $featureKey The feature key to check usage for
     * @return int The usage count for the feature (0 if not found)
     */
    public function getFeatureUsage($featureKey, $periodStart = null)
    {
        // Find the feature by key
        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return 0;
        }

        // Use current month if no period specified
        if (!$periodStart) {
            $periodStart = now()->startOfMonth();
        } else {
            $periodStart = $periodStart instanceof \Carbon\Carbon
                ? $periodStart->startOfMonth()
                : \Carbon\Carbon::parse($periodStart)->startOfMonth();
        }

        // Get the usage record for this user, feature, and current period
        $featureUsage = $this->userFeatureUsages()
            ->where('feature_id', $feature->id)
            ->where('period_start', $periodStart)
            ->where('is_archived', false)
            ->first();

        if ($featureUsage) {
            return $featureUsage->usage_count ?? 0;
        }

        return 0;
    }

    /**
     * Increment feature usage count for a user
     * 
     * @param string $featureKey The feature key
     * @param int $amount The amount to increment (default: 1)
     * @return bool Success status
     */
    public function incrementFeatureUsage($featureKey, $amount = 1)
    {
        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return false;
        }

        $periodStart = now()->startOfMonth();

        // Get or create usage record for current period
        $featureUsage = $this->userFeatureUsages()
            ->where('feature_id', $feature->id)
            ->where('period_start', $periodStart)
            ->where('is_archived', false)
            ->first();

        if (!$featureUsage) {
            $featureUsage = UserFeatureUsage::create([
                'user_id' => $this->id,
                'feature_id' => $feature->id,
                'usage_count' => 0,
                'is_unlimited' => false,
                'period_start' => $periodStart,
                'period_end' => $periodStart->copy()->endOfMonth(),
                'is_archived' => false,
            ]);
        }

        // Increment usage count (can be negative for decrement)
        $newCount = $featureUsage->usage_count + $amount;
        $featureUsage->update(['usage_count' => max(0, $newCount)]);

        return true;
    }

    /**
     * Decrement feature usage count for a user (for rollback scenarios)
     * 
     * @param string $featureKey The feature key
     * @param int $amount The amount to decrement (default: 1)
     * @return bool Success status
     */
    public function decrementFeatureUsage($featureKey, $amount = 1)
    {
        return $this->incrementFeatureUsage($featureKey, -$amount);
    }

    /**
     * Get historical feature usage for a specific period
     * 
     * @param string $featureKey The feature key
     * @param \Carbon\Carbon|string $periodStart The period start date
     * @return int The usage count for that period
     */
    public function getHistoricalFeatureUsage($featureKey, $periodStart)
    {
        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return 0;
        }

        if (!$periodStart instanceof \Carbon\Carbon) {
            $periodStart = \Carbon\Carbon::parse($periodStart)->startOfMonth();
        } else {
            $periodStart = $periodStart->startOfMonth();
        }

        $featureUsage = UserFeatureUsage::where('user_id', $this->id)
            ->where('feature_id', $feature->id)
            ->where('period_start', $periodStart)
            ->where('is_archived', true)
            ->first();

        return $featureUsage ? ($featureUsage->usage_count ?? 0) : 0;
    }

    /**
     * Get all historical usage records for a feature
     * 
     * @param string $featureKey The feature key
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeatureUsageHistory($featureKey)
    {
        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return collect();
        }

        return UserFeatureUsage::where('user_id', $this->id)
            ->where('feature_id', $feature->id)
            ->where('is_archived', true)
            ->orderBy('period_start', 'desc')
            ->get();
    }

    /**
     * Check if a user can use a feature by feature key
     * 
     * @param string $featureKey The feature key to check
     * @return bool True if the user can use the feature, false otherwise
     */
    public function canUseFeature($featureKey)
    {
        // If user is a team member, check team lead's package
        $effectiveUser = $this->isTeamMember() ? ($this->getTeamLead() ?? $this) : $this;

        // Check if user has an active package
        $activePackage = $effectiveUser->activeUserPackage;
        if (!$activePackage || !$activePackage->package) {
            return false;
        }

        $package = $activePackage->package;

        // Find the feature by key
        $feature = Feature::where('key', $featureKey)
            ->where('is_active', true)
            ->first();

        if (!$feature) {
            return false;
        }

        // Check if feature is available in the package and enabled
        $packageFeature = $package->features()
            ->where('features.id', $feature->id)
            ->wherePivot('is_enabled', true)
            ->first();

        if (!$packageFeature) {
            return false;
        }
        return true;
    }

    /**
     * Check if user has full access (lifetime package or no expiration)
     * 
     * @return bool True if user has full access, false otherwise
     */
    public function hasFullAccess()
    {
        // Get the active user package
        $activePackage = $this->activeUserPackage;

        if (!$activePackage || !$activePackage->package) {
            return false;
        }

        $package = $activePackage->package;

        // Check if package is lifetime
        if ($package->is_lifetime) {
            return true;
        }

        // Check if package has no expiration date (full access)
        // If expires_at is null or empty, user has full access
        if (empty($activePackage->expires_at)) {
            return true;
        }

        // If there's an expiration date, it's not full access
        return false;
    }

    /**
     * Team member relationships
     */
    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class, 'member_id');
    }

    public function teamMemberAsLead()
    {
        return $this->hasMany(TeamMember::class, 'team_lead_id');
    }

    public function activeTeamMembership()
    {
        return $this->teamMemberships()->active()->first();
    }

    public function isTeamMember()
    {
        return $this->teamMemberships()->active()->first();
    }

    public function getTeamLead()
    {
        $membership = $this->activeTeamMembership();
        return $membership ? $membership->teamLead : null;
    }

    public function isTeamLead()
    {
        return $this->teamMemberAsLead()->exists();
    }

    /**
     * Get the effective user for feature limits (team member uses team lead's limits)
     */
    public function getEffectiveUser()
    {
        if ($this->isTeamMember()) {
            return $this->getTeamLead();
        }
        return $this;
    }

    /**
     * Get feature limit for team member (considers team member overrides)
     */
    public function getTeamMemberFeatureLimit($featureKey)
    {
        if (!$this->isTeamMember()) {
            return null;
        }

        $membership = $this->activeTeamMembership();
        if (!$membership) {
            return null;
        }

        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return null;
        }

        $limit = TeamMemberFeatureLimit::where('team_member_id', $membership->id)
            ->where('feature_id', $feature->id)
            ->first();

        // If limit is set, return it (null means use team lead's limit)
        if ($limit) {
            if ($limit->is_unlimited) {
                return ['limit' => null, 'is_unlimited' => true];
            }
            return ['limit' => $limit->limit_value, 'is_unlimited' => false];
        }

        // Return null to indicate use team lead's limit
        return null;
    }

    /**
     * Check if team member has access to a menu item
     */
    public function hasMenuAccess(string $menuId): bool
    {
        // If user is not a team member, they have full access
        if (!$this->isTeamMember()) {
            return true;
        }

        $teamMember = $this->activeTeamMembership();
        if (!$teamMember) {
            return false;
        }

        // Load team member menus
        $teamMember->load('menus');

        // Check if team member has access to this menu item
        $hasMenuAccess = $teamMember->menus()
            ->where('menu_id', $menuId)
            ->exists();

        return $hasMenuAccess;
    }

    /**
     * Get board IDs from team_member_accounts for the current user
     * 
     * @return array Array of board IDs that the user has access to through team membership
     */
    public function getTeamMemberBoardIds(): array
    {
        if (!$this->isTeamMember()) {
            return [];
        }

        $teamMember = $this->activeTeamMembership();
        if (!$teamMember) {
            return [];
        }

        return TeamMemberAccount::where('team_member_id', $teamMember->id)
            ->where('account_type', 'board')
            ->pluck('account_id')
            ->toArray();
    }
}
