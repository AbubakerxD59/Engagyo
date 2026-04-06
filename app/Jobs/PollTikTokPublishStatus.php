<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Notification;
use App\Services\TikTokService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PollTikTokPublishStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    private $postId;
    private $attempt;
    private $maxAttempts;

    public function __construct(int $postId, int $attempt = 0, int $maxAttempts = 60)
    {
        $this->postId = $postId;
        $this->attempt = $attempt;
        $this->maxAttempts = $maxAttempts;
    }

    public function handle(): void
    {
        $post = Post::with('tiktok')->find($this->postId);
        if (!$post || $post->social_type !== 'tiktok' || !$post->tiktok || empty($post->post_id)) {
            return;
        }

        if ((int) $post->status === -1) {
            return;
        }

        $tokenResponse = TikTokService::validateToken($post->tiktok);
        if (empty($tokenResponse['success']) || empty($tokenResponse['access_token'])) {
            Log::warning('PollTikTokPublishStatus: token validation failed', ['post_id' => $post->id]);
            $this->requeue();
            return;
        }

        $service = new TikTokService();
        $statusResponse = $service->getPostStatus((string) $post->post_id, $tokenResponse['access_token']);
        if (empty($statusResponse['success'])) {
            Log::warning('PollTikTokPublishStatus: status fetch failed', ['post_id' => $post->id]);
            $this->requeue();
            return;
        }

        $statusData = $statusResponse['data'] ?? [];
        $statusText = strtoupper((string) ($statusData['status'] ?? $statusData['publish_status'] ?? $statusData['state'] ?? ''));

        $response = is_array($post->response) ? $post->response : (json_decode((string) $post->response, true) ?: []);
        $response['publish_status_data'] = $statusData;
        $response['publish_status_checked_at'] = now()->toDateTimeString();
        if ($statusText !== '') {
            $response['processing_status'] = $statusText;
        }

        $isFailed = str_contains($statusText, 'FAIL') || str_contains($statusText, 'ERROR') || str_contains($statusText, 'REJECT');
        $isPublished = str_contains($statusText, 'PUBLISH') || str_contains($statusText, 'SUCCESS') || str_contains($statusText, 'COMPLETE');

        if ($isFailed) {
            $message = $statusData['fail_reason'] ?? $statusData['message'] ?? 'TikTok reported the post failed.';
            $response['success'] = false;
            $response['error'] = $message;
            $response['message'] = $message;

            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode($response),
            ]);

            Notification::create([
                'user_id' => $post->user_id,
                'title' => 'TikTok Post Failed',
                'body' => [
                    'type' => 'error',
                    'message' => 'Your TikTok post failed to publish. ' . $message,
                    'social_type' => 'tiktok',
                    'account_image' => $post->tiktok?->profile_image,
                    'account_name' => $post->tiktok?->display_name ?? $post->tiktok?->username ?? '',
                    'account_username' => $post->tiktok?->username ?? $post->tiktok?->display_name ?? '',
                ],
                'is_read' => false,
                'is_system' => false,
            ]);
            return;
        }

        if ($isPublished) {
            $response['success'] = true;
            $response['message'] = 'TikTok reports your post is published.';

            $post->update([
                'status' => 1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode($response),
            ]);

            Notification::create([
                'user_id' => $post->user_id,
                'title' => 'TikTok Post Published',
                'body' => [
                    'type' => 'success',
                    'message' => 'Your TikTok post is now published.',
                    'social_type' => 'tiktok',
                    'account_image' => $post->tiktok?->profile_image,
                    'account_name' => $post->tiktok?->display_name ?? $post->tiktok?->username ?? '',
                    'account_username' => $post->tiktok?->username ?? $post->tiktok?->display_name ?? '',
                ],
                'is_read' => false,
                'is_system' => false,
            ]);
            return;
        }

        // Still processing
        $response['success'] = true;
        $response['message'] = 'Post submitted to TikTok and still processing. It may take a few minutes to appear on your profile.';
        $post->update([
            'status' => 2,
            'response' => json_encode($response),
        ]);

        $this->requeue();
    }

    private function requeue(): void
    {
        if ($this->attempt >= $this->maxAttempts) {
            return;
        }
        self::dispatch($this->postId, $this->attempt + 1, $this->maxAttempts)->delay(now()->addMinute());
    }
}

