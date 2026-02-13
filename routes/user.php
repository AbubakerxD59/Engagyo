<?php

use App\Models\Feature;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\ApiKeysController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\AccountsController;
use App\Http\Controllers\User\ApiPostsController;
use App\Http\Controllers\User\ScheduleController;
use App\Http\Controllers\User\SettingsController;
use App\Http\Controllers\User\AutomationController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\TeamMemberController;
use App\Http\Controllers\User\UrlTrackingController;

Route::name("panel.")->prefix("panel/")->middleware(["user_auth", "redirect_if_admin", "team.menu"])->group(function () {
    // Schedule Routes
    Route::controller(ScheduleController::class)->middleware(['feature:' . Feature::$features_list[1]])->group(function () {
        Route::get("schedule", "index")->name("schedule");
        Route::controller(ScheduleController::class)->prefix("schedule/")->name("schedule.")->group(function () {
            Route::get("account/status", "accountStatus")->name("account.status");
            Route::post("process/post", "processPost")->name("process.post");
            Route::get("get/setting", "getSetting")->name("get.setting");
            Route::post("timeslot/setting", "timeslotSetting")->name("timeslot.setting");
            Route::post("timeslot/setting/save", "saveTimeslotSettings")->name("timeslot.setting.save");
            Route::get("posts/listing", "postsListing")->name("posts.listing");
            Route::prefix("post/")->name("post.")->group(function () {
                Route::get("delete/{id?}", "postDelete")->name('delete');
                Route::get("edit/{id?}", "postEdit")->name("edit");
                Route::post("update/{id?}", "postUpdate")->name("update");
                Route::post("publish/now/{id?}", "postPublishNow")->name("publish.now");
            });
        });
    });
    // Accounts Routes - Protected by feature middleware
    Route::controller(AccountsController::class)->middleware(['feature:' . Feature::$features_list[0]])->group(function () {
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
            // TikTok
            Route::get("tiktok/{id?}", "tiktok")->name("tiktok");
            Route::delete('tiktok-delete/{id?}', 'tiktokDelete')->name('tiktok.delete');
            // Toggle RSS Pause
            Route::post("toggle-rss-pause", "toggleRssPause")->name("toggleRssPause");
        });
    });
    // Automation Routes
    Route::controller(AutomationController::class)->middleware(['feature:' . Feature::$features_list[2]])->group(function () {
        Route::get("automation", "index")->name("automation");
        Route::name("automation.")->group(function () {
            Route::post("feed-url", "feedUrl")->name("feedUrl");
            Route::get("get-domain", "getDomain")->name("getDomain");
            Route::post("save-filters", "saveFilters")->name("saveFilters");
            Route::post("delete-domain", "deleteDomain")->name("deleteDomain");
            // Posts Routes
            Route::name("posts.")->group(function () {
                Route::get("posts-dataTable", "posts")->name("dataTable");
                Route::post("post-delete", "postDelete")->name("destroy");
                Route::post("post-update/{id?}", "postUpdate")->name("update");
                Route::post("post-publish/{id?}", "postPublish")->name("publish");
                Route::post("posts-shuffle", "postShuffle")->name("shuffle");
                Route::post("posts-delete-all", "deleteAll")->name("deleteAll");
                Route::post("post-fix", "postFix")->name("fix");
                Route::post("save-changes", "saveChanges")->name("saveChanges");
            });
        });
    });
    // API Keys Routes
    Route::controller(ApiKeysController::class)->middleware(['feature:' . Feature::$features_list[5]])->group(function () {
        Route::get("api-keys", "index")->name("api-keys");
        Route::name("api-keys.")->prefix("api-keys/")->group(function () {
            Route::post("store", "store")->name("store");
            Route::post("refresh/{id}", "refresh")->name("refresh");
            Route::post("toggle/{id}", "toggle")->name("toggle");
            Route::delete("destroy/{id}", "destroy")->name("destroy");
        });
    });
    // URL Tracking Routes
    Route::controller(UrlTrackingController::class)->middleware(['feature:' . Feature::$features_list[6]])->group(function () {
        Route::get("url-tracking", "index")->name("url-tracking");
        Route::name("url-tracking.")->prefix("url-tracking/")->group(function () {
            Route::post("store", "store")->name("store");
            Route::get("{id}", "show")->name("show");
            Route::post("update/{id}", "update")->name("update");
            Route::delete("destroy/{id}", "destroy")->name("destroy");
            Route::post("delete-all-domain", "deleteAllForDomain")->name("deleteAllDomain");
            Route::post("get-by-domain", "getByDomain")->name("getByDomain");
        });
    });
    // API Posts Routes
    Route::controller(ApiPostsController::class)->group(function () {
        Route::get("api-posts", "index")->name("api-posts");
        Route::name("api-posts.")->prefix("api-posts/")->group(function () {
            Route::get("posts/listing", "postsListing")->name("posts.listing");
            Route::prefix("post/")->name("post.")->group(function () {
                Route::get("delete", "postDelete")->name('delete');
                Route::get("edit", "postEdit")->name("edit");
                Route::post("update/{id?}", "postUpdate")->name("update");
                Route::post("publish/now", "postPublishNow")->name("publish.now");
            });
        });
    });
    // Settings Routes
    Route::controller(SettingsController::class)->group(function () {
        Route::get("settings", "index")->name("settings");
        Route::name("settings.")->prefix("settings/")->group(function () {
            Route::post("update", "update")->name("update");
            Route::post("update-profile-pic", "updateProfilePic")->name("updateProfilePic");
            Route::post("remove-profile-pic", "removeProfilePic")->name("removeProfilePic");
            Route::post("update-password", "updatePassword")->name("updatePassword");
        });
    });
    // Plan & Billing Routes
    Route::controller(App\Http\Controllers\User\PlanBillingController::class)->middleware(['feature'])->group(function () {
        Route::get("plan-billing", "index")->name("plan.billing");
    });
    // Notifications Routes
    Route::controller(NotificationController::class)->name("notifications.")->prefix("notifications/")->group(function () {
        Route::get("fetch", "fetch")->name("fetch");
        Route::post("mark-read/{id}", "markAsRead")->name("markRead");
        Route::post("mark-all-read", "markAllAsRead")->name("markAllRead");
    });
    // Upgrade Packages (AJAX endpoint)
    Route::get("packages/upgrade", [PaymentController::class, "getUpgradePackages"])->name("packages.upgrade");

    // Team Members Routes
    Route::resource('team-members', TeamMemberController::class)->except(['show']);
});
