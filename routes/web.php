<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\PackageController;
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

Route::get('/', function () {
    return redirect(route('login'));
});

Route::group(['middleware' => 'guest'], function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('showLogin');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::middleware(['auth'])->prefix("admin/")->name("admin.")->group(function () {
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

// Strip routes
Route::name('frontend.')->group(function () {
    Route::get('buy-membership/{id?}', [HomeController::class, 'buyMembership'])->name('buy_memebership');
    Route::post('checkout', [HomeController::class, 'checkout'])->name('checkout');
    Route::get('success', [HomeController::class, 'success'])->name('checkout_success');
});

// Strip webhook route
// Route::stripeWebhooks('stripe-webhook');

// Frontend routes
require __DIR__ . '/frontend.php';
