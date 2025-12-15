<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\FeatureController;
use Illuminate\Support\Facades\Route;

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
        });

        // Features
        Route::resource('features', FeatureController::class)->except('show');
        Route::controller(FeatureController::class)->prefix('features/')->name('features.')->group(function () {
            Route::get('dataTable', 'dataTable')->name('dataTable');
        });
    });
});
