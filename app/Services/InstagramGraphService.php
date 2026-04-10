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
        if ($type === 'video') {
            $this->publishVideoOrReelPost($post, $accessToken, 'VIDEO');

            return;
        }
        if ($type === 'reel') {
            $this->publishVideoOrReelPost($post, $accessToken, 'REELS');

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

        if (! $this->waitForMediaContainerReady($base, (string) $creationId, $accessToken, false)) {
            $this->failPost($post, 'Instagram media container did not become ready in time.');

            return;
        }

        $this->finishMediaPublish($post, $igUserId, (string) $creationId, $accessToken, 'Photo published successfully to Instagram');
    }

    /**
     * @param  'VIDEO'|'REELS'  $mediaType
     */
    private function publishVideoOrReelPost(Post $post, string $accessToken, string $mediaType): void
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

        $payload = array_filter([
            'media_type' => $mediaType,
            'video_url' => $videoUrl,
            'caption' => $body['caption'] ?? null,
            'access_token' => $accessToken,
        ]);

        if ($mediaType === 'REELS') {
            $payload['share_to_feed'] = 'true';
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

        if (! $this->waitForMediaContainerReady($base, (string) $creationId, $accessToken, true)) {
            $this->failPost($post, 'Instagram video container did not become ready in time.');

            return;
        }

        $successMsg = $mediaType === 'REELS'
            ? 'Reel published successfully to Instagram'
            : 'Video published successfully to Instagram';

        $this->finishMediaPublish($post, $igUserId, (string) $creationId, $accessToken, $successMsg);
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

    private function waitForMediaContainerReady(string $base, string $creationId, string $accessToken, bool $isVideo): bool
    {
        $maxAttempts = $isVideo ? 90 : 45;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $statusResp = Http::acceptJson()
                ->timeout(60)
                ->get("{$base}/{$creationId}", [
                    'fields' => 'status_code',
                    'access_token' => $accessToken,
                ]);

            if (! $statusResp->successful()) {
                return false;
            }

            $payload = $statusResp->json();
            $code = is_array($payload)
                ? ($payload['status_code'] ?? data_get($payload, 'status.status_code') ?? data_get($payload, 'status'))
                : null;
            if (is_string($code)) {
                $code = strtoupper($code);
            }

            if ($code === 'FINISHED') {
                return true;
            }
            if (in_array($code, ['ERROR', 'EXPIRED'], true)) {
                Log::warning('Instagram container status error', ['creation_id' => $creationId, 'status_code' => $code]);

                return false;
            }

            sleep($isVideo ? 3 : 2);
        }

        return false;
    }

    private function formatGraphError(\Illuminate\Http\Client\Response $response): string
    {
        $json = $response->json();
        if (is_array($json) && ! empty($json['error']['message'])) {
            return (string) $json['error']['message'];
        }

        return $response->body() ?: 'Instagram Graph API request failed.';
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
