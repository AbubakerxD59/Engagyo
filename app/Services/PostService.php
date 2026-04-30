<?php

namespace App\Services;

use App\Jobs\DeleteFacebookPostJob;
use App\Jobs\PublishFacebookPost;
use App\Jobs\PublishInstagramPost;
use App\Jobs\PublishPinterestPost;
use App\Jobs\PublishThreadsPost;
use App\Models\Board;
use App\Models\InstagramAccount;
use App\Models\Linkedin;
use App\Models\Page;
use App\Models\Post;
use App\Models\Thread;
use App\Models\Tiktok;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            'user_id' => $data['user_id'],
            'account_id' => $data['account_id'],
            'social_type' => $data['social_type'],
            'type' => $data['type'],
            'source' => $data['source'],
            'title' => isset($data['title']) ? $data['title'] : null,
            'description' => isset($data['description']) ? $data['description'] : null,
            'comment' => isset($data['comment']) ? $data['comment'] : null,
            'domain_id' => isset($data['domain_id']) ? $data['domain_id'] : null,
            'url' => isset($data['url']) ? $data['url'] : null,
            'image' => isset($data['image']) ? $data['image'] : null,
            'publish_date' => $data['publish_date'],
            'scheduled' => isset($data['scheduled']) ? $data['scheduled'] : 0,
            'status' => 0,
            'video' => isset($data['video']) ? $data['video'] : 0,
            'metadata' => isset($data['metadata']) ? $data['metadata'] : null,
        ]);

        return $post;
    }

    public static function delete($post_id)
    {
        $post = Post::with('page', 'board.pinterest', 'tiktok', 'instagramAccount', 'thread')->where('id', $post_id)->first();
        if ($post) {
            $status = $post->status;
            $social_type = $post->social_type;
            $socialType = strtolower((string) $social_type);
            $facebookPostId = $post->post_id ?? null;
            $pageId = $post->account_id;
            $dbPostId = $post->id;

            // Pinterest: delete from API synchronously (needs post object)
            if ($status == 1 && $social_type == 'pinterest' && ! empty($post->post_id)) {
                $service = new PinterestService;
                $service->delete($post);
            }

            // Threads: delete from Threads API before deleting local row.
            if ($status == 1 && str_contains($socialType, 'thread') && ! empty($post->post_id)) {
                $thread = $post->thread;
                if (! $thread || empty($thread->access_token)) {
                    throw new Exception('Threads access token is missing. Reconnect your Threads account and try again.');
                }

                $endpoint = 'https://graph.threads.net/v1.0/'.rawurlencode((string) $post->post_id)
                    .'?access_token='.urlencode((string) $thread->access_token);

                $response = Http::acceptJson()
                    ->timeout(30)
                    ->delete($endpoint);

                if (! $response->successful() || ! $response->json('success')) {
                    $apiMessage = (string) ($response->json('error.message')
                        ?? $response->json('message')
                        ?? 'Failed to delete Threads post.');
                    Log::warning('Threads delete failed', [
                        'post_id' => $post->id,
                        'threads_media_id' => $post->post_id,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                    throw new Exception($apiMessage);
                }
            }

            // Delete from database instantly
            $post->delete();

            // Facebook: delete via background job (user gets instant response)
            if ($status == 1 && $social_type == 'facebook' && ! empty($facebookPostId)) {
                DeleteFacebookPostJob::dispatch($facebookPostId, $pageId, $dbPostId);
            }
        }

        return true;
    }

    public static function publishNow($id)
    {
        try {
            $user = Auth::user();
            $post = Post::with('page.facebook', 'board.pinterest', 'instagramAccount', 'thread')->where('status', '!=', 1)->where('id', $id)->firstOrFail();
            if ($post->social_type == 'facebook') {
                $page = $post->page;
                $response = FacebookService::validateToken($page);
                if ($response['success']) {
                    $postData = self::postTypeBody($post);
                    PublishFacebookPost::dispatch($post->id, $postData, $response['access_token'], $post->type, $post->comment);
                } else {
                    return $response;
                }
            }
            if ($post->social_type == 'pinterest') {
                $postData = self::postTypeBody($post);
                PublishPinterestPost::dispatch($post->id, $postData, $post->board->pinterest->access_token, $post->type);
            }
            if (str_contains(strtolower((string) $post->social_type), 'instagram')) {
                $ig = $post->instagramAccount;
                if (! $ig) {
                    return [
                        'success' => false,
                        'message' => 'Instagram account not found for this post.',
                    ];
                }
                if (! $ig->validToken()) {
                    return [
                        'success' => false,
                        'message' => 'Instagram access token expired. Reconnect your Instagram account.',
                    ];
                }
                PublishInstagramPost::dispatch($post->id);
            }
            if (str_contains(strtolower((string) $post->social_type), 'threads')) {
                $thread = $post->thread;
                if (! $thread) {
                    return [
                        'success' => false,
                        'message' => 'Threads account not found for this post.',
                    ];
                }
                if (! $thread->validToken()) {
                    return [
                        'success' => false,
                        'message' => 'Threads access token expired. Reconnect your Threads account.',
                    ];
                }
                PublishThreadsPost::dispatch($post->id);
            }
            $response = [
                'success' => true,
                'message' => 'Your Post is being Published.',
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    private static function applyUtmCodesToData(array &$data): void
    {
        if (empty($data['user_id'])) {
            return;
        }

        $utmContext = null;
        if (! empty($data['social_type']) && ! empty($data['account_id'])) {
            $utmContext = [
                'social_type' => $data['social_type'] ?? null,
                'account_id' => $data['account_id'] ?? null,
                'type' => $data['type'] ?? null,
            ];
        }

        if (! empty($data['url'])) {
            $data['url'] = UtmService::appendUtmCodes($data['url'], $data['user_id'], $utmContext);
        }
        if (! empty($data['title'])) {
            $data['title'] = self::trackUrlsInText($data['title'], $data['user_id'], $utmContext);
        }
        if (! empty($data['comment'])) {
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
            'threads' => Thread::find($data['account_id']),
            'linkedin' => Linkedin::find($data['account_id']),
            default => null,
        };

        if (! $account || ! ($account->url_shortener_enabled ?? false)) {
            return;
        }

        $user = \App\Models\User::find($data['user_id']);
        if (! $user) {
            return;
        }

        $urlShortener = app(UrlShortenerService::class);

        if (! empty($data['url'])) {
            $result = $urlShortener->shortenForUser($user, $data['url']);
            $data['url'] = $result['success'] ? $result['short_url'] : $data['url'];
        }
        if (! empty($data['title'])) {
            $data['title'] = $urlShortener->shortenUrlsInText($user, $data['title'], true);
        }
        if (! empty($data['comment'])) {
            $data['comment'] = $urlShortener->shortenUrlsInText($user, $data['comment'], true);
        }
    }

    public static function postTypeBody($post)
    {
        $st = strtolower(trim((string) $post->social_type));

        return match (true) {
            str_contains($st, 'instagram') => self::instagramPostTypeBody($post),
            str_contains($st, 'pinterest') => self::pinterestPostTypeBody($post),
            str_contains($st, 'tiktok') => self::tiktokPostTypeBody($post),
            str_contains($st, 'facebook') => self::facebookPostTypeBody($post),
            str_contains($st, 'threads') => self::threadsPostTypeBody($post),
            default => [],
        };
    }

    public static function threadsPostTypeBody(Post $post): array
    {
        $attrs = $post->getAttributes();
        $title = trim((string) ($attrs['title'] ?? ''));
        $comment = trim((string) ($attrs['comment'] ?? ''));
        $text = $title;
        if ($comment !== '') {
            $text = $text !== '' ? $text."\n\n".$comment : $comment;
        }

        $resolveImage = function ($raw): ?string {
            if ($raw === null || $raw === '') {
                return null;
            }

            return self::ensureAbsoluteMediaUrl((string) $raw, 'image');
        };

        $resolveVideo = function ($raw): ?string {
            if ($raw === null || $raw === '') {
                return null;
            }

            return self::ensureAbsoluteMediaUrl((string) $raw, 'video');
        };

        $body = [
            'text' => $text,
            'type' => 'text',
            'media_type' => 'TEXT',
        ];

        if (($post->type ?? '') === 'carousel') {
            $meta = [];
            if (! empty($attrs['metadata'])) {
                $decoded = is_string($attrs['metadata']) ? json_decode($attrs['metadata'], true) : $attrs['metadata'];
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            $items = $meta['threads_carousel'] ?? [];
            if (! is_array($items) || $items === []) {
                $items = $meta['ig_carousel'] ?? [];
            }

            $carouselItems = [];
            foreach ($items as $it) {
                if (! is_array($it)) {
                    continue;
                }
                if (! empty($it['video'])) {
                    $url = $resolveVideo($it['video']);
                    if ($url) {
                        $carouselItems[] = ['type' => 'video', 'url' => $url];
                    }
                } elseif (! empty($it['image'])) {
                    $url = $resolveImage($it['image']);
                    if ($url) {
                        $carouselItems[] = ['type' => 'image', 'url' => $url];
                    }
                }
            }

            return [
                'type' => 'carousel',
                'text' => $text,
                'carousel_items' => $carouselItems,
            ];
        }

        $rawImage = $attrs['image'] ?? null;
        $rawVideo = $attrs['video'] ?? null;
        if (! empty($rawVideo)) {
            $body['type'] = 'video';
            $body['media_type'] = 'VIDEO';
            $body['video_url'] = $resolveVideo($rawVideo);

            return $body;
        }
        if (! empty($rawImage)) {
            $body['type'] = 'image';
            $body['media_type'] = 'IMAGE';
            $body['image_url'] = $resolveImage($rawImage);

            return $body;
        }

        return $body;
    }

    /**
     * Normalize stored media path/value into an absolute URL with domain.
     */
    private static function ensureAbsoluteMediaUrl(string $raw, string $kind = 'image'): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'https://') || str_starts_with($raw, 'http://')) {
            return $raw;
        }

        if (str_starts_with($raw, '//')) {
            return 'https:'.$raw;
        }

        if (str_starts_with($raw, '/')) {
            return url($raw);
        }

        if (
            str_starts_with($raw, 'uploads/') ||
            str_starts_with($raw, 'images/') ||
            str_starts_with($raw, 'storage/')
        ) {
            return asset($raw);
        }

        if ($kind === 'video') {
            $videoUrl = fetchFromS3($raw);
            if (is_string($videoUrl) && trim($videoUrl) !== '') {
                $videoUrl = trim($videoUrl);
                if (str_starts_with($videoUrl, 'https://') || str_starts_with($videoUrl, 'http://')) {
                    return $videoUrl;
                }
                if (str_starts_with($videoUrl, '//')) {
                    return 'https:'.$videoUrl;
                }
                if (str_starts_with($videoUrl, '/')) {
                    return url($videoUrl);
                }

                return asset($videoUrl);
            }
        }

        return url(getImage('', $raw));
    }

    /**
     * Payload for Instagram Content Publishing (image_url / video_url / carousel_items / caption).
     *
     * @see https://developers.facebook.com/docs/instagram-platform/content-publishing/
     */
    public static function instagramPostTypeBody(Post $post): array
    {
        $attrs = $post->getAttributes();
        $title = trim((string) ($attrs['title'] ?? ''));
        $comment = trim((string) ($attrs['comment'] ?? ''));
        $caption = $title;
        if ($comment !== '') {
            $caption = $caption !== '' ? $caption."\n\n".$comment : $comment;
        }

        $rawImage = $attrs['image'] ?? null;
        $rawVideo = $attrs['video'] ?? null;

        $resolveImage = function ($raw): ?string {
            if ($raw === null || $raw === '') {
                return null;
            }
            $raw = (string) $raw;
            if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                return $raw;
            }

            return url(getImage('', $raw));
        };

        $resolveVideo = function ($raw): ?string {
            if ($raw === null || $raw === '') {
                return null;
            }
            $raw = (string) $raw;
            if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                return $raw;
            }

            return fetchFromS3($raw);
        };

        $body = [];
        if ($caption !== '') {
            $body['caption'] = $caption;
        }

        if ($post->type === 'carousel') {
            $meta = [];
            if (! empty($attrs['metadata'])) {
                $decoded = is_string($attrs['metadata']) ? json_decode($attrs['metadata'], true) : $attrs['metadata'];
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            $items = $meta['ig_carousel'] ?? [];
            $carouselItems = [];
            foreach ($items as $it) {
                if (! is_array($it)) {
                    continue;
                }
                if (! empty($it['video'])) {
                    $url = $resolveVideo($it['video']);
                    if ($url) {
                        $carouselItems[] = ['type' => 'video', 'url' => $url];
                    }
                } elseif (! empty($it['image'])) {
                    $url = $resolveImage($it['image']);
                    if ($url) {
                        $carouselItems[] = ['type' => 'image', 'url' => $url];
                    }
                }
            }
            $body['carousel_items'] = $carouselItems;

            return $body;
        }

        if ($post->type === 'story') {
            if (! empty($rawVideo)) {
                $body['video_url'] = $resolveVideo($rawVideo);
            } elseif (! empty($rawImage)) {
                $body['image_url'] = $resolveImage($rawImage);
            }

            return $body;
        }

        if ($post->type === 'photo') {
            $body['image_url'] = $resolveImage($rawImage);

            return $body;
        }

        if ($post->type === 'reel' || $post->type === 'video') {
            $body['video_url'] = $resolveVideo($rawVideo);
            $body['share_to_feed'] = $post->type === 'video';

            return $body;
        }

        return $body;
    }

    public static function facebookPostTypeBody($post)
    {
        $postData = [];
        if ($post->type == 'content_only') {
            $postData = [
                'message' => $post->title ?: ' ',
            ];
        }
        if ($post->type == 'photo') {
            $postData = [
                'url' => $post->image,
            ];
            if (! empty($post->title)) {
                $postData['message'] = $post->title;
            }
        }
        if ($post->type == 'video') {
            $postData = [
                'file_url' => $post->video_key,
            ];
            if (! empty($post->title)) {
                $postData['description'] = $post->title;
            }
        }
        if ($post->type == 'reel') {
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
        if ($post->type == 'story') {
            $hasVideo = ! empty($post->video_key) || ! empty($post->video);
            $postData = [
                'media_kind' => $hasVideo ? 'video' : 'photo',
            ];

            if (! $hasVideo) {
                // Ensure photo_url is an absolute URL for Facebook API
                $photo = $post->image;
                if (! empty($photo) && ! str_starts_with($photo, 'http')) {
                    $photo = fetchFromS3($photo);
                }
                $postData['photo_url'] = $photo;
            } else {
                // Ensure video_url is an absolute URL (prefer S3 key accessor, fallback to raw video field)
                $videoUrl = $post->video_key ?: $post->video;
                if (! empty($videoUrl) && ! str_starts_with($videoUrl, 'http')) {
                    $videoUrl = fetchFromS3($videoUrl);
                }
                $postData['video_url'] = $videoUrl;
            }

            if (! empty($post->title)) {
                $postData['caption'] = $post->title;
            }
        }
        if ($post->type == 'link') {
            $postData = [
                'link' => $post->url,
            ];
            if (! empty($post->title)) {
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
        if ($post->type == 'photo') {
            $encoded_image = file_get_contents($post->image);
            $encoded_image = base64_encode($encoded_image);
            $postData = [
                'title' => $post->title,
                'board_id' => (string) $board_id,
                'media_source' => [
                    'source_type' => 'image_base64',
                    'content_type' => 'image/jpeg',
                    'data' => $encoded_image,
                ],
            ];
        }
        if ($post->type == 'video') {
            $postData = [
                'title' => $post->title,
                'board_id' => (string) $board_id,
                'video_key' => $post->video,
            ];
        }
        if ($post->type == 'link') {
            $postData = [
                'title' => $post->title,
                'link' => $post->url,
                'board_id' => (string) $board_id,
                'media_source' => [
                    'source_type' => str_contains($post->image, 'http') ? 'image_url' : 'image_base64',
                    'url' => $post->image,
                ],
            ];
        }

        return $postData;
    }

    public static function tiktokPostTypeBody($post)
    {
        $postData = [];
        if ($post->type == 'photo') {
            $postData = [
                'title' => $post->title,
                'url' => $post->image,
            ];
        }
        if ($post->type == 'video') {
            $postData = [
                'title' => $post->title,
                'file_url' => $post->video_key ?: $post->video,
            ];
        }
        $rawMetadata = $post->metadata ?? $post->response;
        if (! empty($rawMetadata)) {
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
                $urlToTrack = 'http://'.$matchedUrl;
            }

            return UtmService::appendUtmCodes($urlToTrack, $userId, $utmContext);
        }, $text);
    }
}
