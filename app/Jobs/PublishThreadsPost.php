<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\ThreadsGraphService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishThreadsPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public int $postId,
    ) {}

    public function handle(ThreadsGraphService $threadsGraph): void
    {
        $post = Post::with('thread')->find($this->postId);
        if (! $post || ! str_contains(strtolower((string) $post->social_type), 'threads')) {
            Log::error("PublishThreadsPost: missing post or not Threads (id {$this->postId})");

            return;
        }

        $thread = $post->thread;
        if (! $thread) {
            Log::error("PublishThreadsPost: no Threads account on post {$this->postId}");
            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode(['success' => false, 'error' => 'Threads account not found.']),
            ]);

            return;
        }

        if (! $thread->validToken()) {
            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode(['success' => false, 'error' => 'Threads access token expired. Reconnect your Threads account.']),
            ]);

            return;
        }

        $token = (string) ($thread->getRawOriginal('access_token') ?? $thread->access_token ?? '');
        if ($token === '') {
            $post->update([
                'status' => -1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode(['success' => false, 'error' => 'Threads access token is missing.']),
            ]);

            return;
        }

        $threadsGraph->publishPost($post, $token);
    }
}
