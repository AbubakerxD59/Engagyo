<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\PackageController;
use App\Models\Post;
use App\Services\PinterestService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get("/admin", [AuthController::class, 'redirect']);
// Admin Routes
Route::prefix("admin/")->name("admin.")->group(function () {
    //    Auth Routes
    Route::middleware(["guest"])->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('showLogin');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });
    // Panel Routes
    Route::middleware(['auth'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Users
        Route::resource('users', UserController::class)->except('show');
        Route::controller(UserController::class)->prefix('users/')->name('users.')->group(function () {
            Route::get('dataTable', 'dataTable')->name('dataTable');
        });

        //  Roles
        Route::resource('roles', RolesController::class)->except('show');
        Route::controller(RolesController::class)->prefix('roles/')->name('roles.')->group(function () {
            Route::get('dataTable', 'dataTable')->name('dataTable');
            Route::post('assign/permissions/{id?}', 'assignPermissions')->name('assign_permissions');
        });

        // Permissions
        Route::resource('permissions', PermissionController::class)->except('show');
        Route::controller(PermissionController::class)->prefix('permissions/')->name('permissions.')->group(function () {
            Route::get('dataTable', 'dataTable')->name('dataTable');
        });

        // Packages
        Route::resource('packages', PackageController::class)->except('show');
        Route::controller(PackageController::class)->prefix('packages/')->name('packages.')->group(function () {
            Route::get('dataTable', 'dataTable')->name('dataTable');
            Route::post('add-facility', 'addFacility')->name('add_facility');
        });

        // Features
        Route::resource('features', FeatureController::class)->except('show');
        Route::controller(FeatureController::class)->prefix('features/')->name('features.')->group(function () {
            Route::get('dataTable', 'dataTable')->name('dataTable');
        });

        // Promo Codes
        Route::resource('promo-code', PromoCodeController::class)->except('show');
        Route::controller(PromoCodeController::class)->prefix('promo-code/')->name('promo-code.')->group(function () {
            Route::get('dataTable', 'dataTable')->name('dataTable');
        });
    });
});

// General Routes
Route::name("general.")->controller(GeneralController::class)->group(function () {
    Route::get('preview/link', 'previewLink')->name("previewLink");
});

// Frontend routes
require __DIR__ . '/frontend.php';

// User Panel routes
require __DIR__ . '/user.php';

// clear log file
Route::get("clear/log", function () {
    clearLogFile();
});
// php info
Route::get("phpinfo", function () {
    phpinfo();
});

Route::get("pinterest/publish", function (PinterestService $pinterestService, Post $post) {

    info("pinterest:publish");
    $now = date("Y-m-d H:i");
    $posts = $post->notPublished()->past($now)->pinterest()->notSchedule()->get();
    foreach ($posts as $key => $post) {
        if ($post->status == "0") {
            $user = $post->user()->first();
            if ($user) {
                $board = $post->board()->userSearch($user->id)->first();
                if ($board) {
                    $pinterest = $board->pinterest()->userSearch($user->id)->first();
                    if ($pinterest) {
                        $access_token = $pinterest->access_token;
                        if (!$pinterest->validToken()) {
                            $token = $pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                            $access_token = $token["access_token"];
                        }
                        if ($post->type == "link") {
                            $postData = array(
                                "title" => $post->title,
                                "link" => $post->url,
                                "board_id" => (string) $post->account_id,
                                "media_source" => array(
                                    "source_type" => str_contains($post->image, "http") ? "image_url" : "image_base64",
                                    "url" => $post->image
                                )
                            );
                        } elseif ($post->type == "photo") {
                            $encoded_image = file_get_contents($post->image);
                            $encoded_image = base64_encode($encoded_image);
                            $postData = array(
                                "title" => $post->title,
                                "board_id" => (string) $post->account_id,
                                "media_source" => array(
                                    "source_type" => "image_base64",
                                    "content_type" => "image/jpeg",
                                    "data" => $encoded_image
                                )
                            );
                        } elseif ($post->type == "video") {
                            $postData = array(
                                "title" => $post->title,
                                "board_id" => (string) $post->account_id,
                                'video_key' => $post->video
                            );
                        }
                        info("cron type: " . $post->type);
                     $pinterestService->video($post->id, $postData, $access_token);
                    }
                }
            }
        }
    }
});
