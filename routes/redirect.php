<?php

use App\Http\Controllers\FrontEnd\FacebookController;
use App\Http\Controllers\FrontEnd\InstagramLoginController;
use App\Http\Controllers\FrontEnd\PinterestController;
use App\Http\Controllers\FrontEnd\TikTokController;
use Illuminate\Support\Facades\Route;

// Pinterest
Route::name('pinterest.')->controller(PinterestController::class)->group(function () {
    Route::get('pinterest-callback', 'pinterestCallback')->name('callback');
});

// Facebook
Route::name('facebook.')->controller(FacebookController::class)->group(function () {
    Route::get('facebook-callback', 'facebookCallback')->name('callback');
    Route::get('facebook-delete-callback', 'deleteCallback')->name('deleteCallback');
    Route::post('deauthorize-callback', 'deauthorizeCallback')->name('deauthorize');
});

// Instagram API with Instagram Login
Route::name('instagram.')->controller(InstagramLoginController::class)->group(function () {
    Route::get('instagram-callback', 'callback')->name('callback');
});

// TikTok
Route::name('tiktok.')->controller(TikTokController::class)->group(function () {
    Route::get('tiktok-callback', 'tiktokCallback')->name('callback');
});
