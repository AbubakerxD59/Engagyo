<?php

namespace App\Console\Commands;

use App\Services\ThreadInsightsSyncService;
use Illuminate\Console\Command;

class SyncThreadInsights extends Command
{
    protected $signature = 'insights:sync-threads';

    protected $description = 'Update Threads account insights for each duration';

    public function handle(ThreadInsightsSyncService $syncService): int
    {
        $this->info('Starting Threads insights sync for all connected accounts...');
        $result = $syncService->syncAll();

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
