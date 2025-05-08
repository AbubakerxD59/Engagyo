<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
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
    public function handle(Post  $post)
    {
        $now = date("Y-m-d H:i");
        $posts = $post->notPublished()->past($now)->get();
        foreach ($posts as $key => $post) {
            Log::info("post: " . json_encode($post));
            if ($post->status == "0") {
                $user = $post->user()->first();
                if ($user) {
                    $board = $post->board()->userSearch($user->id)->first();
                    if ($board) {
                        $pinterest = $board->pinterest()->userSearch($user->id)->first();
                        if ($pinterest) {
                            $postData = array(
                                "title" => $post->title,
                                "link" => $post->url,
                                "board_id" => (string) $post->account_id,
                                "media_source" => array(
                                    "source_type" => str_contains($post->image, "http") ? "image_url" : "image_base64",
                                    "url" => $post->image
                                )
                            );
                            $pinterestService = new PinterestService();
                            $pinterestService->create($post->id, $postData, $board->access_token);
                        }
                    }
                }
            }
        }
    }
}
