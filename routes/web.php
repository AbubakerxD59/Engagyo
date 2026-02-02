<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Board;
use App\Models\Tiktok;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneralController;
use Illuminate\Support\Facades\DB;

// General Routes
Route::name("general.")->controller(GeneralController::class)->group(function () {
    Route::get('preview/link', 'previewLink')->name("previewLink");
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

Route::get('shuffle-test', function () {
    try {
        $posts = Post::with(["domain", "board", "page", "tiktok"])->whereNotNull("domain_id")->isRss()->notPublished()->get();
        echo 'total posts: ' . count($posts) . '<br>';
        $socialTypeBasedPosts = $posts->groupBy('social_type');
        foreach ($socialTypeBasedPosts as $social_type => $socialTypeBasedPost) {
            if ($social_type == "facebook") {
                continue;
            }
            echo 'social_type: ' . $social_type . '<br>';
            $accountBasedPosts = $socialTypeBasedPost->groupBy('account_id');
            foreach ($accountBasedPosts as $account_id => $accountBasedPost) {
                $post = $accountBasedPost->first();
                $user_id = $post->user_id;
                echo 'account_id: ' . $account_id . '<br>';
                echo 'total_posts_for_account: ' . count($accountBasedPost) . '<br>';
                if ($social_type == "facebook") {
                    $account = Page::find($account_id);
                }
                if ($social_type == "pinterest") {
                    $account = Board::find($account_id);
                }
                if ($social_type == "tiktok") {
                    $account = Tiktok::find($account_id);
                }
                $shuffle = $account ? $account->shuffle : false;
                echo 'shuffle: ' . $shuffle . '<br>';
                if (!$shuffle) {
                    break;
                }
                echo "shuffling posts for user_id: $user_id, account: $account->name, social_type: $social_type <br>";
                // Shuffle start
                $publisheDates = $accountBasedPost->pluck('publish_date');
                $shuffledDates = $publisheDates->shuffle();
                DB::transaction(function () use ($accountBasedPost, $shuffledDates) {
                    $count = 0;
                    foreach ($accountBasedPost as $index => $post) {
                        $post->update([
                            "publish_date" => $shuffledDates[$count],
                        ]);
                        $count++;
                    }
                });

                // Shuffle end
            }
        }
    } catch (Exception $e) {
        return "Error at line#" . $e->getLine() . "with message:" . $e->getMessage();
    }
});
