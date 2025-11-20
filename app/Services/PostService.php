<?php

namespace App\Services;

use App\Models\Post;

class PostService
{
    public static function create($data)
    {
        $post = Post::create([
            "user_id" => $data["user_id"],
            "account_parent_id" => $data["account_parent_id"],
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
    public function delete($post_id)
    {
        $post = Post::where("id", $post_id)->first();
        if ($post) {
            $post->delete();
        }
        return true;
    }
}
