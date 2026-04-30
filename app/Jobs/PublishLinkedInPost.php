<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\LinkedInPublishService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishLinkedInPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public int $postId,
    ) {}

    public function handle(LinkedInPublishService $linkedInPublishService): void
    {
        $post = Post::with('linkedin')->find($this->postId);
        if (! $post || $post->social_type !== 'linkedin') {
            return;
        }

        $account = $post->linkedin;
        if (! $account) {
            $post->update([
                'status' => -1,
                'published_at' => now(),
                'response' => json_encode(['success' => false, 'message' => 'LinkedIn account not found.']),
            ]);

            return;
        }

        if (! $account->validToken()) {
            $post->update([
                'status' => -1,
                'published_at' => now(),
                'response' => json_encode(['success' => false, 'message' => 'LinkedIn access token expired. Reconnect your account.']),
            ]);

            return;
        }

        $result = $linkedInPublishService->publish($post, $account);
        if (! ($result['success'] ?? false)) {
            $post->update([
                'status' => -1,
                'published_at' => now(),
                'response' => json_encode($result),
            ]);

            return;
        }

        $post->update([
            'status' => 1,
            'post_id' => $result['id'] ?? null,
            'published_at' => now(),
            'response' => json_encode($result),
        ]);
    }
}

