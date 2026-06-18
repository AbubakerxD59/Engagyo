<?php

namespace App\Console\Commands;

use App\Services\YouTubePostsSyncService;
use Illuminate\Console\Command;

class SyncYouTubePosts extends Command
{
    protected $signature = 'insights:sync-youtube-posts';

    protected $description = 'Sync YouTube channel videos and per-video analytics for all connected channels';

    public function handle(YouTubePostsSyncService $syncService): int
    {
        $this->info('Starting YouTube posts sync...');

        $result = $syncService->syncAll();

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
