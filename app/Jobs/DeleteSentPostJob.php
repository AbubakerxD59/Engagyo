<?php

namespace App\Jobs;

use App\Models\Page;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * @deprecated Prefer DeleteFacebookPostJob after local DB/snapshot cleanup.
 */
class DeleteSentPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private string $facebookPostId,
        private int $pageId
    ) {}

    public function handle(FacebookService $facebookService): void
    {
        $page = Page::with('facebook')->find($this->pageId);
        if (! $page || empty($page->access_token)) {
            return;
        }

        $facebookService->deleteFromFacebook($this->facebookPostId, $page, null);
    }
}
