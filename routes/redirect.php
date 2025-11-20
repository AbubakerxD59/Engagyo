<?php

use App\Http\Controllers\FrontEnd\FacebookController;
use App\Http\Controllers\FrontEnd\TikTokController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontEnd\PinterestController;

// Pinterest
Route::name("pinterest.")->controller(PinterestController::class)->group(function () {
    Route::get("pinterest-callback", "pinterestCallback")->name("callback");
});

// Facebook
Route::middleware("web")->name("facebook.")->controller(FacebookController::class)->group(function () {
    Route::get("facebook-callback", "facebookCallback")->name("callback");
    Route::get("facebook-delete-callback", "deleteCallback")->name("deleteCallback");
    Route::post("deauthorize-callback", "deauthorizeCallback")->name("deauthorize");
});

// TikTok
Route::name("tiktok.")->controller(TikTokController::class)->group(function () {
    Route::get("tiktok-callback", "tiktokCallback")->name("callback");
});
