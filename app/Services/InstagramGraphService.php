<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramGraphService
{
    private function graphBaseUrl(): string
    {
        $v = (string) env('FACEBOOK_GRAPH_VERSION', 'v21.0');
        $v = ltrim($v, '/');

        return 'https://graph.facebook.com/'.$v;
    }

    /**
     * Publish photo, feed video, or reel via Instagram Content Publishing API.
     */
    public function publishPost(Post $post, string $accessToken): void
    {
        $post->loadMissing('instagramAccount');
        $ig = $post->instagramAccount;
        if (! $ig || empty($ig->ig_user_id)) {
            $this->failPost($post, 'Instagram account not found for this post.');

            return;
        }

        $type = (string) $post->type;
        if ($type === 'photo') {
            $this->publishPhotoPost($post, $accessToken);

            return;
        }
        if ($type === 'carousel') {
            $this->publishCarouselPost($post, $accessToken);

            return;
        }
        if ($type === 'video' || $type === 'reel') {
            // Meta deprecated media_type=VIDEO for standalone posts; use REELS + share_to_feed for feed-visible video.
            $this->publishVideoOrReelPost($post, $accessToken);

            return;
        }

        $this->failPost($post, 'Unsupported Instagram post type: '.$type);
    }

    /**
     * Publish a single-feed photo using Instagram Content Publishing API.
     */
    private function publishPhotoPost(Post $post, string $accessToken): void
    {
        $post->loadMissing('instagramAccount');
        $ig = $post->instagramAccount;
        if (! $ig || empty($ig->ig_user_id)) {
            $this->failPost($post, 'Instagram account not found for this post.');

            return;
        }

        $body = PostService::postTypeBody($post);
        $imageUrl = $body['image_url'] ?? null;
        if (empty($imageUrl)) {
            $this->failPost($post, 'Image URL is missing for Instagram publish.');

            return;
        }

        $base = $this->graphBaseUrl();
        $igUserId = $ig->ig_user_id;

        $create = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post("{$base}/{$igUserId}/media", array_filter([
                'image_url' => $imageUrl,
                'caption' => $body['caption'] ?? null,
                'access_token' => $accessToken,
            ]));

        if (! $create->successful()) {
            $msg = $this->formatGraphError($create);
            Log::warning('Instagram media container failed', ['post_id' => $post->id, 'message' => $msg]);
            $this->failPost($post, $msg);

            return;
        }

        $creationId = $create->json('id');
        if (empty($creationId)) {
            $this->failPost($post, 'Invalid response creating Instagram media container.');

            return;
        }

        $waitError = $this->waitForMediaContainerReady($base, (string) $creationId, $accessToken, false);
        if ($waitError !== null) {
            $this->failPost($post, $waitError);

            return;
        }

        $this->finishMediaPublish($post, $igUserId, (string) $creationId, $accessToken, 'Photo published successfully to Instagram');
    }

    /**
     * Single video / “feed video” via Content Publishing: use REELS container + share_to_feed (VIDEO is deprecated).
     */
    private function publishVideoOrReelPost(Post $post, string $accessToken): void
    {
        $post->loadMissing('instagramAccount');
        $ig = $post->instagramAccount;
        if (! $ig || empty($ig->ig_user_id)) {
            $this->failPost($post, 'Instagram account not found for this post.');

            return;
        }

        $body = PostService::postTypeBody($post);
        $videoUrl = $body['video_url'] ?? null;
        if (empty($videoUrl)) {
            $this->failPost($post, 'Video URL is missing for Instagram publish. Ensure the file is on a public HTTPS URL.');

            return;
        }

        $base = $this->graphBaseUrl();
        $igUserId = $ig->ig_user_id;

        $payload = [
            'media_type' => 'REELS',
            'video_url' => $videoUrl,
            'share_to_feed' => 'true',
            'access_token' => $accessToken,
        ];
        $caption = isset($body['caption']) ? trim((string) $body['caption']) : '';
        if ($caption !== '') {
            $payload['caption'] = $caption;
        }

        $create = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post("{$base}/{$igUserId}/media", $payload);

        if (! $create->successful()) {
            $msg = $this->formatGraphError($create);
            Log::warning('Instagram video container failed', ['post_id' => $post->id, 'message' => $msg]);
            $this->failPost($post, $msg);

            return;
        }

        $creationId = $create->json('id');
        if (empty($creationId)) {
            $this->failPost($post, 'Invalid response creating Instagram video container.');

            return;
        }

        $waitError = $this->waitForMediaContainerReady($base, (string) $creationId, $accessToken, true);
        if ($waitError !== null) {
            $this->failPost($post, $waitError);

            return;
        }

        $successMsg = ($post->type === 'reel')
            ? 'Reel published successfully to Instagram'
            : 'Video published successfully to Instagram';

        $this->finishMediaPublish($post, $igUserId, (string) $creationId, $accessToken, $successMsg);
    }

    /**
     * Carousel: create is_carousel_item children (images and/or videos), then CAROUSEL container, then media_publish.
     */
    private function publishCarouselPost(Post $post, string $accessToken): void
    {
        $post->loadMissing('instagramAccount');
        $ig = $post->instagramAccount;
        if (! $ig || empty($ig->ig_user_id)) {
            $this->failPost($post, 'Instagram account not found for this post.');

            return;
        }

        $body = PostService::postTypeBody($post);
        $items = $body['carousel_items'] ?? [];
        if (! is_array($items) || count($items) < 2) {
            $this->failPost($post, 'At least two media URLs are required for an Instagram carousel.');

            return;
        }
        if (count($items) > 10) {
            $this->failPost($post, 'Instagram carousels support at most 10 items.');

            return;
        }

        $base = $this->graphBaseUrl();
        $igUserId = $ig->ig_user_id;
        $childIds = [];

        foreach ($items as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }
            $kind = $item['type'] ?? 'image';
            $mediaUrl = trim((string) ($item['url'] ?? ''));
            if ($mediaUrl === '') {
                $this->failPost($post, 'Carousel media URL '.($idx + 1).' is empty.');

                return;
            }

            if ($kind === 'video') {
                $payload = [
                    'media_type' => 'VIDEO',
                    'video_url' => $mediaUrl,
                    'is_carousel_item' => 'true',
                    'access_token' => $accessToken,
                ];
                $isVideoChild = true;
            } else {
                $payload = array_filter([
                    'image_url' => $mediaUrl,
                    'is_carousel_item' => 'true',
                    'access_token' => $accessToken,
                ]);
                $isVideoChild = false;
            }

            $create = Http::asForm()
                ->acceptJson()
                ->timeout(120)
                ->post("{$base}/{$igUserId}/media", $payload);

            if (! $create->successful()) {
                $msg = $this->formatGraphError($create);
                Log::warning('Instagram carousel child container failed', ['post_id' => $post->id, 'index' => $idx, 'message' => $msg]);
                $this->failPost($post, $msg);

                return;
            }

            $creationId = $create->json('id');
            if (empty($creationId)) {
                $this->failPost($post, 'Invalid response creating Instagram carousel item '.($idx + 1).'.');

                return;
            }

            $waitError = $this->waitForMediaContainerReady($base, (string) $creationId, $accessToken, $isVideoChild);
            if ($waitError !== null) {
                $this->failPost($post, $waitError);

                return;
            }

            $childIds[] = (string) $creationId;
        }

        $payload = [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childIds),
            'access_token' => $accessToken,
        ];
        $caption = isset($body['caption']) ? trim((string) $body['caption']) : '';
        if ($caption !== '') {
            $payload['caption'] = $caption;
        }

        $createCarousel = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post("{$base}/{$igUserId}/media", $payload);

        if (! $createCarousel->successful()) {
            $msg = $this->formatGraphError($createCarousel);
            Log::warning('Instagram carousel container failed', ['post_id' => $post->id, 'message' => $msg]);
            $this->failPost($post, $msg);

            return;
        }

        $carouselCreationId = $createCarousel->json('id');
        if (empty($carouselCreationId)) {
            $this->failPost($post, 'Invalid response creating Instagram carousel container.');

            return;
        }

        $waitParent = $this->waitForMediaContainerReady($base, (string) $carouselCreationId, $accessToken, false);
        if ($waitParent !== null) {
            $this->failPost($post, $waitParent);

            return;
        }

        $this->finishMediaPublish($post, $igUserId, (string) $carouselCreationId, $accessToken, 'Carousel published successfully to Instagram');
    }

    private function finishMediaPublish(Post $post, string $igUserId, string $creationId, string $accessToken, string $successMessage): void
    {
        $base = $this->graphBaseUrl();

        $publish = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post("{$base}/{$igUserId}/media_publish", [
                'creation_id' => $creationId,
                'access_token' => $accessToken,
            ]);

        if (! $publish->successful()) {
            $msg = $this->formatGraphError($publish);
            Log::warning('Instagram media_publish failed', ['post_id' => $post->id, 'message' => $msg]);
            $this->failPost($post, $msg);

            return;
        }

        $mediaId = $publish->json('id');
        if (empty($mediaId)) {
            $this->failPost($post, 'Instagram publish response missing media id.');

            return;
        }

        $post->update([
            'post_id' => (string) $mediaId,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s'),
            'response' => json_encode([
                'success' => true,
                'post_id' => (string) $mediaId,
                'message' => $successMessage,
            ]),
        ]);

        $this->successNotification($post);
    }

    /**
     * Poll container until status is FINISHED. Video/Reels processing often needs several minutes.
     *
     * @return string|null Error message, or null when ready for media_publish
     */
    private function waitForMediaContainerReady(string $base, string $creationId, string $accessToken, bool $isVideo): ?string
    {
        // Reels/video transcoding can exceed 5–10+ minutes; photos are usually fast.
        $maxAttempts = $isVideo ? 200 : 60;
        $sleepSec = $isVideo ? 5 : 2;
        $maxWaitHuman = $maxAttempts * $sleepSec;

        $lastPayload = null;
        $lastCode = null;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $statusResp = Http::acceptJson()
                ->timeout(90)
                ->get("{$base}/{$creationId}", [
                    'fields' => 'id,status_code,status,copyright_check_status',
                    'access_token' => $accessToken,
                ]);

            if (! $statusResp->successful()) {
                $http = $statusResp->status();
                if ($http === 400 || $http === 401 || $http === 403) {
                    return 'Could not read Instagram container status: '.$this->formatGraphError($statusResp);
                }
                Log::warning('Instagram container status GET non-success', [
                    'creation_id' => $creationId,
                    'http' => $http,
                    'body' => $statusResp->body(),
                ]);
                sleep($sleepSec);

                continue;
            }

            $payload = $statusResp->json();
            $lastPayload = is_array($payload) ? $payload : null;
            $code = $this->normalizeInstagramContainerStatusCode($lastPayload);
            $lastCode = $code;

            if ($code === 'FINISHED') {
                return null;
            }
            if (in_array($code, ['ERROR', 'EXPIRED'], true)) {
                // Meta sometimes briefly reports ERROR while transcoding; one short recheck for video.
                if ($isVideo && $code === 'ERROR') {
                    sleep(10);
                    $recheck = Http::acceptJson()
                        ->timeout(90)
                        ->get("{$base}/{$creationId}", [
                            'fields' => 'id,status_code,status,copyright_check_status',
                            'access_token' => $accessToken,
                        ]);
                    if ($recheck->successful()) {
                        $recheckPayload = $recheck->json();
                        if (is_array($recheckPayload)) {
                            $lastPayload = $recheckPayload;
                            $retryCode = $this->normalizeInstagramContainerStatusCode($recheckPayload);
                            if ($retryCode === 'FINISHED') {
                                return null;
                            }
                            $lastCode = $retryCode ?? $lastCode;
                        }
                    }
                }

                Log::warning('Instagram container status error', [
                    'creation_id' => $creationId,
                    'status_code' => $code,
                    'payload' => $lastPayload,
                ]);

                return $this->formatInstagramContainerProcessingFailure($lastPayload ?? [], $code);
            }

            sleep($sleepSec);
        }

        $detail = $lastCode !== null
            ? ' Last reported status: '.$lastCode.'.'
            : '';
        $snapshot = '';
        if ($lastPayload !== null) {
            $snap = json_encode($lastPayload, JSON_UNESCAPED_SLASHES);
            if (strlen($snap) > 1200) {
                $snap = substr($snap, 0, 1200).'…';
            }
            $snapshot = ' Snapshot: '.$snap;
        }

        $kind = $isVideo ? 'video' : 'media';

        return 'Instagram '.$kind.' container did not become ready within '.$maxWaitHuman.'s.'.$detail.$snapshot
            .($isVideo ? ' For video, try a shorter/smaller MP4 (H.264), or use an async queue worker with a higher time limit than the web request.' : '');
    }

    /**
     * When status_code is ERROR/EXPIRED, Meta may put an error subcode or object in "status" (see IG Container docs).
     *
     * @param  array<string, mixed>  $payload
     */
    private function formatInstagramContainerProcessingFailure(array $payload, string $statusCode): string
    {
        $parts = ['Instagram media container failed during processing.', 'status_code: '.$statusCode.'.'];

        $st = $payload['status'] ?? null;
        if (is_int($st) || (is_string($st) && is_numeric($st) && $st !== '')) {
            $sub = (int) $st;
            $parts[] = 'Meta error subcode: '.$sub.'.';
            $hint = $this->instagramContainerErrorSubcodeHint($sub);
            if ($hint !== null) {
                $parts[] = $hint;
            }
        } elseif (is_string($st) && $st !== '' && strtoupper(trim($st)) !== strtoupper($statusCode)) {
            $parts[] = 'status: '.$st.'.';
        } elseif (is_array($st)) {
            $encoded = json_encode($st, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                $parts[] = 'details: '.$encoded.'.';
            }
        }

        $cc = $payload['copyright_check_status'] ?? null;
        if (is_array($cc)) {
            $matches = $cc['matches_found'] ?? null;
            if ($matches === true || $matches === 'true') {
                $parts[] = 'Copyright check: possible rights conflict reported for this video.';
            }
            $parts[] = 'copyright_check_status: '.json_encode($cc, JSON_UNESCAPED_SLASHES).'.';
        }

        $parts[] = 'For Reels: direct HTTPS MP4 (no login/HTML interstitials), H.264 + AAC, duration/aspect/size within Instagram limits.';

        $raw = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($raw !== false && $raw !== '' && $raw !== '[]') {
            $parts[] = (strlen($raw) > 900 ? substr($raw, 0, 900).'…' : $raw);
        }

        return implode(' ', $parts);
    }

    private function instagramContainerErrorSubcodeHint(int $subcode): ?string
    {
        return match ($subcode) {
            2207001 => 'Meta could not process the media (server/transcode). Retry later or re-encode.',
            2207003 => 'Meta timed out downloading video_url — use a fast public URL; avoid slow hosts and auth redirects.',
            2207004 => 'Media format or specs rejected — use MP4 H.264, AAC audio; check duration, dimensions, and file size.',
            2207010 => 'Invalid media — confirm the file opens as video and meets Reels technical requirements.',
            2207050, 2207051, 2207052, 2207053 => 'Upload/processing failed — re-encode or use a smaller/shorter clip; verify video_url is stable.',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function normalizeInstagramContainerStatusCode(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        if (! empty($payload['status_code']) && is_string($payload['status_code'])) {
            return strtoupper(trim($payload['status_code']));
        }

        $status = $payload['status'] ?? null;
        if (is_int($status)) {
            return null;
        }
        if (is_string($status) && $status !== '') {
            return strtoupper(trim($status));
        }
        if (is_array($status)) {
            foreach (['status_code', 'name', 'coding'] as $key) {
                if (! empty($status[$key]) && is_string($status[$key])) {
                    return strtoupper(trim((string) $status[$key]));
                }
            }
        }

        return null;
    }

    private function formatGraphError(\Illuminate\Http\Client\Response $response): string
    {
        $json = $response->json();
        if (! is_array($json) || empty($json['error']) || ! is_array($json['error'])) {
            return $response->body() ?: 'Instagram Graph API request failed.';
        }

        $err = $json['error'];
        $parts = [];
        if (! empty($err['message'])) {
            $parts[] = (string) $err['message'];
        }
        if (! empty($err['error_user_msg'])) {
            $parts[] = (string) $err['error_user_msg'];
        }
        if (isset($err['error_subcode'])) {
            $parts[] = '(error_subcode: '.$err['error_subcode'].')';
        }

        $msg = implode(' ', array_filter(array_unique($parts)));

        return $msg !== '' ? $msg : ($response->body() ?: 'Instagram Graph API request failed.');
    }

    private function failPost(Post $post, string $message): void
    {
        $post->update([
            'status' => -1,
            'published_at' => date('Y-m-d H:i:s'),
            'response' => json_encode([
                'success' => false,
                'error' => $message,
            ]),
        ]);

        $this->errorNotification($post, $message);
    }

    private function successNotification(Post $post): void
    {
        $post->loadMissing('instagramAccount');
        $ig = $post->instagramAccount;
        $accountImage = $ig?->profile_image ?? null;

        $msg = match ((string) $post->type) {
            'video' => 'Your Instagram video has been published successfully.',
            'reel' => 'Your Instagram reel has been published successfully.',
            'carousel' => 'Your Instagram carousel has been published successfully.',
            default => 'Your Instagram photo has been published successfully.',
        };

        Notification::create([
            'user_id' => $post->user_id,
            'title' => 'Post Published',
            'body' => [
                'type' => 'success',
                'message' => $msg,
                'social_type' => 'instagram',
                'account_image' => $accountImage,
                'account_name' => $ig?->name ?? $ig?->username ?? '',
                'account_username' => $ig?->username ?? '',
            ],
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    private function errorNotification(Post $post, string $message): void
    {
        $post->loadMissing('instagramAccount');
        $ig = $post->instagramAccount;
        $accountImage = $ig?->profile_image ?? null;

        $kind = match ((string) $post->type) {
            'video' => 'video',
            'reel' => 'reel',
            'carousel' => 'carousel',
            default => 'photo',
        };

        Notification::create([
            'user_id' => $post->user_id,
            'title' => 'Post Publishing Failed',
            'body' => [
                'type' => 'error',
                'message' => 'Failed to publish Instagram '.$kind.'. '.$message,
                'social_type' => 'instagram',
                'account_image' => $accountImage,
                'account_name' => $ig?->name ?? $ig?->username ?? '',
                'account_username' => $ig?->username ?? '',
            ],
            'is_read' => false,
            'is_system' => false,
        ]);
    }
}
