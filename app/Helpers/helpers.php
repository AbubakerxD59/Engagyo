<?php

use App\Models\Menu;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Models\Package;
use App\Models\DomainUtmCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

require_once __DIR__ . '/api_docs.php';

function site_logo()
{
    $logo = asset('assets/frontend/images/logo.png');
    return $logo;
}

function panel_logo()
{
    $logo = asset('assets/frontend/images/panel_logo.png');
    return $logo;
}

function site_company()
{
    // $company = "TRUE BREATHE MEDIA (SMC-PRIVATE) LIMITED";
    $company = "";
    return $company;
}

/**
 * Absolute logo URL for HTML emails (email clients cannot resolve relative asset() URLs).
 */
function email_logo_url(): string
{
    if ($custom = config('mail_branding.logo_url')) {
        return $custom;
    }

    return rtrim(config('app.url'), '/') . '/assets/frontend/images/logo.png';
}

function email_app_name(): string
{
    return (string) config('mail_branding.app_name', config('app.name', 'Engagyo'));
}

function email_brand_color(string $key = 'primary'): string
{
    return (string) config("mail_branding.colors.{$key}", '#4F46E5');
}

function no_image()
{
    $image = asset("assets/img/noimage.png");
    return $image;
}

function get_menus()
{
    $menus = Menu::with("features")->orderBy("display_order")->get();
    return $menus;
}

/**
 * Map Menu route to team member menu_id (string used in team_member_menus).
 * Returns null if menu is not in team member system (team members have no access).
 */
function get_team_member_menu_id($menu): ?string
{
    $map = [
        'panel.schedule' => 'schedule',
        'panel.automation' => 'automation',
        'panel.api-posts' => 'api-posts',
        'panel.accounts' => 'accounts',
        'panel.team-members.index' => 'team',
        'panel.api-keys' => 'api',
        'panel.url-tracking' => 'url-tracking',
        'panel.link-shortener' => 'link-shortener',
        'panel.analytics' => 'analytics',
    ];
    return $map[$menu->route ?? ''] ?? null;
}

function default_user_avatar($userId = null, $userName = null)
{
    // If user ID is provided, use it to consistently select an avatar
    if ($userId) {
        $avatarNumber = ($userId % 5) + 1; // Cycle through 5 default avatars (1-5)
        $avatarPath = "assets/img/avatars/default-{$avatarNumber}.png";

        // Check if file exists, if not use UI Avatars API
        if (file_exists(public_path($avatarPath))) {
            return asset($avatarPath);
        }
    }

    // Fallback: Use UI Avatars API to generate avatar based on name
    if ($userName) {
        $name = urlencode(trim($userName));
        if (!empty($name)) {
            $colors = ['0D8ABC', '7B9F35', 'E74C3C', '9B59B6', 'F39C12'];
            $colorIndex = $userId ? ($userId % count($colors)) : rand(0, count($colors) - 1);
            $backgroundColor = $colors[$colorIndex];
            return "https://ui-avatars.com/api/?name={$name}&size=128&background={$backgroundColor}&color=fff&bold=true";
        }
    }

    // Final fallback: use default-1.png
    $defaultPath = "assets/img/avatars/default-1.png";
    if (file_exists(public_path($defaultPath))) {
        return asset($defaultPath);
    }

    // Ultimate fallback: use no_image() if default avatars don't exist
    return no_image();
}

function saveImage($file)
{
    $fileName = strtotime(date('Y-m-d H:i:s')) . rand() . '.' . $file->extension();
    $path = public_path() . '/uploads//';
    $file->move($path, $fileName);

    return $fileName;
}

/**
 * Store an uploaded post image on S3 and return the object key.
 */
function savePostImageToS3($file): ?string
{
    if ($file === null) {
        return null;
    }

    return Storage::disk('s3')->putFile('images', $file) ?: null;
}

/**
 * Download a remote image and store it on S3 for a post.
 */
function savePostImageFromUrlToS3(string $url): ?string
{
    if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    $body = null;
    try {
        $response = Http::timeout(45)
            ->withOptions(['allow_redirects' => true])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ])
            ->get($url);
        if ($response->successful()) {
            $body = $response->body();
        }
    } catch (\Throwable $e) {
        $body = null;
    }

    if ($body === null || $body === '') {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 45,
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
    }

    if ($body === false || $body === '') {
        return null;
    }

    $urlPath = parse_url($url, PHP_URL_PATH) ?: '';
    $ext = strtolower((string) pathinfo($urlPath, PATHINFO_EXTENSION));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext);
    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }
    if (! in_array($ext, ['jpg', 'png', 'gif', 'webp'], true)) {
        $ext = 'jpg';
    }

    $key = 'images/'.strtotime(date('Y-m-d H:i:s')).random_int(1000, 999999).'.'.$ext;

    return Storage::disk('s3')->put($key, $body) ? $key : null;
}

function isS3StoragePath(string $path): bool
{
    return str_contains($path, '/')
        && ! str_starts_with($path, 'http://')
        && ! str_starts_with($path, 'https://');
}

/**
 * Resolve a post image URL from aws_link and/or legacy image column values.
 */
function resolveStoredPostImageUrl(?string $awsLink, ?string $image): string
{
    if (! empty($awsLink)) {
        return fetchFromS3($awsLink);
    }

    if ($image === null || $image === '') {
        return automation_placeholder_image();
    }

    $image = (string) $image;

    if (str_contains($image, 'http')) {
        return $image;
    }

    if (isS3StoragePath($image)) {
        return fetchFromS3($image);
    }

    return url(getImage('', $image));
}

/**
 * Apply uploaded/stored image values to post create/update payloads.
 */
function applyPostImageFields(array $data, ?string $storedPath): array
{
    if ($storedPath === null || $storedPath === '') {
        return $data;
    }

    if (str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
        $data['image'] = $storedPath;

        return $data;
    }

    if (isS3StoragePath($storedPath)) {
        $data['aws_link'] = $storedPath;
        $data['image'] = null;

        return $data;
    }

    $data['image'] = $storedPath;

    return $data;
}

function postUploadHasImage(array $upload): bool
{
    return ! empty($upload['aws_link']) || ! empty($upload['image']);
}

function postUploadImagePath(array $upload): ?string
{
    if (! empty($upload['aws_link'])) {
        return (string) $upload['aws_link'];
    }

    if (! empty($upload['image'])) {
        return (string) $upload['image'];
    }

    return null;
}

function saveToS3($file)
{
    $path = Storage::disk('s3')->putFile('videos', $file);
    return $path;
}

function removeFromS3($file)
{
    $disk = Storage::disk('s3');
    if ($disk->exists($file)) {
        $disk->delete($file);
    }
}

function fetchFromS3($path)
{
    $url = Storage::disk('s3')->url($path);
    return $url;
}

function saveImageFromUrl($url, $folder = 'images')
{
    if (empty($url) || ! is_string($url)) {
        return false;
    }
    $url = trim($url);
    if (! filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $body = null;
    try {
        $response = Http::timeout(45)
            ->withOptions(['allow_redirects' => true])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ])
            ->get($url);
        if ($response->successful()) {
            $body = $response->body();
        }
    } catch (\Throwable $e) {
        $body = null;
    }

    if ($body === null || $body === '') {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 45,
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
    }

    if ($body === false || $body === '') {
        return false;
    }

    $urlPath = parse_url($url, PHP_URL_PATH) ?: '';
    $ext = strtolower((string) pathinfo($urlPath, PATHINFO_EXTENSION));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext);
    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }
    if (! in_array($ext, ['jpg', 'png', 'gif', 'webp'], true)) {
        $ext = 'jpg';
    }

    $fileName = strtotime(date('Y-m-d H:i:s')).random_int(1000, 999999).'.'.$ext;
    $dir = public_path().'/'.trim($folder, '/').'/';
    if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
        return false;
    }
    $path = $dir.$fileName;
    if (file_put_contents($path, $body)) {
        return $fileName;
    }

    return false;
}

/**
 * Generate or download a placeholder image for automation posts
 * This is used when posts don't have images fetched yet (images are fetched after 5 minutes)
 * 
 * @param string $title Optional title text to include in the placeholder
 * @return string URL to the placeholder image
 */
function automation_placeholder_image($title = null)
{
    // Create images directory if it doesn't exist
    $imagesDir = public_path() . "/assets/img" . '/';
    if (!is_dir($imagesDir)) {
        if (!mkdir($imagesDir, 0755, true)) {
            return no_image();
        }
    }

    // Check if a generic placeholder already exists
    $placeholderFileName = 'automation-placeholder.png';
    $placeholderPath = $imagesDir . $placeholderFileName;

    // If placeholder doesn't exist, download/create it
    if (!file_exists($placeholderPath)) {
        // Use a reliable placeholder service with automation-related text
        $width = 1200;
        $height = 630; // Standard social media image size (1.91:1 ratio)
        $bgColor = '4A90E2'; // Nice blue color for automation/tech theme
        $textColor = 'FFFFFF';
        $text = urlencode('Automation Post');

        // Try dummyimage.com first (more reliable)
        $placeholderUrl = "https://dummyimage.com/{$width}x{$height}/{$bgColor}/{$textColor}.png&text={$text}";

        // Download the placeholder image
        $context = stream_context_create([
            "http" => [
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
                "timeout" => 10
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ]);

        $imageData = @file_get_contents($placeholderUrl, false, $context);

        // If first attempt fails, try placeholder.com
        if ($imageData === false || strlen($imageData) < 100) {
            $placeholderUrl = "https://via.placeholder.com/{$width}x{$height}/{$bgColor}/{$textColor}?text={$text}";
            $imageData = @file_get_contents($placeholderUrl, false, $context);
        }

        if ($imageData !== false && strlen($imageData) > 100) {
            // Verify it's actually an image (PNG files start with specific bytes or contain PNG signature)
            $isImage = substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n" ||
                strpos($imageData, 'PNG') !== false ||
                strpos($imageData, 'JFIF') !== false ||
                strpos($imageData, 'image') !== false;

            if ($isImage) {
                // Save the placeholder image
                if (file_put_contents($placeholderPath, $imageData)) {
                    return asset("images/{$placeholderFileName}");
                }
            }
        }

        // If all downloads fail, return no_image as fallback
        return no_image();
    }

    // Return the existing placeholder
    return asset("assets/img/{$placeholderFileName}");
}

function saveVideo($folderName, $file)
{
    $fileName = strtotime(date('Y-m-d H:i:s')) . '.' . $file->extension();
    $path = public_path() . '/uploads//' . $folderName;
    $file->move($path, $fileName);

    return $fileName;
}

function getImage($fileName, $parentFolder = 'uploads', $folderName = null)
{
    return asset($parentFolder . '/' . $folderName . '/' . $fileName);
}

function getVideo($folderName, $fileName)
{
    return asset('uploads/' . $folderName . '/' . $fileName);
}
function removeFile($file)
{
    $filePath = $file;
    if (Storage::disk('public')->exists($filePath)) {
        Storage::disk('public')->delete($filePath);
    }
}
function check_permission($permission)
{
    $user = auth()->user();
    $permissionsViaRole = $user->getPermissionsViaRoles()->pluck('name')->toArray();
    $permission = strtolower($permission);
    if (in_array($permission, $permissionsViaRole)) {
        return true;
    } else {
        return false;
    }
}
function get_total_roles()
{
    $roles = Role::get();
    return count($roles);
}
function get_total_users($type = null)
{
    $users = new User;
    if ($type == 'active') {
        $users = $users->where('status', '1');
    } else if ($type == 'inactive') {
        $users = $users->where('status', '0');
    }
    $users = $users->get();
    return count($users);
}

function get_status_view($type)
{
    if ($type == '1') {
        $div = '<span><i class="fa fa-check" style="color: green"></i></span>';
    } else if ($type == '0') {
        $div = '<span><i class="fa fa-times" style="color: red"></i></span>';
    }
    return $div;
}

function get_post_status($type)
{
    if ($type == '1') {
        $div = '<span class="badge badge-success">Published</span>';
    } else if ($type == '0') {
        $div = '<span class="badge badge-primary">Pending</span>';
    } else if ($type == '-1') {
        $div = '<span class="badge badge-danger">Failed</span>';
    }
    return $div;
}

function session_set($key, $value)
{
    Session::put($key, $value);
    return true;
}

function session_get($key)
{
    $session = Session::get($key);
    return $session;
}

function session_check($key)
{
    if (Session::has($key)) {
        if (!empty(Session::get($key))) {
            return true;
        }
    }
    return false;
}

function session_delete($key)
{
    Session::forget($key);
    return true;
}

function calculate_discount_price($base_price, $discount_price, $discount_type, $type)
{
    $response = 0;
    $discounted_price = 0;
    if ($discount_type == '%') {
        $discounted_price = ($base_price * $discount_price) / 100;
        $response = $base_price - $discounted_price;
    } else {
        $discounted_price = $discount_price;
        $response = $base_price - $discount_price;
    }
    if ($type == 0) {
        return $discounted_price;
    } elseif ($type == 1) {
        return $response;
    }
}

function getPreviousWeekDates($mode = 0)
{
    $dates = [];
    $todayTimestamp = time();
    $startDateTimestamp = strtotime('-6 days', $todayTimestamp);
    for ($timestamp = $startDateTimestamp; $timestamp <= $todayTimestamp; $timestamp += 86400) {
        if ($mode == 1) {
            $dates[] = date('Y-m-d 00:00:00', $timestamp);
        } else {
            $dates[] = date('m-d', $timestamp);
        }
    }
    if ($mode == 1) {
        return $dates;
    }
    $dates = '"' . implode('", "', $dates) . '"';
    return $dates;
}

function getPreviousWeeksUsers($type = 'users')
{
    $dates = getPreviousWeekDates(1);
    $users = [];
    foreach ($dates as $date) {
        $start_date = $date;
        $end_date = date('Y-m-d 23:59:59', strtotime($start_date));
        if ($type == 'users') {
            $user = User::whereBetween('created_at', [$start_date, $end_date])->get()->count();
        }
        $users[] = $user;
    }
    $users = '"' . implode('", "', $users) . '"';
    return $users;
}

function getCurrentMonthDates($mode = 0)
{
    $dates = [];
    $currentYear = date('Y');
    $currentMonth = date('m');
    $daysInMonth = date('t');

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $timestamp = strtotime("$currentYear-$currentMonth-$day");
        if ($mode == 1) {
            $dates[] = date('Y-m-d 00:00:00', $timestamp);
        } else {
            $dates[] = date('d-M', $timestamp);
        }
    }
    if ($mode == 1) {
        return $dates;
    }
    $dates = '"' . implode('", "', $dates) . '"';

    return $dates;
}

function getCurrentMonthUsers($type = 'users')
{
    $dates = getCurrentMonthDates(1);
    $users = [];
    foreach ($dates as $date) {
        $start_date = $date;
        $end_date = date('Y-m-d 23:59:59', strtotime($start_date));
        if ($type == 'users') {
            $user = User::whereBetween('created_at', [$start_date, $end_date])->get()->count();
        }
        $users[] = $user;
    }
    $users = '"' . implode('", "', $users) . '"';
    return $users;
}


function getPreviousMonths($mode = 0)
{
    $months = [];
    $currentMonth = new DateTime();
    for ($i = 7; $i >= 0; $i--) {
        $month = clone $currentMonth;
        $month->modify("-$i months");
        if ($mode == 1) {
            $months[] = $month->format("Y-m-1 00:00:00");
        } else {
            $months[] = $month->format("M-y");
        }
    }
    if ($mode == 1) {
        return $months;
    }
    $months = '"' . implode('", "', $months) . '"';
    return $months;
}

function getPreviousMonthsUsers($type = 'users')
{
    $dates = getPreviousMonths(1);
    $users = [];
    foreach ($dates as $date) {
        $start_date = $date;
        $end_date = date('Y-m-t 23:59:59', strtotime($start_date));
        if ($type == 'users') {
            $user = User::whereBetween('created_at', [$start_date, $end_date])->get()->count();
        }
        $users[] = $user;
    }
    $users = '"' . implode('", "', $users) . '"';
    return $users;
}

function check_features($features, $package_id)
{
    $packages = Package::where('id', '<>', $package_id)->get();
    $include = [];
    foreach ($packages as $package) {
        $package_features = $package->getFeatures();
        $diff = array_diff($package_features, $features);
        if (count($diff) <= 0) {
            array_push($include, $package->name);
            $features = array_diff($features, $package_features);
        }
    }
    if (count($include) > 0) {
        return [
            'include' => $include,
            'extra' => $features
        ];
    } else {
        return $features;
    }
}

function social_logo($type = null)
{
    $type = strtolower($type);
    if ($type == 'facebook') {
        $logo = asset("assets/img/icons/facebook-circle.svg");
    } elseif ($type == 'instagram') {
        $logo = asset("assets/img/icons/instagram.png");
    } elseif ($type == 'pinterest') {
        $logo = asset("assets/img/icons/pinterest-circle.svg");
    } elseif ($type == 'tiktok') {
        $logo = asset("assets/img/icons/tiktok-circle.svg");
    } elseif ($type == 'threads') {
        $logo = asset("assets/img/icons/threads-circle.svg");
    } elseif ($type == 'linkedin') {
        $logo = asset("assets/img/icons/linkedin-circle.svg");
    } elseif ($type == 'youtube') {
        $logo = asset("assets/img/icons/youtube-circle.svg");
    } else {
        $logo = "";
    }
    return $logo;
}

function social_icon($type = null)
{
    $type = strtolower($type);
    if ($type == 'facebook') {
        $logo = '<i class="fa-brands fa-facebook"></i>';
    } elseif ($type == 'instagram') {
        $logo = '<i class="fa-brands fa-instagram"></i>';
    } elseif ($type == 'pinterest') {
        $logo = '<i class="fa-brands fa-pinterest"></i>';
    } elseif ($type == 'tiktok') {
        $logo = '<i class="fa-brands fa-tiktok"></i>';
    } elseif ($type == 'threads') {
        $logo = '<img src="'.asset("assets/img/icons/threads-circle.svg").'" alt="Threads" style="width:14px;height:14px;object-fit:contain;">';
    } elseif ($type == 'linkedin') {
        $logo = '<i class="fa-brands fa-linkedin"></i>';
    } elseif ($type == 'youtube') {
        $logo = '<i class="fa-brands fa-youtube"></i>';
    } else {
        $logo = "";
    }
    return $logo;
}

function get_options($type)
{
    if ($type == "social_accounts") {
        $options = get_social_accounts();
    } else if ($type == "utm_keys") {
        $options = DomainUtmCode::$utm_keys;
    } else if ($type == "utm_values") {
        $options = DomainUtmCode::$utm_values;
    }

    return $options;
}

function get_social_accounts()
{
    $social = [
        "facebook",
        "pinterest",
        "tiktok",
        "threads",
        "linkedin",
        "youtube",
        "twitter"
    ];
    return $social;
}

function newDateTime($nextDate, $time)
{
    $nextDate = $nextDate . " " . $time;
    return $nextDate;
}

/**
 * Convert user's local datetime to UTC for storage.
 * @deprecated Use \App\Services\TimezoneService::toUtc() instead
 */
function user_local_datetime_to_utc(string $datetime, ?User $user = null): string
{
    return \App\Services\TimezoneService::toUtc($datetime, $user);
}

function loader()
{
    $loader = "<span class='preLoader' id='preLoader'></span>";
    return $loader;
}

function check_for($string, $find)
{
    $words = explode(' ', $string);
    if (in_array($find, $words)) {
        return true;
    }
    return false;
}

function getError($string)
{
    $start_marker = '"message":';
    $start_pos = strpos($string, $start_marker);
    if ($start_pos !== false) {
        $start_of_message = $start_pos + strlen($start_marker);
        $extracted_message = substr($string, $start_of_message);
        return $extracted_message;
    } else {
        return "Start marker not found.";
    }
}

function timeslots()
{
    $hours = 12;
    $minutes = 60;
    $timeslots = [];
    $formats = ["AM", "PM"];
    $hours = ['12', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'];
    $minutes = ["00", "10", "20", "30", "40", "50"];
    foreach ($formats as $format) {
        foreach ($hours as $hour) {
            foreach ($minutes as $minute) {
                $timeslots[] = $hour . ":" . $minute . " " . $format;
            }
        }
    }
    return $timeslots;
}

function pinterestDimensions()
{
    $response = array(
        "height" => array("1128", "900", "1000", "1024", "1349"),
        "width" => array("564", "700", "1500", "512", "759"),
    );
    return $response;
}


/**
 * Track feature usage for the authenticated user
 * 
 * @param string $featureKey The feature key
 * @param int $amount Amount to increment (default: 1)
 * @return array Result array with allowed, usage, limit, remaining, message
 */
function trackFeatureUsage($featureKey, $amount = 1)
{
    $user = auth()->user();
    if (!$user) {
        return [
            'allowed' => false,
            'usage' => 0,
            'limit' => null,
            'remaining' => null,
            'message' => 'User not authenticated.',
        ];
    }

    $service = app(\App\Services\FeatureUsageService::class);
    return $service->checkAndIncrement($user, $featureKey, $amount);
}

/**
 * Get feature usage statistics for the authenticated user
 * 
 * @param string $featureKey The feature key
 * @return array Usage statistics
 */
function getFeatureUsageStats($featureKey)
{
    $user = auth()->user();
    if (!$user) {
        return [];
    }

    $service = app(\App\Services\FeatureUsageService::class);
    return $service->getUsageStats($user, $featureKey);
}

/**
 * Check if user can use a feature (helper function)
 * 
 * @param string $featureKey The feature key
 * @return bool
 */
function canUseFeature($featureKey)
{
    $user = User::find(auth()->user()->id);
    if (!$user) {
        return false;
    }

    return $user->canUseFeature($featureKey);
}

/**
 * Get current feature usage count
 * 
 * @param string $featureKey The feature key
 * @return int
 */
function getFeatureUsage($featureKey)
{
    $user = User::find(auth()->user()->id);
    if (!$user) {
        return 0;
    }

    return $user->getFeatureUsage($featureKey);
}

/**
 * Cache-busting token for a public asset path.
 */
function asset_version_for_path(string $path): string
{
    static $versions = [];

    if (isset($versions[$path])) {
        return $versions[$path];
    }

    $configured = trim((string) config('app.asset_version', ''));
    $normalized = ltrim(str_replace('\\', '/', $path), '/');
    $fullPath = public_path($normalized);

    if (is_file($fullPath)) {
        $versions[$path] = (string) filemtime($fullPath);

        return $versions[$path];
    }

    $versions[$path] = $configured !== '' ? $configured : '1';

    return $versions[$path];
}

/**
 * Append a version query string to an asset URL.
 */
function append_asset_version(string $url, string $path): string
{
    $version = asset_version_for_path($path);

    if ($version === '') {
        return $url;
    }

    return $url.(str_contains($url, '?') ? '&' : '?').'v='.rawurlencode($version);
}
