<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Console\Command;
use App\Jobs\PublishPinterestPost;
use App\Services\PinterestService;
use Illuminate\Support\Facades\Log;

class PinterestPublishCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pinterest:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to publish pinterest posts.';

    /**
     * Execute the console command.
     */
    public function handle(Post $post, PinterestService $pinterestService)
    {
        $now = date("Y-m-d H:i");
        $posts = $post->with("user", "board.pinterest")->notPublished()->past($now)->pinterest()->notSchedule()->get();
        
        foreach ($posts as $post) {
            try {
                $board = $post->board;
                
                if (!$board) {
                    $post->update([
                        "status" => -1,
                        "response" => "Pinterest board not found.",
                        "published_at" => date("Y-m-d H:i:s")
                    ]);
                    continue;
                }

                $pinterest = $board->pinterest;
                
                if (!$pinterest) {
                    $post->update([
                        "status" => -1,
                        "response" => "Pinterest account not found. Please reconnect your account.",
                        "published_at" => date("Y-m-d H:i:s")
                    ]);
                    continue;
                }

                // Use validateToken for proper error handling
                $tokenResponse = PinterestService::validateToken($board);
                if (!$tokenResponse['success']) {
                    $post->update([
                        "status" => -1,
                        "response" => $tokenResponse["message"] ?? "Failed to validate Pinterest access token.",
                        "published_at" => date("Y-m-d H:i:s")
                    ]);
                    continue;
                }

                $access_token = $tokenResponse['access_token'];
                $postData = PostService::postTypeBody($post);
                PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);

            } catch (\Exception $e) {
                $post->update([
                    "status" => -1,
                    "response" => "Error: " . $e->getMessage(),
                    "published_at" => date("Y-m-d H:i:s")
                ]);
            }
        }
    }
}
