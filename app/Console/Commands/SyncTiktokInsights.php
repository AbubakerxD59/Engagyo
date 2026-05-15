<?php

namespace App\Console\Commands;

use App\Services\TiktokInsightsSyncService;
use Illuminate\Console\Command;

class SyncTiktokInsights extends Command
{
    protected $signature = 'insights:sync-tiktok';

    protected $description = 'Sync TikTok account insights for all connected accounts and standard durations';

    public function handle(TiktokInsightsSyncService $syncService): int
    {
        $this->info('Starting TikTok account insights sync...');

        $result = $syncService->syncAll();

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
