<?php

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Models\Package;
use App\Models\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

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
    $company = "TRUE BREATHE MEDIA (SMC-PRIVATE) LIMITED";
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
        $logo = asset("assets/frontend/images/Icons/facebook-circle.svg");
    } elseif ($type == 'instagram') {
        $logo = asset("assets/frontend/images/Icons/instagram.png");
    } elseif ($type == 'pinterest') {
        $logo = asset("assets/frontend/images/Icons/pinterest-circle.svg");
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

function newDateTime($nextDate, $time)
{
    // if ($day) {
    //     $nextDate = date("Y-m-d", strtotime($nextDate . ' +' . $day . ' days'));
    // }
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

function clearLogFile()
{
    $logPath = storage_path('logs/laravel.log');

    if (File::exists($logPath)) {
        File::put($logPath, '');
        return "Log file cleared successfully!";
    }
    return "Log file not found.";
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
    $hours = 24;
    $minutes = 60;
    $timeslots = [];
    for ($i = 1; $i <= $hours; $i++) {
        for ($j = 0; $j < $minutes; $j += 10) {
            $j = $j ? $j : '0' . $j;
            $time = $i >= 10 ? $i . ':' . $j : "0" . $i . ':' . $j;
            $timeslots[] = $time;
        }
    }
    return $timeslots;
}
