<?php

namespace App\Jobs;

use App\Models\Post;
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

    private int $id;

    private array $data;

    private string $accessToken;

    public function __construct(int $id, array $data, string $accessToken)
    {
        $this->id = $id;
        $this->data = $data;
        $this->accessToken = $accessToken;
    }

    public function handle(): void
    {
        $post = Post::with('youtube')->find($this->id);
        if (! $post || $post->social_type !== 'youtube' || ! $post->youtube) {
            Log::error("PublishYouTubePost: missing post or YouTube account for post id {$this->id}");

            return;
        }

        $tokenResponse = YouTubeService::validateToken($post->youtube);
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

        $youtubeService = new YouTubeService();
        $youtubeService->video($this->id, $this->data, $tokenResponse['access_token']);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishYouTubePost job failed: '.$exception->getMessage());
    }
}
