<?php

namespace App\Console\Commands;

use App\Services\PinterestBoardInsightsSyncService;
use Illuminate\Console\Command;

class SyncPinterestBoardInsights extends Command
{
    protected $signature = 'insights:sync-pinterest-boards';

    protected $description = 'Sync Pinterest per-board analytics (insights + pins) for standard date ranges';

    public function handle(PinterestBoardInsightsSyncService $syncService): int
    {
        $this->info('Starting Pinterest board insights sync...');
        $result = $syncService->syncAll();
        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
