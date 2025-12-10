<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Post;
use Illuminate\Console\Command;
use App\Jobs\PublishTikTokPost;
use App\Services\TikTokService;
use App\Services\PostService;

class TikTokPublishCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to publish TikTok posts';

    /**
     * Execute the console command.
     */
    public function handle(Post $post, TikTokService $tiktokService)
    {
        $now = date("Y-m-d H:i");

        $posts = $post->with("user", "tiktok")->notPublished()->past($now)->tiktok()->notSchedule()->get();
        foreach ($posts as $key => $post) {
            try {
                sleep(3);
                $tiktok = $post->tiktok;
                
                if (!$tiktok) {
                    $post->update([
                        "status" => -1,
                        "response" => "TikTok account not found.",
                        "published_at" => date("Y-m-d H:i:s")
                    ]);
                    continue;
                }

                // Use validateToken for proper error handling
                $tokenResponse = TikTokService::validateToken($tiktok);
                if (!$tokenResponse['success']) {
                    $post->update([
                        "status" => -1,
                        "response" => $tokenResponse["message"] ?? "Failed to validate TikTok access token.",
                        "published_at" => date("Y-m-d H:i:s")
                    ]);
                    continue;
                }

                $access_token = $tokenResponse['access_token'];
                $postData = PostService::postTypeBody($post);
                PublishTikTokPost::dispatch($post->id, $postData, $access_token, $post->type);

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

