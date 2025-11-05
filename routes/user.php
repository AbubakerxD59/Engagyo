<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AccountsController;
use App\Http\Controllers\User\ScheduleController;
use App\Http\Controllers\User\AutomationController;

Route::name("panel.")->prefix("panel/")->middleware(["user_auth"])->group(function () {
    // Schedule Routes
    Route::controller(ScheduleController::class)->group(function () {
        Route::get("schedule", "index")->name("schedule");
        Route::controller(ScheduleController::class)->prefix("schedule/")->name("schedule.")->group(function () {
            Route::get("account/status", "accountStatus")->name("account.status");
            Route::post("process/post", "processPost")->name("process.post");
            Route::get("get/setting", "getSetting")->name("get.setting");
            Route::post("timeslot/setting", "timeslotSetting")->name("timeslot.setting");
            Route::get("posts/listing", "postsListing")->name("posts.listing");
        });
    });
    // Accounts Routes
    Route::controller(AccountsController::class)->group(function () {
        Route::get("accounts", "index")->name("accounts");
        // Accounts sub Routes
        Route::name("accounts.")->group(function () {
            // Pinterest
            Route::get("pinterest/{id?}", "pinterest")->name("pinterest");
            Route::post("add-board", "addBoard")->name("addBoard");
            Route::delete('pinterest-delete/{id?}', 'pinterestDelete')->name('pinterest.delete');
            Route::delete('board-delete/{id?}', 'boardDelete')->name("board.delete");
            // Facebook
            Route::get("facebook/{id?}", "facebook")->name("facebook");
            Route::post("add-page", "addPage")->name("addPage");
            Route::delete('facebook-delete/{id?}', 'facebookDelete')->name('facebook.delete');
            Route::delete('page-delete/{id?}', 'pageDelete')->name("page.delete");
        });
    });
    // Automation Routes
    Route::controller(AutomationController::class)->group(function () {
        Route::get("automation", "index")->name("automation");
        Route::name("automation.")->group(function () {
            Route::post("feed-url", "feedUrl")->name("feedUrl");
            Route::get("get-domain", "getDomain")->name("getDomain");
            Route::post("save-filters", "saveFilters")->name("saveFilters");
            // Posts Routes
            Route::name("posts.")->group(function () {
                Route::get("posts-dataTable", "posts")->name("dataTable");
                Route::post("post-delete", "postDelete")->name("destroy");
                Route::post("post-update/{id?}", "postUpdate")->name("update");
                Route::post("post-publish/{id?}", "postPublish")->name("publish");
                Route::post("posts-shuffle", "postShuffle")->name("shuffle");
                Route::post("posts-delete-all", "deleteAll")->name("deleteAll");
                Route::post("post-fix", "postFix")->name("fix");
            });
        });
    });
});

Route::get("test", [AutomationController::class, "test"])->name("fetch.rss");
