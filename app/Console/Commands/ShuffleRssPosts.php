<?php

namespace App\Console\Commands;

use App\Models\Board;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tiktok;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShuffleRssPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:shuffle-rss-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command shuffles rss posts daily (once) for shuffle enabled accounts.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get posts
        $posts = Post::with(["domain", "board", "page", "tiktok"])->whereNotNull("domain_id")->isRss()->notPublished()->get();
        echo 'total posts: ' . count($posts) . '<br>';
        $userBasedPosts = $posts->groupBy('user_id');
        foreach ($userBasedPosts as $user_id => $userBasedPost) {
            echo 'user_id: ' . $user_id . '<br>';
            $socialTypeBasedPosts = $userBasedPost->groupBy('social_type');
            foreach ($socialTypeBasedPosts as $social_type => $socialTypeBasedPost) {
                echo 'social_type: ' . $social_type . '<br>';
                $accountBasedPosts = $socialTypeBasedPost->groupBy('account_id');
                foreach ($accountBasedPosts as $account_id => $accountBasedPost) {
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
        }
    }
}
