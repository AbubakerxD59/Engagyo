<?php

namespace App\Console\Commands;

use App\Services\TiktokPostsSyncService;
use Illuminate\Console\Command;

class SyncTiktokPosts extends Command
{
    protected $signature = 'insights:sync-tiktok-posts';

    protected $description = 'Sync TikTok public videos and per-video metrics for all connected accounts';

    public function handle(TiktokPostsSyncService $syncService): int
    {
        $this->info('Starting TikTok posts sync...');

        $result = $syncService->syncAll();

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
