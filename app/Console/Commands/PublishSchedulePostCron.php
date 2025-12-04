<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Console\Command;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Jobs\PublishPinterestPost;
use App\Services\PinterestService;

class PublishSchedulePostCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to publish scheduled posts.';

    /**
     * Execute the console command.
     */
    public function handle(Post $post, PinterestService $pinterestService, FacebookService $facebookService)
    {
        $now = date("Y-m-d H:i");
        $posts = $post->with("user", "page.facebook", "board.pinterest")->notPublished()->past($now)->schedule()->get();
        info(json_encode($posts));
        foreach ($posts as $key => $post) {
            // for facebook posts
            if ($post->social_type == "facebook") {
                $page = $post->page;
                $facebook = $page ? $page->facebook : null;
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
                    $postData = PostService::postTypeBody($post);
                    PublishFacebookPost::dispatch($post->id, $postData, $access_token, $post->type, $post->comment);
                }
            }
            // for pinterest posts
            if ($post->social_type == "pinterest") {
                $board = $post->board;
                $pinterest = $board ? $board->pinterest : null;
                if ($pinterest) {
                    $access_token = $pinterest->access_token;
                    if (!$pinterest->validToken()) {
                        $token = $pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                        $access_token = $token["access_token"];
                    }
                    $postData = PostService::postTypeBody($post);
                    PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);
                }
            }
        }
    }
}
