<?php

namespace App\Jobs;

use App\Models\Thread;
use App\Services\PostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteThreadsPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private string $threadsMediaId,
        private int $threadAccountId
    ) {}

    public function handle(): void
    {
        if ($this->threadsMediaId === '') {
            return;
        }

        $thread = Thread::find($this->threadAccountId);
        if (! $thread) {
            return;
        }

        try {
            PostService::deleteThreadsMediaFromApi($thread, $this->threadsMediaId);
        } catch (\Throwable $e) {
            Log::warning('DeleteThreadsPostJob: API delete failed', [
                'threads_media_id' => $this->threadsMediaId,
                'thread_id' => $this->threadAccountId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
