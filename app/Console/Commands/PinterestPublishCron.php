<?php

namespace App\Console\Commands;

use App\Models\Post;
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
        info("pinterest:publish");
        $now = date("Y-m-d H:i");
        $posts = $post->notPublished()->past($now)->pinterest()->notSchedule()->get();
        info(json_encode($posts));
        foreach ($posts as $key => $post) {
            if ($post->status == "0") {
                $user = $post->user()->first();
                if ($user) {
                    $board = $post->board()->userSearch($user->id)->first();
                    if ($board) {
                        $pinterest = $board->pinterest()->userSearch($user->id)->first();
                        if ($pinterest) {
                            $access_token = $pinterest->access_token;
                            if (!$pinterest->validToken()) {
                                $token = $pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                                $access_token = $token["access_token"];
                            }
                            if ($post->type == "link") {
                                $postData = array(
                                    "title" => $post->title,
                                    "link" => $post->url,
                                    "board_id" => (string) $post->account_id,
                                    "media_source" => array(
                                        "source_type" => str_contains($post->image, "http") ? "image_url" : "image_base64",
                                        "url" => $post->image
                                    )
                                );
                            } elseif ($post->type == "photo") {
                                $encoded_image = file_get_contents($post->image);
                                $encoded_image = base64_encode($encoded_image);
                                $postData = array(
                                    "title" => $post->title,
                                    "board_id" => (string) $post->account_id,
                                    "media_source" => array(
                                        "source_type" => "image_base64",
                                        "content_type" => "image/jpeg",
                                        "data" => $encoded_image
                                    )
                                );
                            } elseif ($post->type == "video") {
                                $postData = array(
                                    "title" => $post->title,
                                    "board_id" => (string) $post->account_id,
                                    'video_key' => $post->video
                                );
                            }
                            info($post->type);
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);
                        }
                    }
                }
            }
        }
    }
}
