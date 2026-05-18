<?php

namespace App\Console\Commands;

use App\Services\InstagramInsightsSyncService;
use Illuminate\Console\Command;

class SyncInstagramInsights extends Command
{
    protected $signature = 'insights:sync-instagram';

    protected $description = 'Sync Instagram account insights for all connected accounts and standard durations';

    public function handle(InstagramInsightsSyncService $syncService): int
    {
        $this->info('Starting Instagram account insights sync...');

        $result = $syncService->syncAll();

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
