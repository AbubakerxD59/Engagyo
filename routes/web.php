<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Board;
use App\Models\Tiktok;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\ShortLinkRedirectController;
use Illuminate\Support\Facades\DB;

// Short link redirect (public)
Route::get('/s/{code}', ShortLinkRedirectController::class)->name('short.redirect')->where('code', '[a-zA-Z0-9]+');

// General Routes
Route::name("general.")->controller(GeneralController::class)->group(function () {
    Route::get('preview/link', 'previewLink')->name("previewLink");
    Route::post('shorten', 'shortenPublic')->name("shorten");
    Route::post('save-pending-url-tracking', 'savePendingUrlTracking')->name("savePendingUrlTracking");
    Route::get('set-intended-login', 'setIntendedAndShowLogin')->name("setIntendedAndShowLogin");
    Route::get('set-intended-register', 'setIntendedAndShowRegister')->name("setIntendedAndShowRegister");
    Route::get('url-tracking/after-auth', 'urlTrackingAfterAuth')->name("urlTrackingAfterAuth")
        ->middleware('auth:user');
});
// Admin Routes
require __DIR__ . '/admin.php';
// Payment Gateways routes
require __DIR__ . '/payment_gateways.php';
// Frontend routes
require __DIR__ . '/frontend.php';
// Redirects
require __DIR__ . '/redirect.php';
// User Panel routes
require __DIR__ . '/user.php';
// API Docs
require __DIR__ . '/api-docs.php';
