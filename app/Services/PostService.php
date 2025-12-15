<?php

namespace App\Services;

use Exception;
use App\Models\Post;
use App\Jobs\PublishFacebookPost;
use App\Jobs\PublishPinterestPost;
use Illuminate\Support\Facades\Auth;

class PostService
{
    public static function create($data)
    {
        $post = Post::create([
            "user_id" => $data["user_id"],
            "account_id" => $data["account_id"],
            "social_type" => $data["social_type"],
            "type" => $data["type"],
            "source" => $data["source"],
            "title" => isset($data["title"]) ? $data["title"] : null,
            "description" => isset($data["description"]) ? $data["description"] : null,
            "comment" => isset($data["comment"]) ? $data["comment"] : null,
            "domain_id" => isset($data["domain_id"]) ? $data["domain_id"] : null,
            "url" => isset($data["url"]) ? $data["url"] : null,
            "image" => isset($data["image"]) ? $data["image"] : null,
            "publish_date" => $data["publish_date"],
            "scheduled" => isset($data["scheduled"]) ? $data["scheduled"] : 0,
            "status" => 0,
            "video" => isset($data["video"]) ? $data["video"] : 0,
        ]);
        return $post;
    }
    public static function delete($post_id)
    {
        $post = Post::with("page", "board.pinterest", "tiktok")->where("id", $post_id)->first();
        if ($post) {
            if ($post->status == 1) {
                if ($post->social_type == "facebook") {
                    $service = new FacebookService();
                    $service->delete($post);
                }
                if ($post->social_type == "pinterest") {
                    $service = new PinterestService();
                    $service->delete($post);
                }
                // TikTok delete API is disabled for now
                // if ($post->social_type == "tiktok") {
                //     $service = new TikTokService();
                //     $service->delete($post);
                // }
            }
            $post->delete();
        }
        return true;
    }
    public static function publishNow($id)
    {
        try {
            $user = Auth::user();
            $post = Post::with("page.facebook", "board.pinterest")->where("status", "!=", 1)->where("id", $id)->firstOrFail();
            if ($post->social_type == "facebook") { // Facebook
                $page = $post->page;
                $response = FacebookService::validateToken($page);
                if ($response['success']) {
                    $postData = self::postTypeBody($post);
                    PublishFacebookPost::dispatch($post->id, $postData, $response["access_token"], $post->type, $post->comment);
                } else {
                    return $response;
                }
            }
            if ($post->social_type == "pinterest") { // Pinterest
                $postData = self::postTypeBody($post);
                PublishPinterestPost::dispatch($post->id, $postData, $post->board->pinterest->access_token, $post->type);
            }
            $response = array(
                "success" => true,
                "message" => "Your Post is being Published.",
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return $response;
    }
    public static function postTypeBody($post)
    {
        if ($post->social_type == "facebook") { //Facebook
            if ($post->type == "content_only") {
                $postData = [
                    'message' => $post->title ?: ' ' // Facebook requires a message for content_only posts, use space if empty
                ];
            }
            if ($post->type == "photo") {
                $postData = [
                    "url" => $post->image
                ];
                // Only include message if title is not empty (Facebook prefers 'message' over 'caption')
                if (!empty($post->title)) {
                    $postData["message"] = $post->title;
                }
            }
            if ($post->type == "video") {
                $postData = [
                    "file_url" => $post->video_key
                ];
                // Only include description if title is not empty
                if (!empty($post->title)) {
                    $postData["description"] = $post->title;
                }
            }
            if ($post->type == "link") {
                $postData = [
                    'link' => $post->url
                ];
                // Only include message if title is not empty (Facebook rejects empty messages)
                if (!empty($post->title)) {
                    $postData['message'] = $post->title;
                }
            }
        }
        if ($post->social_type == "pinterest") { //Pinterest
            $board = $post->board;
            $board_id = $board ? $board->board_id : null;
            if ($post->type == "photo") {
                $encoded_image = file_get_contents($post->image);
                $encoded_image = base64_encode($encoded_image);
                $postData = array(
                    "title" => $post->title,
                    "board_id" => (string) $board_id,
                    "media_source" => array(
                        "source_type" => "image_base64",
                        "content_type" => "image/jpeg",
                        "data" => $encoded_image
                    )
                );
            }
            if ($post->type == "video") {
                $postData = array(
                    "title" => $post->title,
                    "board_id" => (string) $board_id,
                    'video_key' => $post->video
                );
            }
            if ($post->type == "link") {
                $postData = [
                    "title" => $post->title,
                    "link" => $post->url,
                    "board_id" => (string) $board_id,
                    "media_source" => [
                        "source_type" => str_contains($post->image, "http") ? "image_url" : "image_base64",
                        "url" => $post->image
                    ]
                ];
            }
        }
        if ($post->social_type == "tiktok") { //TikTok
            if ($post->type == "photo") {
                $postData = [
                    "title" => $post->title,
                    "url" => $post->image
                ];
            }
            if ($post->type == "video") {
                $postData = [
                    "title" => $post->title,
                    "file_url" => $post->video_key
                ];
            }
            if ($post->type == "link") {
                $postData = [
                    "title" => $post->title,
                    "link" => $post->url,
                    "url" => $post->image
                ];
            }
        }
        return $postData;
    }
}
