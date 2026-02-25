<?php

namespace App\Console\Commands;

use App\Services\PageInsightsSyncService;
use Illuminate\Console\Command;

class SyncPageInsights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insights:sync-page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update page insights for each duration (last_7, last_28, last_90, this_month, this_year) with since/until based on current date';

    /**
     * Execute the console command.
     */
    public function handle(PageInsightsSyncService $syncService): int
    {
        $this->info('Starting page insights sync for all pages...');

        $result = $syncService->syncAll();

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
