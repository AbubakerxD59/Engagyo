<?php

use App\Http\Controllers\FrontEnd\FacebookController;
use App\Http\Controllers\FrontEnd\InstagramLoginController;
use App\Http\Controllers\FrontEnd\LinkedInAuthController;
use App\Http\Controllers\FrontEnd\LinkedInAuthTestController;
use App\Http\Controllers\FrontEnd\PinterestController;
use App\Http\Controllers\FrontEnd\ThreadsController;
use App\Http\Controllers\FrontEnd\TikTokController;
use Illuminate\Support\Facades\Route;

// Pinterest
Route::name('pinterest.')->controller(PinterestController::class)->group(function () {
    Route::get('pinterest-callback', 'pinterestCallback')->name('callback');
});

// Instagram
Route::name('instagram.')->controller(InstagramLoginController::class)->group(function () {
    Route::get('instagram-callback', 'callback')->name('callback');
});

// Facebook
Route::name('facebook.')->controller(FacebookController::class)->group(function () {
    Route::get('facebook-callback', 'facebookCallback')->name('callback');
    Route::get('facebook-delete-callback', 'deleteCallback')->name('deleteCallback');
    Route::post('deauthorize-callback', 'deauthorizeCallback')->name('deauthorize');
});

// TikTok
Route::name('tiktok.')->controller(TikTokController::class)->group(function () {
    Route::get('tiktok-callback', 'tiktokCallback')->name('callback');
});

// Threads
Route::name('threads.')->controller(ThreadsController::class)->group(function () {
    Route::get('threads-callback', 'threadsCallback')->name('callback');
    Route::get('threads-delete-callback', 'deleteCallback')->name('deleteCallback');
    Route::post('threads-uninstall-callback', 'uninstallCallback')->name('uninstallCallback');
});

// LinkedIn
Route::name('linkedin.')->controller(LinkedInAuthController::class)->group(function () {
    Route::get('linkedin-callback', 'linkedInCallback')->name('callback');
});

// LinkedIn OAuth test flow (step-by-step)
Route::prefix('linkedin-test')->name('linkedin.test.')->controller(LinkedInAuthTestController::class)->group(function () {
    Route::get('login', 'index')->name('index');
    Route::get('start', 'start')->name('start');
    Route::get('callback', 'callback')->name('callback');
});
