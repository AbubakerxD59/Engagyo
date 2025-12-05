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
        
        foreach ($posts as $key => $post) {
            try {
                // for facebook posts
                if ($post->social_type == "facebook") {
                    $this->processFacebookPost($post);
                }
                // for pinterest posts
                if ($post->social_type == "pinterest") {
                    $this->processPinterestPost($post);
                }
            } catch (\Exception $e) {
                info("Schedule Publish Error for Post ID {$post->id}: " . $e->getMessage());
                $post->update([
                    "status" => -1,
                    "response" => "Error: " . $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process Facebook scheduled post
     */
    private function processFacebookPost(Post $post)
    {
        $page = $post->page;
        
        if (!$page) {
            $post->update([
                "status" => -1,
                "response" => "Error: Facebook page not found."
            ]);
            return;
        }

        $facebook = $page->facebook;
        
        if (!$facebook) {
            $post->update([
                "status" => -1,
                "response" => "Error: Facebook account not found. Please reconnect your Facebook account."
            ]);
            return;
        }

        // Use the static validateToken method for proper error handling
        $tokenResponse = FacebookService::validateToken($page);
        
        if (!$tokenResponse['success']) {
            $post->update([
                "status" => -1,
                "response" => $tokenResponse["message"] ?? "Error: Failed to validate Facebook access token."
            ]);
            return;
        }

        $access_token = $tokenResponse['access_token'];

        try {
            $postData = PostService::postTypeBody($post);
            PublishFacebookPost::dispatch($post->id, $postData, $access_token, $post->type, $post->comment);
        } catch (\Exception $e) {
            $post->update([
                "status" => -1,
                "response" => "Error preparing post: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Process Pinterest scheduled post
     */
    private function processPinterestPost(Post $post)
    {
        $board = $post->board;
        
        if (!$board) {
            $post->update([
                "status" => -1,
                "response" => "Error: Pinterest board not found."
            ]);
            return;
        }

        $pinterest = $board->pinterest;
        
        if (!$pinterest) {
            $post->update([
                "status" => -1,
                "response" => "Error: Pinterest account not found. Please reconnect your Pinterest account."
            ]);
            return;
        }

        // Use the static validateToken method for proper error handling
        $tokenResponse = PinterestService::validateToken($board);
        
        if (!$tokenResponse['success']) {
            $post->update([
                "status" => -1,
                "response" => $tokenResponse["message"] ?? "Error: Failed to validate Pinterest access token."
            ]);
            return;
        }

        $access_token = $tokenResponse['access_token'];

        try {
            $postData = PostService::postTypeBody($post);
            PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);
        } catch (\Exception $e) {
            $post->update([
                "status" => -1,
                "response" => "Error preparing post: " . $e->getMessage()
            ]);
        }
    }
}
