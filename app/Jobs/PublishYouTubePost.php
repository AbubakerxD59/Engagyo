<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Youtube;
use App\Services\PostService;
use App\Services\YouTubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishYouTubePost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public int $postId,
    ) {}

    public function handle(): void
    {
        $post = Post::withoutGlobalScopes()
            ->with(['youtube' => fn ($query) => $query->withoutGlobalScopes()])
            ->find($this->postId);

        if (! $post || ! str_contains(strtolower((string) $post->social_type), 'youtube')) {
            Log::error("PublishYouTubePost: missing post or YouTube social type for post id {$this->postId}");

            return;
        }

        $youtube = $post->youtube;
        if (! $youtube) {
            $youtube = Youtube::withoutGlobalScopes()->find($post->account_id);
        }

        if (! $youtube) {
            Log::error("PublishYouTubePost: YouTube account not found for post id {$this->postId}");
            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode([
                    'success' => false,
                    'error' => 'YouTube account not found.',
                ]),
            ]);

            return;
        }

        $tokenResponse = YouTubeService::validateToken($youtube);
        if (empty($tokenResponse['success'])) {
            $message = $tokenResponse['message'] ?? 'YouTube authentication failed.';
            Log::error("PublishYouTubePost: {$message}");
            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode([
                    'success' => false,
                    'error' => $message,
                ]),
            ]);

            return;
        }

        $postData = PostService::postTypeBody($post);
        (new YouTubeService())->video($this->postId, $postData, $tokenResponse['access_token']);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishYouTubePost job failed', [
            'postId' => $this->postId,
            'error' => $exception->getMessage(),
        ]);

        $post = Post::withoutGlobalScopes()->find($this->postId);
        if (! $post || (int) $post->status === 1) {
            return;
        }

        $post->update([
            'status' => -1,
            'published_at' => date('Y-m-d H:i:s'),
            'response' => json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
            ]),
        ]);
    }
}
