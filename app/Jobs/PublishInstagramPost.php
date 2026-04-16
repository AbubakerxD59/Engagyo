<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\InstagramGraphService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishInstagramPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public int $postId,
    ) {}

    public function handle(InstagramGraphService $instagramGraph): void
    {
        $post = Post::with('instagramAccount')->find($this->postId);
        if (! $post || ! str_contains(strtolower((string) $post->social_type), 'instagram')) {
            Log::error("PublishInstagramPost: missing post or not Instagram (id {$this->postId})");

            return;
        }

        $ig = $post->instagramAccount;
        if (! $ig) {
            Log::error("PublishInstagramPost: no Instagram account on post {$this->postId}");
            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode(['success' => false, 'error' => 'Instagram account not found.']),
            ]);

            return;
        }

        if (! $ig->validToken()) {
            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode(['success' => false, 'error' => 'Instagram access token expired. Reconnect your Instagram account.']),
            ]);

            return;
        }

        $token = (string) ($ig->getRawOriginal('access_token') ?? $ig->access_token ?? '');
        if ($token === '') {
            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode(['success' => false, 'error' => 'Instagram access token is missing.']),
            ]);

            return;
        }

        $instagramGraph->publishPost($post, $token);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishInstagramPost job failed: '.$exception->getMessage());
    }
}
