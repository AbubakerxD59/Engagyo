<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Post;
use Illuminate\Console\Command;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Services\PostService;

class FacebookPublishCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facebook:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(Post $post, FacebookService $facebookService)
    {
        $now = date("Y-m-d H:i");

        $posts = $post->with("user", "page.facebook")->notPublished()->past($now)->facebook()->notSchedule()->get();
        foreach ($posts as $key => $post) {
            try {
                sleep(3);
                $page = $post->page;
                
                if (!$page) {
                    $post->update([
                        "status" => -1,
                        "response" => "Facebook page not found.",
                        "published_at" => date("Y-m-d H:i:s")
                    ]);
                    continue;
                }

                if (!$page->facebook) {
                    $post->update([
                        "status" => -1,
                        "response" => "Facebook account not found. Please reconnect your account.",
                        "published_at" => date("Y-m-d H:i:s")
                    ]);
                    continue;
                }

                // Use validateToken for proper error handling
                $tokenResponse = FacebookService::validateToken($page);
                if (!$tokenResponse['success']) {
                    $post->update([
                        "status" => -1,
                        "response" => $tokenResponse["message"] ?? "Failed to validate Facebook access token.",
                        "published_at" => date("Y-m-d H:i:s")
                    ]);
                    continue;
                }

                $access_token = $tokenResponse['access_token'];
                $postData = PostService::postTypeBody($post);
                PublishFacebookPost::dispatch($post->id, $postData, $access_token, $post->type, $post->comment);

            } catch (Exception $e) {
                $post->update([
                    "status" => -1,
                    "response" => $e->getMessage(),
                    "published_at" => date("Y-m-d H:i:s")
                ]);
            }
        }
    }
}
