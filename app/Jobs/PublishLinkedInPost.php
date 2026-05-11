<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Post;
use App\Services\LinkedInPublishService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishLinkedInPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public int $postId,
    ) {}

    public function handle(): void
    {
        $post = Post::with('linkedin')->find($this->postId);
        try {
            if (! $post || $post->social_type !== 'linkedin') {
                Log::error("PublishLinkedInPost: missing post or not LinkedIn (id {$this->postId})");
                return;
            }

            $account = $post->linkedin;
            if (! $account) {
                $post->update([
                    'status' => -1,
                    'published_at' => now(),
                    'response' => json_encode(['success' => false, 'message' => 'LinkedIn account not found.']),
                ]);
                $this->errorNotification($post, 'LinkedIn account not found for this post.');

                return;
            }

            if (! $account->validToken()) {
                $post->update([
                    'status' => -1,
                    'published_at' => now(),
                    'response' => json_encode(['success' => false, 'message' => 'LinkedIn access token expired. Reconnect your account.']),
                ]);
                $this->errorNotification($post, 'LinkedIn access token expired. Reconnect your account.');

                return;
            }

            $linkedInPublishService = new LinkedInPublishService();
            $result = $linkedInPublishService->publish($post, $account);
            if (! ($result['success'] ?? false)) {
                $post->update([
                    'status' => -1,
                    'published_at' => now(),
                    'response' => json_encode($result),
                ]);
                $message = (string) ($result['message'] ?? 'LinkedIn API returned an error.');
                $this->errorNotification($post, $message);

                return;
            }

            $post->update([
                'status' => 1,
                'post_id' => $result['id'] ?? null,
                'published_at' => now(),
                'response' => json_encode($result),
            ]);
            $this->successNotification($post);
        } catch (\Throwable $e) {
            if ($post) {
                $post->update([
                    'status' => -1,
                    'published_at' => now(),
                    'response' => json_encode(['success' => false, 'message' => $e->getMessage()]),
                ]);
                $this->errorNotification($post, $e->getMessage());
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishLinkedInPost job failed', [
            'postId' => $this->postId,
            'error' => $exception->getMessage(),
        ]);

        $post = Post::with('linkedin')->find($this->postId);
        if ($post && $post->social_type === 'linkedin') {
            $this->errorNotification($post, 'Publishing job failed: '.$exception->getMessage());
        }
    }

    private function successNotification(Post $post): void
    {
        $post->loadMissing('linkedin');
        $li = $post->linkedin;
        $message = match ((string) $post->type) {
            'video' => 'Your LinkedIn video has been published successfully.',
            'link' => 'Your LinkedIn link post has been published successfully.',
            'carousel' => 'Your LinkedIn carousel has been published successfully.',
            'document' => 'Your LinkedIn document has been published successfully.',
            'content_only' => 'Your LinkedIn post has been published successfully.',
            default => 'Your LinkedIn photo post has been published successfully.',
        };

        Notification::create([
            'user_id' => $post->user_id,
            'title' => 'Post Published',
            'body' => [
                'type' => 'success',
                'message' => $message,
                'social_type' => 'linkedin',
                'account_image' => $li?->profile_image ?? null,
                'account_name' => ($li && $li->username !== '') ? '@'.$li->username : 'LinkedIn',
                'account_username' => $li?->username ?? '',
            ],
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    private function errorNotification(Post $post, string $message): void
    {
        $post->loadMissing('linkedin');
        $li = $post->linkedin;
        $kind = match ((string) $post->type) {
            'video' => 'video',
            'link' => 'link post',
            'carousel' => 'carousel',
            'document' => 'document',
            'content_only' => 'post',
            default => 'photo post',
        };

        Notification::create([
            'user_id' => $post->user_id,
            'title' => 'Post Publishing Failed',
            'body' => [
                'type' => 'error',
                'message' => 'Failed to publish LinkedIn '.$kind.'. '.$message,
                'social_type' => 'linkedin',
                'account_image' => $li?->profile_image ?? null,
                'account_name' => ($li && $li->username !== '') ? '@'.$li->username : 'LinkedIn',
                'account_username' => $li?->username ?? '',
            ],
            'is_read' => false,
            'is_system' => false,
        ]);
    }
}
