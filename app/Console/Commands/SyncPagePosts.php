<?php

namespace App\Console\Commands;

use App\Services\PagePostsSyncService;
use Illuminate\Console\Command;

class SyncPagePosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insights:sync-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch posts and post insights for all pages (from page_insight) for all durations and update page_posts';

    /**
     * Execute the console command.
     */
    public function handle(PagePostsSyncService $syncService): int
    {
        $this->info('Starting page posts sync for all pages...');

        $result = $syncService->syncAll();

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
