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

function getImage($folderName = null, $fileName, $parentFolder = 'uploads')
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
        $logo = '<i class="fa-brands fa-threads"></i>';
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

function getApiEndpoints()
{
    $endpoints = [];

    $endpoints[] = [
        "id" => "auth-test",
        "method" => "GET",
        "endpoint" => "/auth/test",
        "description" => "Test authentication",
        "category" => "Authentication",
        "parameters" => [],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here"
            ]
        ],
        "response" => [
            "success" => true,
            "message" => "Authentication successful",
            "data" => [
                "authenticated" => true,
                "user" => [
                    "id" => 1,
                    "email" => "user@example.com",
                    "name" => "John Doe"
                ],
                "api_key" => [
                    "id" => 1,
                    "last_used_at" => "2025-12-02T12:00:00+00:00"
                ]
            ]
        ]
    ];

    $endpoints[] = [
        "id" => "user-profile",
        "method" => "GET",
        "endpoint" => "/user/profile",
        "description" => "Get user profile",
        "category" => "User",
        "parameters" => [],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here"
            ]
        ],
        "response" => [
            "success" => true,
            "data" => [
                "user" => [
                    "id" => 1,
                    "first_name" => "John",
                    "last_name" => "Doe",
                    "full_name" => "John Doe",
                    "email" => "user@example.com",
                    "phone_number" => "+1234567890",
                    "city" => "New York",
                    "country" => "USA",
                    "address" => "123 Main St",
                    "timezone_id" => 1,
                    "created_at" => "2025-01-01T00:00:00+00:00"
                ]
            ]
        ]
    ];

    $endpoints[] = [
        "id" => "user-profile-update",
        "method" => "PUT",
        "endpoint" => "/user/profile",
        "description" => "Update user profile",
        "category" => "User",
        "parameters" => [
            ["name" => "first_name", "type" => "string", "required" => false, "description" => "User's first name"],
            ["name" => "last_name", "type" => "string", "required" => false, "description" => "User's last name"],
            ["name" => "phone_number", "type" => "string", "required" => false, "description" => "Phone number"],
            ["name" => "city", "type" => "string", "required" => false, "description" => "City"],
            ["name" => "country", "type" => "string", "required" => false, "description" => "Country"],
            ["name" => "address", "type" => "string", "required" => false, "description" => "Address"]
        ],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here",
                "Content-Type" => "application/json"
            ],
            "body" => [
                "first_name" => "John",
                "last_name" => "Smith",
                "city" => "Los Angeles"
            ]
        ],
        "response" => [
            "success" => true,
            "message" => "Profile updated successfully",
            "data" => [
                "user" => [
                    "id" => 1,
                    "first_name" => "John",
                    "last_name" => "Smith",
                    "full_name" => "John Smith",
                    "email" => "user@example.com",
                    "city" => "Los Angeles"
                ]
            ]
        ]
    ];

    $endpoints[] = [
        "id" => "user-stats",
        "method" => "GET",
        "endpoint" => "/user/stats",
        "description" => "Get user statistics",
        "category" => "User",
        "parameters" => [],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here"
            ]
        ],
        "response" => [
            "success" => true,
            "data" => [
                "stats" => [
                    "pinterest_accounts" => 2,
                    "facebook_accounts" => 1,
                    "boards" => 5,
                    "pages" => 3,
                    "domains" => 10,
                    "api_keys" => 2
                ]
            ]
        ]
    ];

    $endpoints[] = [
        "id" => "user-accounts",
        "method" => "GET",
        "endpoint" => "/user/accounts",
        "description" => "Get user connected accounts",
        "category" => "User",
        "parameters" => [],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here"
            ]
        ],
        "response" => [
            "success" => true,
            "data" => [
                "accounts" => [
                    "pinterest" => [
                        [
                            "_id" => "pin_123456",
                            "username" => "johndoe",
                            "type" => "pinterest",
                            "profile_image" => "https://example.com/image.jpg",
                            "created_at" => "2025-01-01T00:00:00+00:00"
                        ]
                    ],
                    "facebook" => [
                        [
                            "_id" => "fb_789012",
                            "name" => "John Doe",
                            "type" => "facebook",
                            "profile_image" => "https://example.com/fb-image.jpg",
                            "created_at" => "2025-01-01T00:00:00+00:00"
                        ]
                    ]
                ],
                "total" => 2
            ]
        ]
    ];

    $endpoints[] = [
        "id" => "user-boards",
        "method" => "GET",
        "endpoint" => "/user/boards",
        "description" => "Get user Pinterest boards",
        "category" => "User",
        "parameters" => [],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here"
            ]
        ],
        "response" => [
            "success" => true,
            "data" => [
                "boards" => [
                    [
                        "_id" => "board_123456",
                        "name" => "My Board",
                        "type" => "pinterest",
                        "pinterest_account" => [
                            "_id" => "pin_123456",
                            "name" => "johndoe",
                            "profile_image" => "https://example.com/image.jpg"
                        ],
                        "created_at" => "2025-01-01T00:00:00+00:00"
                    ]
                ],
                "total" => 1
            ]
        ]
    ];

    $endpoints[] = [
        "id" => "user-pages",
        "method" => "GET",
        "endpoint" => "/user/pages",
        "description" => "Get user Facebook pages",
        "category" => "User",
        "parameters" => [],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here"
            ]
        ],
        "response" => [
            "success" => true,
            "data" => [
                "pages" => [
                    [
                        "_id" => "page_123456",
                        "name" => "My Business Page",
                        "type" => "facebook",
                        "facebook_account" => [
                            "_id" => "fb_789012",
                            "name" => "John Doe",
                            "profile_image" => "https://example.com/fb-image.jpg"
                        ],
                        "created_at" => "2025-01-01T00:00:00+00:00"
                    ]
                ],
                "total" => 1
            ]
        ]
    ];

    $endpoints[] = [
        "id" => "user-domains",
        "method" => "GET",
        "endpoint" => "/user/domains",
        "description" => "Get user domains",
        "category" => "User",
        "parameters" => [],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here"
            ]
        ],
        "response" => [
            "success" => true,
            "data" => [
                "domains" => [
                    [
                        "_id" => 1,
                        "name" => "example.com",
                        "type" => "rss",
                        "category" => "blog",
                        "created_at" => "2025-01-01T00:00:00+00:00"
                    ]
                ],
                "total" => 1
            ]
        ]
    ];

    // Posts Endpoints
    $endpoints[] = [
        "id" => "posts-create",
        "method" => "POST",
        "endpoint" => "/posts",
        "description" => "Create a post (photo or link) to Facebook or Pinterest. Use publish_now=1 to publish immediately. Otherwise scheduling follows: optional scheduled_at, else the next free queue timeslot for that page/board, else 14:00 today (user timezone) or tomorrow if 14:00 has passed.",
        "category" => "Posts",
        "parameters" => [
            ["name" => "platform", "type" => "string", "required" => true, "description" => "Target platform: 'facebook' or 'pinterest'"],
            ["name" => "account_id", "type" => "string", "required" => true, "description" => "Page ID (Facebook) or Board ID (Pinterest)"],
            ["name" => "image_url", "type" => "string", "required" => true, "description" => "Publicly accessible URL of the image to post"],
            ["name" => "title", "type" => "string", "required" => true, "description" => "Post title/message (max 500 characters)"],
            ["name" => "description", "type" => "string", "required" => false, "description" => "Additional description (max 2000 characters)"],
            ["name" => "link", "type" => "string", "required" => false, "description" => "Destination URL for link posts"],
            ["name" => "publish_now", "type" => "integer", "required" => false, "description" => "Set to 1 to publish immediately. If 0 or omitted, scheduled_at and queue logic apply (publish_now takes precedence over scheduled_at when set to 1)."],
            ["name" => "scheduled_at", "type" => "datetime", "required" => false, "description" => "Used only when publish_now is not 1. Schedule for this date/time (must be after now; format e.g. Y-m-d H:i:s). Interpreted in the user's timezone then stored in UTC."]
        ],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here",
                "Content-Type" => "application/json"
            ],
            "body" => [
                "platform" => "facebook",
                "account_id" => "123456789012345",
                "image_url" => "https://example.com/images/my-photo.jpg",
                "title" => "Check out this amazing post!",
                "description" => "This is an optional description for the post.",
                "link" => "https://example.com/my-article",
                "scheduled_at" => "2025-12-25 10:00:00"
            ],
            "curl" => 'curl -X POST "{base_url}/posts" \\\n  -H "Authorization: Bearer your_api_key_here" \\\n  -H "Content-Type: application/json" \\\n  -d \'{"platform":"facebook","account_id":"123456789","image_url":"https://example.com/image.jpg","title":"My Post","publish_now":1}\''
        ],
        "response" => [
            "success" => true,
            "message" => "Post scheduled successfully for Dec 25, 2025 at 10:00 AM",
            "data" => [
                "post" => [
                    "id" => 123,
                    "platform" => "facebook",
                    "status" => "scheduled",
                    "type" => "link",
                    "scheduled_at" => "2025-12-25 10:00:00",
                    "created_at" => "2025-12-04T10:30:00+00:00"
                ],
                "account" => [
                    "type" => "facebook_page",
                    "page_id" => "123456789012345",
                    "name" => "My Business Page",
                    "profile_image" => "https://example.com/fb-profile.jpg"
                ]
            ]
        ],
        "notes" => "Supported platforms: facebook, pinterest. For Facebook, use page_id as account_id. For Pinterest, use board_id as account_id. Scheduling: (1) publish_now=1 → immediate publish, status 'publishing'. (2) Else if scheduled_at is set → schedule at that time, status 'scheduled'. (3) Else if the page/board has queue timeslots configured → next available slot among pending posts on that account. (4) Else default 14:00 local user time today, or tomorrow if 14:00 already passed. Omitting publish_now and scheduled_at no longer implies immediate publish; send publish_now=1 for that."
    ];

    $endpoints[] = [
        "id" => "posts-status",
        "method" => "GET",
        "endpoint" => "/posts/status/{id}",
        "description" => "Get post status by ID",
        "category" => "Posts",
        "parameters" => [
            ["name" => "id", "type" => "integer", "required" => true, "description" => "Post ID (returned from create endpoint)"]
        ],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here"
            ],
            "curl" => 'curl -X GET "{base_url}/posts/status/123" \\\n  -H "Authorization: Bearer your_api_key_here"'
        ],
        "response" => [
            "success" => true,
            "data" => [
                "post" => [
                    "id" => 123,
                    "platform" => "facebook",
                    "status" => "published",
                    "status_code" => 1,
                    "is_scheduled" => false,
                    "title" => "Check out this amazing post!",
                    "image" => "https://example.com/images/my-photo.jpg",
                    "post_id" => "fb_987654321",
                    "scheduled_at" => null,
                    "published_at" => "2025-12-04 10:30:05",
                    "created_at" => "2025-12-04T10:30:00+00:00"
                ],
                "account" => [
                    "type" => "facebook_page",
                    "page_id" => "123456789012345",
                    "name" => "My Business Page",
                    "profile_image" => "https://example.com/fb-profile.jpg"
                ]
            ]
        ],
        "notes" => "Status values: 'pending' (waiting to publish), 'scheduled' (scheduled for future), 'publishing' (being processed), 'published' (successfully posted), 'failed' (error occurred). If status is 'failed', an 'error' field will be included with the error message."
    ];

    // Video Endpoints
    $endpoints[] = [
        "id" => "videos-create",
        "method" => "POST",
        "endpoint" => "/posts/video",
        "description" => "Create a video post on Facebook or Pinterest. Same scheduling rules as POST /posts: publish_now=1 for immediate publish; otherwise scheduled_at, then queue timeslots, then default 14:00 (user timezone).",
        "category" => "Videos",
        "parameters" => [
            ["name" => "platform", "type" => "string", "required" => true, "description" => "Target platform: 'facebook' or 'pinterest'"],
            ["name" => "account_id", "type" => "string", "required" => true, "description" => "Page ID (Facebook) or Board ID (Pinterest)"],
            ["name" => "video_url", "type" => "string", "required" => true, "description" => "Publicly accessible URL of the video to post"],
            ["name" => "title", "type" => "string", "required" => true, "description" => "Video title/description (max 500 characters)"],
            ["name" => "description", "type" => "string", "required" => false, "description" => "Additional description (max 2000 characters)"],
            ["name" => "link", "type" => "string", "required" => false, "description" => "Optional. Destination URL for the video post"],
            ["name" => "publish_now", "type" => "integer", "required" => false, "description" => "Set to 1 to publish immediately. If 0 or omitted, scheduled_at and queue logic apply."],
            ["name" => "scheduled_at", "type" => "datetime", "required" => false, "description" => "Used only when publish_now is not 1. Schedule for this date/time (must be after now). Interpreted in the user's timezone."]
        ],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here",
                "Content-Type" => "application/json"
            ],
            "body" => [
                "platform" => "facebook",
                "account_id" => "123456789012345",
                "video_url" => "https://example.com/videos/my-video.mp4",
                "title" => "Check out this amazing video!",
                "description" => "This is an optional description for the video.",
                "link" => "https://example.com/my-article",
                "scheduled_at" => "2025-12-25 10:00:00"
            ],
            "curl" => 'curl -X POST "{base_url}/posts/video" \\\n  -H "Authorization: Bearer your_api_key_here" \\\n  -H "Content-Type: application/json" \\\n  -d \'{"platform":"facebook","account_id":"123456789","video_url":"https://example.com/video.mp4","title":"My Video","publish_now":1}\''
        ],
        "response" => [
            "success" => true,
            "message" => "Video scheduled successfully for Dec 25, 2025 at 10:00 AM",
            "data" => [
                "post" => [
                    "id" => 125,
                    "platform" => "facebook",
                    "status" => "scheduled",
                    "type" => "video",
                    "scheduled_at" => "2025-12-25 10:00:00",
                    "created_at" => "2025-12-04T10:30:00+00:00"
                ],
                "account" => [
                    "type" => "facebook_page",
                    "page_id" => "123456789012345",
                    "name" => "My Business Page",
                    "profile_image" => "https://example.com/fb-profile.jpg"
                ]
            ]
        ],
        "notes" => "Supported platforms: facebook, pinterest. For Facebook, use page_id as account_id. For Pinterest, use board_id as account_id. The video is downloaded from video_url (Google Drive share links supported the same way as Jogg AI), validated by Content-Type (e.g. video/mp4), stored on S3, then published using a public file URL. Supported types: mp4, mov, avi, mkv, webm (by MIME). Scheduling matches POST /posts: publish_now=1 → 'publishing'; else scheduled_at → 'scheduled'; else queue slot or default 14:00 local. Send publish_now=1 for immediate publish."
    ];

    return $endpoints;
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
