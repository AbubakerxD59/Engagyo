<?php

use App\Http\Controllers\FrontEnd\FacebookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontEnd\PinterestController;

Route::name("pinterest.")->controller(PinterestController::class)->group(function () {
    Route::get("pinterest-callback", "pinterestCallback")->name("callback");
});

Route::middleware("web")->name("facebook.")->controller(FacebookController::class)->group(function () {
    Route::get("facebook-callback", "facebookCallback")->name("callback");
    Route::get("facebook-delete-callback", "deleteCallback")->name("deleteCallback");
    Route::post("deauthorize-callback", "deauthorizeCallback")->name("deauthorize");
});
