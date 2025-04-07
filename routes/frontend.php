<?php

use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\FrontEnd\FrontendController;
use Illuminate\Support\Facades\Route;

// Routes for frontend
Route::name("frontend.")->controller(AuthController::class)->middleware(["frontend_guest"])->group(function () {
    Route::get("login", "showLogin")->name("showLogin");
    Route::get("register", "showRegister")->name("showRegister");
});
Route::name("frontend.")->controller(FrontendController::class)->group(function () {
    Route::get("/", "home")->name("home");
    Route::get("calendar-view", "calendarView")->name("calendarView");
    Route::get("bulk-scheduling", "bulkScheduling")->name("bulkScheduling");
    Route::get("analytics", "analytics")->name("analytics");
    Route::get("recycling", "recycling")->name("recycling");
    Route::get("rss-feeds", "rssFeeds")->name("rssFeeds");
    Route::get("curate-post", "curatePost")->name("curatePost");
});
