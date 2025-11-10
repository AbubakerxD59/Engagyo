<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;

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
        info("facebook:publish");
        $now = date("Y-m-d H:i");
        $posts = $post->notPublished()->past($now)->facebook()->notSchedule()->get();
        foreach ($posts as $key => $post) {
            if ($post->status == "0") {
                $user = $post->user()->first();
                if ($user) {
                    $page = $post->page()->userSearch($user->id)->first();
                    if ($page) {
                        $facebook = $page->facebook()->userSearch($user->id)->first();
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
                            if ($post->type == "content_only") {
                                $postData = [
                                    "message" => $post->title
                                ];
                            } elseif ($post->type == "link") {
                                $postData = [
                                    'link' => $post->url,
                                    'message' => $post->title,
                                ];
                            } elseif ($post->type == "photo") {
                                $postData = [
                                    "caption" => $post->title,
                                    "url" => $post->image
                                ];
                            } elseif ($post->type == "video") {
                                $postData = [
                                    "description" => $post->title,
                                    "file_url" => $post->video
                                ];
                            }
                            PublishFacebookPost::dispatch($post->id, $postData, $access_token, $post->type, $post->comment);
                        }
                    }
                }
            }
        }
    }
}
