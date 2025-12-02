<?php

use App\Models\Post;
use App\Services\PostService;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;

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
// API Docs
require __DIR__ . '/api-docs.php';
// php info
Route::get("phpinfo", function () {
    phpinfo();
});
