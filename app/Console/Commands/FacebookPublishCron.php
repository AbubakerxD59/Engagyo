<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Log;

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
        $posts = $post->notPublished()->past($now)->facebook()->get();
        foreach ($posts as $key => $post) {
            Log::info("post: " . json_encode($post));
            if ($post->status == "0") {
                $user = $post->user()->first();
                if ($user) {
                    $page = $post->page()->userSearch($user->id)->first();
                    if ($page) {
                        $facebook = $page->facebook()->userSearch($user->id)->first();
                        if ($facebook) {
                            if (!$page->validToken()) {
                                $token = $facebookService->refreshAccessToken($page->access_token);
                                $data = $token["data"];
                                $meta_data = $data["metadata"];
                                $access_token = $data["access_token"];;
                                $page->update([
                                    "access_token" => $access_token,
                                    "expires_in" => $meta_data->getField("data_access_expires_at"),
                                ]);
                            } else {
                                $access_token = $page->access_token;
                            }
                            $postData = [
                                'link' => $post->url,
                                'message' => $post->title,
                            ];
                            Log::info("data: " . json_encode($postData));
                            $facebookService->createLink($post->id, $access_token, $postData);
                        }
                    }
                }
            }
        }
    }
}
