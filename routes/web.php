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
        $socialTypeBasedPosts = $posts->groupBy('social_type');
        print_r("Social types: " . $socialTypeBasedPosts->keys());
        echo '<br>';
        foreach ($socialTypeBasedPosts as $social_type => $socialTypeBasedPost) {
            $accountBasedPosts = $socialTypeBasedPost->groupBy('account_id');
            print_r("Account IDS: " . $accountBasedPosts->keys());
            echo '<br>';
            dd($accountBasedPosts);
            foreach ($accountBasedPosts as $account_id => $accountBasedPost) {
                echo 'account_id: ' . $account_id;
                echo '<br>';
                $post = $accountBasedPost->first();
                $user_id = $post->user_id;
                if ($social_type == "facebook") {
                    $account = Page::find($account_id);
                }
                if ($social_type == "pinterest") {
                    $account = Board::find($account_id);
                }
                if ($social_type == "tiktok") {
                    $account = Tiktok::find($account_id);
                }
                echo 'Total Posts for account: ' . $account->name . " are " . count($accountBasedPost) . '<br>';
                $shuffle = $account ? $account->shuffle : false;
                echo 'shuffle: ' . $shuffle . '<br>';
                if (!$shuffle) {
                    echo '-----------------------------';
                    echo '<br>';
                    break;
                }
                echo "shuffling posts for user_id: $user_id, account: $account->name, social_type: $social_type <br>";
                // Shuffle start
                $publisheDates = $accountBasedPost->pluck('publish_date');
                $shuffledDates = $publisheDates->shuffle();
                // DB::transaction(function () use ($accountBasedPost, $shuffledDates) {
                //     $count = 0;
                //     foreach ($accountBasedPost as $index => $post) {
                //         $post->update([
                //             "publish_date" => $shuffledDates[$count],
                //         ]);
                //         $count++;
                //     }
                // });
                echo '-----------------------------';
                echo '<br>';
                // Shuffle end
            }
        }
    } catch (Exception $e) {
        return "Error at line#" . $e->getLine() . "with message:" . $e->getMessage();
    }
});
