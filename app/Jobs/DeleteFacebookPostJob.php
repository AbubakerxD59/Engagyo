<?php

namespace App\Jobs;

use App\Models\Page;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteFacebookPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private string $facebookPostId,
        private int $pageId,
        private ?int $dbPostId = null
    ) {}

    public function handle(): void
    {
        $page = Page::with('facebook')->find($this->pageId);
        if (!$page || empty($page->access_token)) {
            return;
        }

        $facebookService = new FacebookService();
        $facebookService->deleteFromFacebook($this->facebookPostId, $page, $this->dbPostId);
    }
}
