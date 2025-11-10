<?php

namespace App\Console\Commands;

use App\Models\Post;
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
        info("schedule:publish");
        $now = date("Y-m-d H:i");
        $posts = $post->notPublished()->past($now)->schedule()->get();
        foreach ($posts as $key => $post) {
            $user = $post->user()->first();
            if ($user) {
                // for facebook posts
                if ($post->social_type == "facebook") {
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
                // for pinterest posts
                if ($post->social_type == "pinterest") {
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
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);
                        }
                    }
                }
            }
        }
    }
}
