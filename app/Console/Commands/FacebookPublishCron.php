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
                $page = $post->page;
                if ($page->facebook) {
                    $access_token = $page->access_token;
                    if (!$page->validToken()) {
                        $token = $facebookService->refreshAccessToken($page->access_token, $page->id);
                        if ($token["success"]) {
                            $data = $token["data"];
                            $access_token = $data["access_token"];
                        } else {
                            $post->update([
                                "status" => -1,
                                "response" => $token["message"],
                                "published_at" => date("Y-m-d H:i:s")
                            ]);
                            continue;
                        }
                    }
                    $postData = PostService::postTypeBody($post);
                    PublishFacebookPost::dispatch($post->id, $postData, $access_token, $post->type, $post->comment);
                }
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
