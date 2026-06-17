<?php

namespace App\Jobs;

use App\Models\Page;
use App\Services\FacebookFeedSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncFacebookPagePostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public int $pageId) {}

    public function handle(FacebookFeedSyncService $syncService): void
    {
        $page = Page::query()->find($this->pageId);

        if (! $page || empty($page->page_id) || empty($page->access_token)) {
            return;
        }

        try {
            $syncService->syncLatestPostsForPage($page);
        } catch (\Throwable $e) {
            Log::warning('Facebook page posts sync failed after connect', [
                'page_id' => $page->id,
                'external_page_id' => $page->page_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
