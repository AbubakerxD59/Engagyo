<?php

namespace App\Console\Commands;

use App\Services\ThreadPostsSyncService;
use Illuminate\Console\Command;

class SyncThreadPosts extends Command
{
    protected $signature = 'insights:sync-threads-posts';

    protected $description = 'Fetch Threads posts and post insights for all connected accounts and durations';

    public function handle(ThreadPostsSyncService $syncService): int
    {
        $this->info('Starting Threads posts sync for all connected accounts...');
        $result = $syncService->syncAll();

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
