<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\FailedPostEmailService;

class PostObserver
{
    public function __construct(
        private FailedPostEmailService $failedPostEmailService
    ) {}

    public function updated(Post $post): void
    {
        if (! $post->wasChanged('status')) {
            return;
        }

        if ((int) $post->status !== -1) {
            return;
        }

        $this->failedPostEmailService->send($post);
    }
}
