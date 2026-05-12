<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThreadsGraphService
{
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

    public function publishPost(Post $post, string $accessToken): void
    {
        $post->loadMissing('thread');
        $thread = $post->thread;

        if (! $thread || empty($thread->threads_id)) {
            $this->failPost($post, 'Threads account not found for this post.');

            return;
        }

        $body = PostService::postTypeBody($post);
        $type = (string) ($body['type'] ?? '');
        $threadsUserId = (string) $thread->threads_id;

        if ($type === 'carousel') {
            $this->publishCarousel($post, $threadsUserId, $accessToken, $body);

            return;
        }

        $this->publishSingle($post, $threadsUserId, $accessToken, $body);
    }

    private function publishSingle(Post $post, string $threadsUserId, string $accessToken, array $body): void
    {
        $payload = [
            'access_token' => $accessToken,
            'text' => trim((string) ($body['text'] ?? '')),
        ];

        $mediaType = strtoupper((string) ($body['media_type'] ?? 'TEXT'));
        if (! in_array($mediaType, ['TEXT', 'IMAGE', 'VIDEO'], true)) {
            $mediaType = 'TEXT';
        }

        if ($mediaType === 'IMAGE') {
            $url = trim((string) ($body['image_url'] ?? ''));
            if ($url === '') {
                $this->failPost($post, 'Image URL is missing for Threads post.');

                return;
            }

            $staged = InstagramPublishMediaStorageService::ensureStoredPublicUrl($url, $post, 'image');
            if ($staged['error'] !== null) {
                $this->failPost($post, $staged['error']);

                return;
            }

            $payload['media_type'] = 'IMAGE';
            $payload['image_url'] = $staged['url'];
        } elseif ($mediaType === 'VIDEO') {
            $url = trim((string) ($body['video_url'] ?? ''));
            if ($url === '') {
                $this->failPost($post, 'Video URL is missing for Threads post.');

                return;
            }

            $staged = InstagramPublishMediaStorageService::ensureStoredPublicUrl($url, $post, 'video');
            if ($staged['error'] !== null) {
                $this->failPost($post, $staged['error']);

                return;
            }

            $payload['media_type'] = 'VIDEO';
            $payload['video_url'] = $staged['url'];
        } else {
            $payload['media_type'] = 'TEXT';
        }

        $creationId = $this->createThreadsContainer($threadsUserId, $payload);
        if ($creationId === null) {
            $this->failPost($post, 'Failed to create Threads media container.');

            return;
        }

        $postId = $this->publishThreadsContainer($threadsUserId, $creationId, $accessToken);
        if ($postId === null) {
            $this->failPost($post, 'Failed to publish Threads post.');

            return;
        }

        $this->markSuccess($post, $postId, $mediaType === 'TEXT'
            ? 'Text post published successfully to Threads'
            : 'Post published successfully to Threads');
        $this->publishFirstReplyIfNeeded($post, $threadsUserId, $accessToken, $postId, $mediaType);
    }

    private function publishCarousel(Post $post, string $threadsUserId, string $accessToken, array $body): void
    {
        $items = $body['carousel_items'] ?? [];
        if (! is_array($items) || count($items) < 2) {
            $this->failPost($post, 'Threads carousel requires at least 2 media items.');

            return;
        }
        if (count($items) > 20) {
            $this->failPost($post, 'Threads carousel supports up to 20 media items.');

            return;
        }

        $children = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $kind = strtolower((string) ($item['type'] ?? 'image'));
            $url = trim((string) ($item['url'] ?? ''));
            if ($url === '') {
                $this->failPost($post, 'Carousel item URL is missing.');

                return;
            }

            if ($kind === 'video') {
                $staged = InstagramPublishMediaStorageService::ensureStoredPublicUrl($url, $post, 'video');
                if ($staged['error'] !== null) {
                    $this->failPost($post, $staged['error']);

                    return;
                }
                $containerId = $this->createThreadsContainer($threadsUserId, [
                    'access_token' => $accessToken,
                    'media_type' => 'VIDEO',
                    'video_url' => $staged['url'],
                    'is_carousel_item' => 'true',
                ]);
            } else {
                $staged = InstagramPublishMediaStorageService::ensureStoredPublicUrl($url, $post, 'image');
                if ($staged['error'] !== null) {
                    $this->failPost($post, $staged['error']);

                    return;
                }
                $containerId = $this->createThreadsContainer($threadsUserId, [
                    'access_token' => $accessToken,
                    'media_type' => 'IMAGE',
                    'image_url' => $staged['url'],
                    'is_carousel_item' => 'true',
                ]);
            }

            if ($containerId === null) {
                $this->failPost($post, 'Failed to create a Threads carousel child container.');

                return;
            }

            $children[] = $containerId;
        }

        if (count($children) < 2) {
            $this->failPost($post, 'Threads carousel requires at least 2 valid media items.');

            return;
        }

        $parentPayload = [
            'access_token' => $accessToken,
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $children),
            'text' => trim((string) ($body['text'] ?? '')),
        ];

        $parentCreationId = $this->createThreadsContainer($threadsUserId, $parentPayload);
        if ($parentCreationId === null) {
            $this->failPost($post, 'Failed to create Threads carousel container.');

            return;
        }

        $postId = $this->publishThreadsContainer($threadsUserId, $parentCreationId, $accessToken);
        if ($postId === null) {
            $this->failPost($post, 'Failed to publish Threads carousel post.');

            return;
        }

        $this->markSuccess($post, $postId, 'Carousel published successfully to Threads');
        $this->publishFirstReplyIfNeeded($post, $threadsUserId, $accessToken, $postId, 'CAROUSEL');
    }

    /**
     * Publish optional "first comment" as a text reply to the root thread (Threads API: reply_to_id).
     * Does not fail the post if the reply step fails; logs a warning and stores details on the post response.
     */
    private function publishFirstReplyIfNeeded(Post $post, string $threadsUserId, string $accessToken, string $rootThreadMediaId, string $rootMediaKind): void
    {
        $comment = trim((string) ($post->comment ?? ''));
        if ($comment === '') {
            return;
        }

        $this->waitBeforeFirstReply($rootThreadMediaId, $accessToken, $rootMediaKind);

        $replyPayload = [
            'access_token' => $accessToken,
            'media_type' => 'TEXT',
            'text' => $comment,
            'reply_to_id' => $rootThreadMediaId,
        ];

        $createResult = $this->createThreadsContainerEx($threadsUserId, $replyPayload);
        if ($createResult['id'] === null) {
            $detail = $this->threadsApiErrorSummary($createResult['error_body']);
            Log::warning('Threads first-reply container create failed', [
                'post_id' => $post->id,
                'http_status' => $createResult['http_status'],
                'response' => $createResult['error_body'],
            ]);
            $this->mergeFirstReplyIntoPostResponse($post, [
                'success' => false,
                'step' => 'create_container',
                'error' => $detail,
            ]);

            return;
        }

        $publishResult = $this->publishThreadsContainerEx($threadsUserId, $createResult['id'], $accessToken);
        if ($publishResult['id'] === null) {
            $detail = $this->threadsApiErrorSummary($publishResult['error_body']);
            Log::warning('Threads first-reply publish failed', [
                'post_id' => $post->id,
                'creation_id' => $createResult['id'],
                'http_status' => $publishResult['http_status'],
                'response' => $publishResult['error_body'],
            ]);
            $this->mergeFirstReplyIntoPostResponse($post, [
                'success' => false,
                'step' => 'publish',
                'creation_id' => $createResult['id'],
                'error' => $detail,
            ]);

            return;
        }

        Post::withoutGlobalScopes()->where('id', $post->id)->update(['comment_id' => $publishResult['id']]);
        $this->mergeFirstReplyIntoPostResponse($post, [
            'success' => true,
            'reply_media_id' => $publishResult['id'],
        ]);
    }

    /**
     * Give the root thread time to become replyable. Text posts use a short fixed delay; media uses
     * GET status polling (see Threads API troubleshooting — status on media id).
     */
    private function waitBeforeFirstReply(string $rootThreadMediaId, string $accessToken, string $rootMediaKind): void
    {
        $kind = strtoupper($rootMediaKind);
        if ($kind === 'TEXT') {
            sleep(6);

            return;
        }

        $maxAttempts = 28;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if ($attempt > 0) {
                sleep(4);
            }

            $data = $this->fetchThreadsMediaPublishingStatus($rootThreadMediaId, $accessToken);
            if (! is_array($data)) {
                if ($attempt >= 4) {
                    Log::warning('Threads first-reply wait: status requests failing, continuing', [
                        'media_id' => $rootThreadMediaId,
                        'kind' => $kind,
                    ]);
                    sleep(12);

                    return;
                }

                continue;
            }

            if (! array_key_exists('status', $data) || $data['status'] === null || $data['status'] === '') {
                sleep(15);

                return;
            }

            $status = strtoupper((string) $data['status']);
            if (in_array($status, ['PUBLISHED', 'FINISHED'], true)) {
                sleep(2);

                return;
            }
            if ($status === 'ERROR') {
                Log::warning('Threads root media reported ERROR before first reply', [
                    'media_id' => $rootThreadMediaId,
                    'error_message' => $data['error_message'] ?? null,
                ]);
                sleep(10);

                return;
            }
        }

        Log::warning('Threads root media did not report PUBLISHED/FINISHED before first reply; attempting reply anyway', [
            'media_id' => $rootThreadMediaId,
            'kind' => $kind,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchThreadsMediaPublishingStatus(string $mediaId, string $accessToken): ?array
    {
        $resp = Http::acceptJson()
            ->timeout(45)
            ->get('https://graph.threads.net/v1.0/'.rawurlencode($mediaId), [
                'fields' => 'status,error_message',
                'access_token' => $accessToken,
            ]);

        if (! $resp->successful()) {
            return null;
        }

        $json = $resp->json();

        return is_array($json) ? $json : null;
    }

    /**
     * @param  array{success: bool, step?: string, creation_id?: string, error?: string, reply_media_id?: string}  $payload
     */
    private function mergeFirstReplyIntoPostResponse(Post $post, array $payload): void
    {
        $post->refresh();
        $raw = $post->getAttributes()['response'] ?? $post->response ?? '';
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($decoded)) {
            $decoded = [];
        }
        $decoded['first_reply'] = $payload;
        Post::withoutGlobalScopes()->where('id', $post->id)->update([
            'response' => json_encode($decoded),
        ]);
    }

    private function threadsApiErrorSummary(?string $body): string
    {
        if ($body === null || $body === '') {
            return 'Empty API response.';
        }
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $msg = $decoded['error']['message'] ?? $decoded['error_message'] ?? $decoded['message'] ?? null;
            if (is_string($msg) && $msg !== '') {
                return $msg;
            }
        }

        return strlen($body) > 500 ? substr($body, 0, 500).'…' : $body;
    }

    /**
     * @return array{id: ?string, error_body: ?string, http_status: ?int}
     */
    private function createThreadsContainerEx(string $threadsUserId, array $payload): array
    {
        $resp = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post("https://graph.threads.net/v1.0/{$threadsUserId}/threads", $payload);

        if (! $resp->successful()) {
            Log::warning('Threads container create failed', ['response' => $resp->body(), 'http_status' => $resp->status()]);

            return ['id' => null, 'error_body' => $resp->body(), 'http_status' => $resp->status()];
        }

        $id = $resp->json('id');
        if (! is_string($id) || $id === '') {
            Log::warning('Threads container create missing id', ['response' => $resp->body()]);

            return ['id' => null, 'error_body' => $resp->body(), 'http_status' => $resp->status()];
        }

        return ['id' => $id, 'error_body' => null, 'http_status' => $resp->status()];
    }

    /**
     * @return array{id: ?string, error_body: ?string, http_status: ?int}
     */
    private function publishThreadsContainerEx(string $threadsUserId, string $creationId, string $accessToken): array
    {
        $resp = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post("https://graph.threads.net/v1.0/{$threadsUserId}/threads_publish", [
                'creation_id' => $creationId,
                'access_token' => $accessToken,
            ]);

        if (! $resp->successful()) {
            Log::warning('Threads publish failed', ['response' => $resp->body(), 'creation_id' => $creationId, 'http_status' => $resp->status()]);

            return ['id' => null, 'error_body' => $resp->body(), 'http_status' => $resp->status()];
        }

        $id = $resp->json('id');
        if (! is_string($id) || $id === '') {
            Log::warning('Threads publish missing id', ['response' => $resp->body(), 'creation_id' => $creationId]);

            return ['id' => null, 'error_body' => $resp->body(), 'http_status' => $resp->status()];
        }

        return ['id' => $id, 'error_body' => null, 'http_status' => $resp->status()];
    }

    private function createThreadsContainer(string $threadsUserId, array $payload): ?string
    {
        $r = $this->createThreadsContainerEx($threadsUserId, $payload);

        return $r['id'];
    }

    private function publishThreadsContainer(string $threadsUserId, string $creationId, string $accessToken): ?string
    {
        $r = $this->publishThreadsContainerEx($threadsUserId, $creationId, $accessToken);

        return $r['id'];
    }

    private function markSuccess(Post $post, string $postId, string $message): void
    {
        $post->update([
            'post_id' => $postId,
            'status' => 1,
            'published_at' => date('Y-m-d H:i:s'),
            'response' => json_encode([
                'success' => true,
                'post_id' => $postId,
                'message' => $message,
            ]),
        ]);

        $this->successNotification($post, $message);
    }

    private function successNotification(Post $post, string $message): void
    {
        $post->loadMissing('thread');
        $thread = $post->thread;

        Notification::create([
            'user_id' => $post->user_id,
            'title' => 'Post Published',
            'body' => [
                'type' => 'success',
                'message' => $message,
                'social_type' => 'threads',
                'account_image' => $thread?->profile_image ?? null,
                'account_name' => $thread?->username ?? '',
                'account_username' => $thread?->username ?? '',
            ],
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    private function errorNotification(Post $post, string $message): void
    {
        $post->loadMissing('thread');
        $thread = $post->thread;

        Notification::create([
            'user_id' => $post->user_id,
            'title' => 'Post Publishing Failed',
            'body' => [
                'type' => 'error',
                'message' => 'Failed to publish Threads post. '.$message,
                'social_type' => 'threads',
                'account_image' => $thread?->profile_image ?? null,
                'account_name' => $thread?->username ?? '',
                'account_username' => $thread?->username ?? '',
            ],
            'is_read' => false,
            'is_system' => false,
        ]);
    }
}
