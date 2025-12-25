<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\FacebookTestController;
use Illuminate\Support\Facades\Route;

Route::get("/admin", [AuthController::class, 'redirect']);
// Admin Routes
Route::prefix("admin/")->name("admin.")->group(function () {
    //    Auth Routes
    Route::middleware(["guest:admin", "redirect_if_user"])->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('showLogin');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });
    // Panel Routes
    Route::middleware(['auth.admin', 'redirect_if_user'])->group(function () {
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
        Route::resource('features', FeatureController::class)->only(['index']);
        Route::controller(FeatureController::class)->prefix('features/')->name('features.')->group(function () {
            Route::get('dataTable', 'dataTable')->name('dataTable');
        });

        // Promo Codes
        Route::resource('promo-codes', PromoCodeController::class)->except('show');
        Route::controller(PromoCodeController::class)->prefix('promo-codes/')->name('promo-codes.')->group(function () {
            Route::get('dataTable', 'datatable')->name('dataTable');
        });

        // Facebook Test Cases
        Route::resource('facebook-tests', FacebookTestController::class)->except(['show']);
        Route::controller(FacebookTestController::class)->prefix('facebook-tests/')->name('facebook-tests.')->group(function () {
            Route::get('show', 'show')->name('show');
            Route::get('dataTable', 'dataTable')->name('dataTable');
            Route::post('run', 'runTests')->name('run');
        });
    });
});
