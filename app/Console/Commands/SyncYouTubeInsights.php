<?php

namespace App\Console\Commands;

use App\Services\YouTubeInsightsSyncService;
use Illuminate\Console\Command;

class SyncYouTubeInsights extends Command
{
    protected $signature = 'insights:sync-youtube';

    protected $description = 'Sync YouTube channel analytics for all connected channels';

    public function handle(YouTubeInsightsSyncService $syncService): int
    {
        $this->info('Starting YouTube insights sync...');

        $result = $syncService->syncAll();

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
