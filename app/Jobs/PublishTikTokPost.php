<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use App\Services\TikTokService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PublishTikTokPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    private $id;
    private $data;
    private $post;
    private $access_token;
    private $type;
    /**
     * Create a new job instance.
     */
    public function __construct($id, $data, $access_token, $type)
    {
        $this->id = $id;
        $this->data = $data;
        $this->access_token = $access_token;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $post = Post::with('tiktok')->find($this->id);
        if (!$post || $post->social_type !== 'tiktok' || !$post->tiktok) {
            Log::error("PublishTikTokPost: missing post or TikTok account for post id {$this->id}");
            return;
        }

        $tokenResponse = TikTokService::validateToken($post->tiktok);
        if (empty($tokenResponse['success'])) {
            $message = $tokenResponse['message'] ?? 'TikTok authentication failed.';
            Log::error("PublishTikTokPost: {$message}");
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

        $accessToken = $tokenResponse['access_token'];
        $tiktokService = new TikTokService();
        if ($this->type == "photo") {
            $tiktokService->photo($this->id, $this->data, $accessToken);
        } elseif ($this->type == "video") {
            $tiktokService->video($this->id, $this->data, $accessToken);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("PublishTikTokPost job failed: " . $exception->getMessage());
    }
}

