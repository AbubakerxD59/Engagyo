<?php

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Models\Package;
use App\Models\Notification;
use Illuminate\Support\Facades\File;
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

function saveImageFromUrl($url)
{
    $image_info = pathinfo($url);
    if (isset($image_info["extension"])) {
        $fileName = strtotime(date('Y-m-d H:i:s')) . rand() . '.' . $image_info["extension"];
        $path = public_path() . "/images" . '/';
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                return false;
            }
        }
        $path .= $fileName;
        $imageData = @file_get_contents($url);
        if ($imageData === false) {
            return false;
        }
        if (file_put_contents($path, $imageData)) {
            return $fileName;
        } else {
            return false;
        }
    } else {
        $imageData = @file_get_contents($url);
        if ($imageData !== false) {
            $fileName = strtotime(date('Y-m-d H:i:s')) . rand() . '.png';
            $path = public_path() . "/images" . '/';
            $path .= $fileName;
            if (file_put_contents($path, $imageData)) {
                return $fileName;
            } else {
                return false;
            }
        }
        return false;
    }
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
        dd('File ' . $file . ' successfully deleted.');
    }
    dd('File ' . $file . ' not found.');
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
    } else {
        $logo = "";
    }
    return $logo;
}

function get_options($type)
{
    if ($type == "social_accounts") {
        $options = get_social_accounts();
    }
    return $options;
}

function get_social_accounts()
{
    $social = [
        "facebook",
        "pinterest",
        "tiktok",
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

function create_notification($user_id, $body, $modal)
{
    Notification::create([
        "user_id" => $user_id,
        "body" => json_encode($body),
        "modal" => $modal
    ]);
    return true;
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

    $endpoints[] = [
        "id" => "media-upload",
        "method" => "POST",
        "endpoint" => "/media/upload",
        "description" => "Upload media (image/video) to S3",
        "category" => "Media",
        "parameters" => [
            ["name" => "media", "type" => "file", "required" => true, "description" => "Image or video file to upload (max 100MB)"]
        ],
        "request" => [
            "headers" => [
                "Authorization" => "Bearer your_api_key_here",
                "Content-Type" => "multipart/form-data"
            ],
            "body" => [
                "media" => "(binary file)"
            ],
            "curl" => 'curl -X POST "{base_url}/media/upload" \\\n  -H "Authorization: Bearer your_api_key_here" \\\n  -F "media=@/path/to/image.jpg"'
        ],
        "response" => [
            "success" => true,
            "message" => "Media uploaded successfully",
            "data" => [
                "type" => "image",
                "url" => "https://s3.amazonaws.com/bucket/images/20251202_abc123.jpg",
                "filename" => "20251202120000_abc123xyz.jpg",
                "original_filename" => "my-photo.jpg",
                "size" => 1048576,
                "size_formatted" => "1 MB",
                "mimetype" => "image/jpeg",
                "extension" => "jpg",
                "path" => "images/20251202120000_abc123xyz.jpg"
            ]
        ],
        "notes" => "Supported formats: Images (jpeg, jpg, png, gif, webp, bmp), Videos (mp4, mpeg, mov, avi, wmv, webm, 3gp, flv). Maximum file size: 100MB."
    ];

    return $endpoints;
}
