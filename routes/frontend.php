<?php

use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\User\AccountsController;
use Illuminate\Support\Facades\Route;

// Routes for frontend
Route::name("frontend.")->controller(AuthController::class)->group(function () {
    Route::middleware(["frontend_guest"])->group(function () {
        Route::get("users/sign_in", "showLogin")->name("showLogin");
        Route::get("users/sign_up", "showRegister")->name("showRegister");
        Route::post("users/login", "login")->name("login");
        Route::post("users/register", "register")->name("register");
    });
    Route::get("users/logout", "logout")->name("logout");
});
Route::name("frontend.")->controller(FrontendController::class)->group(function () {
    Route::get("/", "home")->name("home");
    Route::get("calendar-view", "calendarView")->name("calendarView");
    Route::get("bulk-scheduling", "bulkScheduling")->name("bulkScheduling");
    Route::get("analytics", "analytics")->name("analytics");
    Route::get("recycling", "recycling")->name("recycling");
    Route::get("rss-feeds", "rssFeeds")->name("rssFeeds");
    Route::get("curate-post", "curatePost")->name("curatePost");
    Route::get("terms", "terms")->name("terms");
    Route::get("privacy", "privacy")->name("privacy");
});
Route::name("panel.")->prefix("panel/")->middleware(["user_auth"])->group(function () {
    Route::controller(AccountsController::class)->group(function () {
        Route::get("accounts", "index")->name("accounts");
    });
});
