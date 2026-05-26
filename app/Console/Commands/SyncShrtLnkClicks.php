<?php

namespace App\Console\Commands;

use App\Services\ShrtLnkClickSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncShrtLnkClicks extends Command
{
    protected $signature = 'shrtlnk:sync-clicks';

    protected $description = 'Sync short link click counts from ShrtLnk into Engagyo';

    public function handle(ShrtLnkClickSyncService $syncService): int
    {
        if (! config('shrtlnk.sync_clicks_enabled', true)) {
            $this->warn('ShrtLnk click sync is disabled (SHRTLNK_SYNC_CLICKS_ENABLED=false).');

            return Command::SUCCESS;
        }

        $this->info('Syncing click counts from ShrtLnk...');

        $summary = $syncService->syncAll();

        $this->info("Updated: {$summary['synced']}");
        $this->info("Unchanged: {$summary['unchanged']}");
        $this->info("Failed: {$summary['failed']}");
        $this->info("Not found on ShrtLnk: {$summary['not_found']}");
        $this->info("Skipped: {$summary['skipped']}");

        Log::info('ShrtLnk click sync completed', $summary);

        return Command::SUCCESS;
    }
}
