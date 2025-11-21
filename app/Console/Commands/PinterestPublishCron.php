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
        $posts = $post->notPublished()->past($now)->pinterest()->notSchedule()->get();
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
                            $postData = PostService::postTypeBody($post);
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);
                        }
                    }
                }
            }
        }
    }
}
