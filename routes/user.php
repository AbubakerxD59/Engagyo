<?php

use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AccountsController;
use App\Http\Controllers\User\ScheduleController;
use App\Http\Controllers\User\AutomationController;
use App\Models\Post;

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
            Route::get("post/delete/{id?}", "postDelete")->name('post.delete');
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

Route::get("test", function (Post $post, FacebookService $facebookService) {
    $now = date("Y-m-d H:i");
    $posts = $post->notPublished()->past($now)->facebook()->notSchedule()->get();
    foreach ($posts as $key => $post) {
        if ($post->status == "0") {
            $user = $post->user()->first();
            if ($user) {
                $page = $post->page()->userSearch($user->id)->first();
                if ($page) {
                    $facebook = $page->facebook()->userSearch($user->id)->first();
                    if ($facebook) {
                        $access_token = $page->access_token;
                        if (!$page->validToken()) {
                            $token = $facebookService->refreshAccessToken($page->access_token, $page->id);
                            if ($token["success"]) {
                                $data = $token["data"];
                                $access_token = $data["access_token"];
                            } else {
                                $post->update([
                                    "status" => -1,
                                    "response" => $token["message"]
                                ]);
                                continue;
                            }
                        }
                        if ($post->type == "content_only") {
                            $postData = [
                                "message" => $post->title
                            ];
                        } elseif ($post->type == "link") {
                            $postData = [
                                'link' => $post->url,
                                'message' => $post->title,
                            ];
                        } elseif ($post->type == "photo") {
                            $postData = [
                                "caption" => $post->title,
                                "url" => $post->image
                            ];
                        } elseif ($post->type == "video") {
                            $postData = [
                                "title" => $post->title,
                                "file_url" => $post->video_key
                            ];
                        }
                        info("post: " . json_encode($postData));
                        $facebookService = new facebookService();
                        if ($post->type == "link") {
                            $publish_response = $facebookService->createLink($post->id, $access_token, $postData);
                        } elseif ($post->type == "content_only") {
                            $publish_response = $facebookService->contentOnly($post->id, $access_token, $postData);
                        } elseif ($post->type == "photo") {
                            $publish_response = $facebookService->photo($post->id, $access_token, $postData);
                        } elseif ($post->type == "video") {
                            $publish_response = $facebookService->video($post->id, $access_token, $postData);
                        }
                        dd($publish_response);
                    }
                }
            }
        }
    }
});
