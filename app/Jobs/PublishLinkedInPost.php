<?php

namespace App\Jobs;

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
        $service = new LinkedInPublishService();
        try {
            $service->publishQueuedPost($this->postId);
        } catch (\Throwable $e) {
            Log::error('PublishLinkedInPost: handle exception', [
                'postId' => $this->postId,
                'error' => $e->getMessage(),
            ]);
            $service->publishQueuedPostFailed($this->postId, $e->getMessage());
        }
    }

    /**
     * Covers timeouts and other worker-level failures when {@see handle()} does not catch.
     * Notifications are created only in {@see LinkedInPublishService::publishQueuedPostFailed()}.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PublishLinkedInPost job failed', [
            'postId' => $this->postId,
            'error' => $exception->getMessage(),
        ]);

        (new LinkedInPublishService())->publishQueuedPostFailed($this->postId, $exception->getMessage());
    }
}
