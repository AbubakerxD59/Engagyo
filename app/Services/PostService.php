<?php

namespace App\Services;

use Exception;
use App\Models\Post;
use App\Models\Page;
use App\Models\Board;
use App\Models\Tiktok;
use App\Models\InstagramAccount;
use App\Jobs\DeleteFacebookPostJob;
use App\Jobs\PublishFacebookPost;
use App\Jobs\PublishInstagramPost;
use App\Jobs\PublishPinterestPost;
use App\Models\User;
use App\Services\UtmService;
use App\Services\UrlShortenerService;
use Illuminate\Support\Facades\Auth;

class PostService
{
    public static function create($data)
    {
        self::applyUtmCodesToData($data);
        self::applyLinkShorteningToData($data);

        $user = User::find($data['user_id']);
        $data['publish_date'] = TimezoneService::toUtc($data['publish_date'], $user);

        // publish_date is stored as provided (no timezone conversion)
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
            "metadata" => isset($data["metadata"]) ? $data["metadata"] : null,
        ]);
        return $post;
    }
    public static function delete($post_id)
    {
        $post = Post::with("page", "board.pinterest", "tiktok", "instagramAccount")->where("id", $post_id)->first();
        if ($post) {
            $status = $post->status;
            $social_type = $post->social_type;
            $facebookPostId = $post->post_id ?? null;
            $pageId = $post->account_id;
            $dbPostId = $post->id;

            // Pinterest: delete from API synchronously (needs post object)
            if ($status == 1 && $social_type == "pinterest" && !empty($post->post_id)) {
                $service = new PinterestService();
                $service->delete($post);
            }

            // Delete from database instantly
            $post->delete();

            // Facebook: delete via background job (user gets instant response)
            if ($status == 1 && $social_type == "facebook" && !empty($facebookPostId)) {
                DeleteFacebookPostJob::dispatch($facebookPostId, $pageId, $dbPostId);
            }
        }
        return true;
    }
    public static function publishNow($id)
    {
        try {
            $user = Auth::user();
            $post = Post::with("page.facebook", "board.pinterest", "instagramAccount.linkedPage")->where("status", "!=", 1)->where("id", $id)->firstOrFail();
            if ($post->social_type == "facebook") {
                $page = $post->page;
                $response = FacebookService::validateToken($page);
                if ($response['success']) {
                    $postData = self::postTypeBody($post);
                    PublishFacebookPost::dispatch($post->id, $postData, $response["access_token"], $post->type, $post->comment);
                } else {
                    return $response;
                }
            }
            if ($post->social_type == "pinterest") {
                $postData = self::postTypeBody($post);
                PublishPinterestPost::dispatch($post->id, $postData, $post->board->pinterest->access_token, $post->type);
            }
            if ($post->social_type == "instagram") {
                $ig = $post->instagramAccount;
                $page = $ig?->linkedPage;
                if (! $ig || ! $page) {
                    return [
                        'success' => false,
                        'message' => 'Instagram account or linked Facebook Page not found.',
                    ];
                }
                $tokenResponse = FacebookService::validateToken($page);
                if (! $tokenResponse['success']) {
                    return $tokenResponse;
                }
                PublishInstagramPost::dispatch($post->id, $tokenResponse['access_token']);
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

    private static function applyUtmCodesToData(array &$data): void
    {
        if (empty($data['user_id'])) {
            return;
        }

        $utmContext = null;
        if (!empty($data['social_type']) && !empty($data['account_id'])) {
            $utmContext = [
                'social_type' => $data['social_type'] ?? null,
                'account_id' => $data['account_id'] ?? null,
                'type' => $data['type'] ?? null,
            ];
        }

        if (!empty($data['url'])) {
            $data['url'] = UtmService::appendUtmCodes($data['url'], $data['user_id'], $utmContext);
        }
        if (!empty($data['title'])) {
            $data['title'] = self::trackUrlsInText($data['title'], $data['user_id'], $utmContext);
        }
        if (!empty($data['comment'])) {
            $data['comment'] = self::trackUrlsInText($data['comment'], $data['user_id'], $utmContext);
        }
    }

    private static function applyLinkShorteningToData(array &$data): void
    {
        if (empty($data['user_id']) || empty($data['account_id']) || empty($data['social_type'])) {
            return;
        }

        $account = match ($data['social_type']) {
            'facebook' => Page::find($data['account_id']),
            'pinterest' => Board::find($data['account_id']),
            'tiktok' => Tiktok::find($data['account_id']),
            'instagram' => InstagramAccount::find($data['account_id']),
            default => null,
        };

        if (!$account || !($account->url_shortener_enabled ?? false)) {
            return;
        }

        $user = \App\Models\User::find($data['user_id']);
        if (!$user) {
            return;
        }

        $urlShortener = app(UrlShortenerService::class);

        if (!empty($data['url'])) {
            $result = $urlShortener->shortenForUser($user, $data['url']);
            $data['url'] = $result['success'] ? $result['short_url'] : $data['url'];
        }
        if (!empty($data['title'])) {
            $data['title'] = $urlShortener->shortenUrlsInText($user, $data['title'], true);
        }
        if (!empty($data['comment'])) {
            $data['comment'] = $urlShortener->shortenUrlsInText($user, $data['comment'], true);
        }
    }

    public static function postTypeBody($post)
    {
        switch ($post->social_type) {
            case 'facebook':
                return self::facebookPostTypeBody($post);
            case 'pinterest':
                return self::pinterestPostTypeBody($post);
            case 'tiktok':
                return self::tiktokPostTypeBody($post);
            case 'instagram':
                return self::instagramPostTypeBody($post);
            default:
                return [];
        }
    }

    public static function instagramPostTypeBody(Post $post): array
    {
        $postData = [];
        if ($post->type === 'photo') {
            $postData['image_url'] = $post->image;
            $captionParts = array_filter([(string) ($post->title ?? ''), (string) ($post->comment ?? '')]);
            $caption = trim(implode("\n\n", $captionParts));
            if ($caption !== '') {
                $postData['caption'] = $caption;
            }
        }

        return $postData;
    }

    public static function facebookPostTypeBody($post)
    {
        $postData = [];
        if ($post->type == "content_only") {
            $postData = array(
                'message' => $post->title ?: ' '
            );
        }
        if ($post->type == "photo") {
            $postData = array(
                "url" => $post->image
            );
            if (!empty($post->title)) {
                $postData["message"] = $post->title;
            }
        }
        if ($post->type == "video") {
            $postData = array(
                "file_url" => $post->video_key
            );
            if (!empty($post->title)) {
                $postData["description"] = $post->title;
            }
        }
        if ($post->type == "reel") {
            $postData = [
                'file_url' => $post->video_key ?: $post->video,
            ];
            if (! empty($post->title)) {
                $postData['description'] = $post->title;
            }
            if (! empty($post->description)) {
                $postData['title'] = $post->description;
            }
        }
        if ($post->type == "story") {
            $hasVideo = !empty($post->video_key) || !empty($post->video);
            $postData = [
                'media_kind' => $hasVideo ? 'video' : 'photo',
            ];

            if (!$hasVideo) {
                // Ensure photo_url is an absolute URL for Facebook API
                $photo = $post->image;
                if (!empty($photo) && !str_starts_with($photo, 'http')) {
                    $photo = fetchFromS3($photo);
                }
                $postData['photo_url'] = $photo;
            } else {
                // Ensure video_url is an absolute URL (prefer S3 key accessor, fallback to raw video field)
                $videoUrl = $post->video_key ?: $post->video;
                if (!empty($videoUrl) && !str_starts_with($videoUrl, 'http')) {
                    $videoUrl = fetchFromS3($videoUrl);
                }
                $postData['video_url'] = $videoUrl;
            }

            if (!empty($post->title)) {
                $postData['caption'] = $post->title;
            }
        }
        if ($post->type == "link") {
            $postData = array(
                'link' => $post->url
            );
            if (!empty($post->title)) {
                $postData['message'] = $post->title;
            }
        }
        return $postData;
    }

    public static function pinterestPostTypeBody($post)
    {
        $postData = [];
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
        return $postData;
    }

    public static function tiktokPostTypeBody($post)
    {
        $postData = [];
        if ($post->type == "photo") {
            $postData = [
                "title" => $post->title,
                "url" => $post->image
            ];
        }
        if ($post->type == "video") {
            $postData = [
                "title" => $post->title,
                "file_url" => $post->video_key ?: $post->video
            ];
        }
        $rawMetadata = $post->metadata ?? $post->response;
        if (!empty($rawMetadata)) {
            $metadata = is_array($rawMetadata) ? $rawMetadata : json_decode($rawMetadata, true);
            if (is_array($metadata)) {
                $postData = array_merge($postData, $metadata);
            }
        }

        return $postData;
    }

    private static function trackUrlsInText($text, $userId, $utmContext = null)
    {
        if (empty($text) || empty($userId)) {
            return $text;
        }

        $urlPattern = '/(?:https?:\/\/|www\.)[^\s<>"{}|\\^`\[\]]+/i';

        return preg_replace_callback($urlPattern, function ($matches) use ($userId, $utmContext) {
            $matchedUrl = $matches[0];
            $urlToTrack = $matchedUrl;
            if (stripos($matchedUrl, 'www.') === 0 && stripos($matchedUrl, 'http://') === false && stripos($matchedUrl, 'https://') === false) {
                $urlToTrack = 'http://' . $matchedUrl;
            }
            return UtmService::appendUtmCodes($urlToTrack, $userId, $utmContext);
        }, $text);
    }
}
