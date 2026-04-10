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

    /**
     * Video/Reels container polling can exceed several minutes; keep workers from timing out at 60s.
     */
    public int $timeout = 1200;

    public function __construct(
        private int $postId,
        private string $accessToken
    ) {}

    public function handle(InstagramGraphService $instagramGraphService): void
    {
        $post = Post::withoutGlobalScopes()
            ->with('instagramAccount')
            ->find($this->postId);
        if (! $post) {
            Log::error('PublishInstagramPost: post not found', ['post_id' => $this->postId]);

            return;
        }

        try {
            $instagramGraphService->publishPost($post, $this->accessToken);
        } catch (\Throwable $e) {
            Log::error('PublishInstagramPost failed: '.$e->getMessage(), ['post_id' => $this->postId]);
            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ]),
            ]);
        }
    }
}
