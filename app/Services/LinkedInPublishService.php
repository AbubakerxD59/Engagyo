<?php

namespace App\Services;

use App\Models\Linkedin;
use App\Models\Notification;
use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedInPublishService
{
    private const API_BASE_V2 = 'https://api.linkedin.com/v2';

    private const RESTLI_VERSION = '2.0.0';

    /**
     * Queue job entrypoint: load post, validate account/token, call LinkedIn API,
     * persist status/response, and create user notifications (success or error).
     */
    public function publishQueuedPost(int $postId): void
    {
        $post = Post::with('linkedin')->find($postId);
        if (! $post || $post->social_type !== 'linkedin') {
            Log::error("LinkedInPublishService: missing post or not LinkedIn (id {$postId})");

            return;
        }

        $account = $post->linkedin;
        if (! $account) {
            $this->failQueuedPublish($post, [
                'success' => false,
                'message' => 'LinkedIn account not found for this post.',
            ]);

            return;
        }

        if (! $account->validToken()) {
            $this->failQueuedPublish($post, [
                'success' => false,
                'message' => 'LinkedIn access token expired. Please reconnect LinkedIn.',
            ]);

            return;
        }

        try {
            $result = $this->publish($post, $account);
            if (! ($result['success'] ?? false)) {
                $this->failQueuedPublish($post, $result);

                return;
            }

            $post->update([
                'status' => 1,
                'post_id' => $result['id'] ?? null,
                'published_at' => now(),
                'response' => json_encode($result),
            ]);
            $typeLabel = $this->linkedinTypeLabel($post);
            $this->successNotification(
                $post->user_id,
                'Post Published',
                "Your LinkedIn {$typeLabel} has been published successfully.",
                $post
            );
        } catch (\Throwable $e) {
            Log::error('LinkedInPublishService: publishQueuedPost exception', [
                'postId' => $postId,
                'error' => $e->getMessage(),
            ]);
            $this->failQueuedPublish($post, [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Called from {@see PublishLinkedInPost} when handle() catches an exception or when the job {@see PublishLinkedInPost::failed()} hook runs (e.g. timeout).
     * Skips if the post already published or already marked failed (avoids duplicate notifications).
     */
    public function publishQueuedPostFailed(int $postId, string $exceptionMessage): void
    {
        $post = Post::with('linkedin')->find($postId);
        if (! $post || $post->social_type !== 'linkedin') {
            return;
        }
        $status = (int) $post->status;
        if ($status === 1 || $status === -1) {
            return;
        }

        $this->failQueuedPublish($post, [
            'success' => false,
            'message' => 'Publishing job failed: '.$exceptionMessage,
        ]);
    }

    /**
     * Persist failed publish state and create the user error notification when {@see $responsePayload} indicates failure.
     */
    private function failQueuedPublish(Post $post, array $responsePayload): void
    {
        if (($responsePayload['success'] ?? false) === true) {
            return;
        }

        $this->markPostFailed($post, $responsePayload);
        $typeLabel = $this->linkedinTypeLabel($post);
        $detail = $this->flattenPublishErrorMessage($responsePayload);
        try {
            $this->errorNotification(
                $post->user_id,
                'Post Publishing Failed',
                "Failed to publish LinkedIn {$typeLabel}. {$detail}",
                $post
            );
        } catch (\Throwable $e) {
            Log::warning('LinkedInPublishService: could not create failure notification', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function publish(Post $post, Linkedin $account): array
    {
        $token = (string) ($account->getRawOriginal('access_token') ?? $account->access_token ?? '');
        if ($token === '') {
            return ['success' => false, 'message' => 'LinkedIn access token is missing.'];
        }

        $authorUrn = 'urn:li:person:' . $account->linkedin_id;
        $ugcPayload = $this->buildUgcPayload($post, $authorUrn, $token);
        if (! ($ugcPayload['success'] ?? false)) {
            return $ugcPayload;
        }

        $response = Http::withHeaders($this->jsonHeaders($token))
            ->post(self::API_BASE_V2 . '/ugcPosts', $ugcPayload['payload']);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => $response->json('message')
                    ?? $response->json('error.message')
                    ?? 'LinkedIn post publish failed.',
                'response' => $response->body(),
            ];
        }

        $id = (string) ($response->header('x-restli-id') ?? '');
        if ($id === '') {
            $id = (string) ($response->json('id') ?? '');
        }

        return [
            'success' => true,
            'id' => $id,
            'response' => $response->json() ?: ['raw' => $response->body()],
        ];
    }

    /**
     * Delete a published UGC post on LinkedIn. {@see https://learn.microsoft.com/en-us/linkedin/compliance/integrations/shares/ugc-post-api}
     * {@code post_id} on our {@see Post} row is the ugcPost or share URN returned at publish time.
     *
     * @return array{success: bool, message?: string}
     */
    public function deletePublishedUgcPost(Linkedin $account, string $ugcPostUrn): array
    {
        $ugcPostUrn = trim($ugcPostUrn);
        if ($ugcPostUrn === '') {
            return ['success' => false, 'message' => 'Missing LinkedIn post URN.'];
        }

        $token = (string) ($account->getRawOriginal('access_token') ?? $account->access_token ?? '');
        if ($token === '') {
            return ['success' => false, 'message' => 'LinkedIn access token is missing.'];
        }

        if (! $account->validToken()) {
            return ['success' => false, 'message' => 'LinkedIn access token expired. Please reconnect LinkedIn.'];
        }

        $encoded = rawurlencode($ugcPostUrn);
        $response = Http::withHeaders($this->jsonHeaders($token))
            ->timeout(30)
            ->delete(self::API_BASE_V2.'/ugcPosts/'.$encoded);

        if ($response->status() === 204 || $response->successful()) {
            return ['success' => true];
        }

        return [
            'success' => false,
            'message' => (string) ($response->json('message')
                ?? $response->json('error.message')
                ?? 'Failed to delete LinkedIn post.'),
            'status' => $response->status(),
            'response' => $response->body(),
        ];
    }

    private function buildUgcPayload(Post $post, string $authorUrn, string $token): array
    {
        $commentary = $this->buildCommentary($post);
        $mediaCategory = 'NONE';
        $media = [];

        if ($post->type === 'photo') {
            $imageUrl = $this->resolveImageUrl($post);
            if ($imageUrl === '') {
                return ['success' => false, 'message' => 'Photo publish requires a valid image URL or uploaded image.'];
            }
            $assetResult = $this->registerAndUploadAsset($token, $authorUrn, 'image', $imageUrl);
            if (! ($assetResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Failed to register/upload image asset to LinkedIn.',
                    'details' => $assetResult,
                ];
            }
            $assetUrn = (string) ($assetResult['asset'] ?? '');
            $mediaCategory = 'IMAGE';
            $media[] = [
                'status' => 'READY',
                'media' => $assetUrn,
                'title' => ['text' => $this->mediaTitle($post)],
            ];
        } elseif ($post->type === 'video') {
            $videoUrl = $this->resolveVideoUrl($post);
            if ($videoUrl === '') {
                return ['success' => false, 'message' => 'Video publish requires a valid video URL or uploaded video.'];
            }
            $assetResult = $this->registerAndUploadAsset($token, $authorUrn, 'video', $videoUrl);
            if (! ($assetResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Failed to register/upload video asset to LinkedIn.',
                    'details' => $assetResult,
                ];
            }
            $assetUrn = (string) ($assetResult['asset'] ?? '');
            $mediaCategory = 'VIDEO';
            $media[] = [
                'status' => 'READY',
                'media' => $assetUrn,
                'title' => ['text' => $this->mediaTitle($post)],
            ];
        } elseif ($post->type === 'carousel') {
            return [
                'success' => false,
                'message' => 'LinkedIn does not support carousel posts.',
            ];
        } elseif ($post->type === 'document') {
            return [
                'success' => false,
                'message' => 'LinkedIn Share API (ugcPosts) does not support document publishing in this implementation.',
            ];
        }

        $shareContent = [
            'shareCommentary' => [
                'text' => $commentary,
            ],
            'shareMediaCategory' => $mediaCategory,
        ];
        if ($media !== []) {
            $shareContent['media'] = $media;
        }

        return [
            'success' => true,
            'payload' => [
                'author' => $authorUrn,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => $shareContent,
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ],
        ];
    }

    private function buildCommentary(Post $post): string
    {
        $title = trim((string) ($post->title ?? ''));
        $comment = trim((string) ($post->comment ?? ''));
        if ($title !== '' && $comment !== '') {
            return $title . "\n\n" . $comment;
        }

        return $title !== '' ? $title : $comment;
    }

    private function mediaTitle(Post $post): string
    {
        $title = trim((string) ($post->title ?? ''));

        return $title !== '' ? $title : 'LinkedIn media';
    }

    private function registerAndUploadAsset(string $token, string $ownerUrn, string $kind, string $sourceUrl): array
    {
        $recipe = $kind === 'video'
            ? 'urn:li:digitalmediaRecipe:feedshare-video'
            : 'urn:li:digitalmediaRecipe:feedshare-image';

        $registerResponse = Http::withHeaders($this->jsonHeaders($token))
            ->post(self::API_BASE_V2 . '/assets?action=registerUpload', [
                'registerUploadRequest' => [
                    'recipes' => [$recipe],
                    'owner' => $ownerUrn,
                    'serviceRelationships' => [
                        [
                            'relationshipType' => 'OWNER',
                            'identifier' => 'urn:li:userGeneratedContent',
                        ],
                    ],
                ],
            ]);

        if (! $registerResponse->successful()) {
            return [
                'success' => false,
                'stage' => 'register_upload',
                'status' => $registerResponse->status(),
                'response' => $registerResponse->json() ?: $registerResponse->body(),
            ];
        }

        $registerJson = $registerResponse->json();
        $value = is_array($registerJson) ? ($registerJson['value'] ?? []) : [];
        $uploadMechanism = is_array($value) ? ($value['uploadMechanism'] ?? []) : [];
        $httpRequestMechanism = is_array($uploadMechanism)
            ? ($uploadMechanism['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest'] ?? [])
            : [];

        $uploadUrl = is_array($httpRequestMechanism) ? (string) ($httpRequestMechanism['uploadUrl'] ?? '') : '';
        $asset = is_array($value) ? (string) ($value['asset'] ?? '') : '';
        if ($uploadUrl === '' || $asset === '') {
            return [
                'success' => false,
                'stage' => 'register_upload_parse',
                'response' => $registerJson ?: $registerResponse->body(),
            ];
        }

        $binaryResponse = Http::timeout(120)->get($sourceUrl);
        if (! $binaryResponse->successful()) {
            return [
                'success' => false,
                'stage' => 'fetch_source_binary',
                'status' => $binaryResponse->status(),
                'source_url' => $sourceUrl,
                'response' => $binaryResponse->body(),
            ];
        }

        $contentType = (string) ($binaryResponse->header('Content-Type') ?: 'application/octet-stream');
        $upload = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => $contentType,
        ])
            ->withBody($binaryResponse->body(), $contentType)
            ->put($uploadUrl);

        if (! $upload->successful()) {
            return [
                'success' => false,
                'stage' => 'upload_binary',
                'status' => $upload->status(),
                'response' => $upload->body(),
            ];
        }

        return [
            'success' => true,
            'asset' => $asset,
            'upload_status' => $upload->status(),
        ];
    }

    private function resolveImageUrl(Post $post): string
    {
        $raw = (string) ($post->image ?? '');
        // $raw = (string) ($post->getRawOriginal('image') ?: $post->image ?: '');
        if ($raw === '') {
            return '';
        }

        return $this->resolveImagePath($raw);
    }

    private function resolveImagePath(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->ensureAbsoluteUrl(getImage('', $path));
    }

    private function resolveVideoUrl(Post $post): string
    {
        $raw = (string) ($post->video_key ?? '');
        // $raw = (string) ($post->getRawOriginal('video') ?: $post->video ?: '');
        if ($raw === '') {
            return '';
        }
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        return $this->ensureAbsoluteUrl((string) fetchFromS3($raw));
    }

    private function ensureAbsoluteUrl(string $urlOrPath): string
    {
        $value = trim($urlOrPath);
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $baseUrl = rtrim((string) config('app.url', ''), '/');
        if ($baseUrl === '') {
            return $value;
        }

        return $baseUrl . '/' . ltrim($value, '/');
    }

    private function jsonHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => self::RESTLI_VERSION,
        ];
    }

    private function markPostFailed(Post $post, array $responsePayload): void
    {
        $post->update([
            'status' => -1,
            'published_at' => now(),
            'response' => json_encode($responsePayload),
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function flattenPublishErrorMessage(array $result): string
    {
        $message = (string) ($result['message'] ?? '');
        if ($message !== '') {
            return $message;
        }
        $details = $result['details'] ?? null;
        if (is_array($details)) {
            $nested = $details['response'] ?? $details['message'] ?? null;
            if (is_string($nested) && $nested !== '') {
                return $nested;
            }

            return json_encode($details);
        }

        return 'LinkedIn API returned an error.';
    }

    /**
     * Human-readable post type label (aligned with FacebookService wording).
     */
    private function linkedinTypeLabel(Post $post): string
    {
        $type = strtolower((string) ($post->type ?? 'photo'));

        return match ($type) {
            'link' => 'link',
            'video' => 'video',
            'document' => 'document',
            'content_only' => 'post',
            'carousel' => 'post',
            default => 'photo',
        };
    }

    /**
     * Same shape as FacebookService::successNotification().
     */
    private function successNotification(int $userId, string $title, string $message, ?Post $post = null): void
    {
        $body = ['type' => 'success', 'message' => $message];

        if ($post) {
            $post->loadMissing('linkedin');
            $li = $post->linkedin;
            $body['social_type'] = 'linkedin';
            $body['account_image'] = $li?->profile_image ?? null;
            $body['account_name'] = ($li && $li->username !== '') ? '@'.$li->username : '';
            $body['account_username'] = $li?->username ?? '';
        }

        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    /**
     * Same shape as FacebookService::errorNotification().
     */
    private function errorNotification(int $userId, string $title, string $message, ?Post $post = null): void
    {
        $body = ['type' => 'error', 'message' => $message];

        if ($post) {
            $post->loadMissing('linkedin');
            $li = $post->linkedin;
            $body['social_type'] = 'linkedin';
            $body['account_image'] = $li?->profile_image ?? null;
            $body['account_name'] = ($li && $li->username !== '') ? '@'.$li->username : '';
            $body['account_username'] = $li?->username ?? '';
        }

        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }
}
