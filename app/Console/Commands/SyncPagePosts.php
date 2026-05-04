<?php

namespace App\Console\Commands;

use App\Services\PagePostsSyncService;
use Illuminate\Console\Command;

class SyncPagePosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insights:sync-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch posts and post insights for all pages (from page_insight) for all durations and update page_posts';

    /**
     * Execute the console command.
     */
    public function handle(PagePostsSyncService $syncService): int
    {
        $this->info('Starting page posts sync for all pages...');

        $result = $syncService->syncAll(function (array $event): void {
            $type = $event['type'] ?? '';
            if ($type === 'start') {
                $this->line(sprintf(
                    'Total pages: %d | Durations per page: %d | Total steps: %d',
                    (int) ($event['total_pages'] ?? 0),
                    (int) ($event['total_durations'] ?? 0),
                    (int) ($event['total_steps'] ?? 0)
                ));
                return;
            }

            if ($type === 'page_start') {
                $pageName = (string) ($event['page_name'] ?? 'N/A');
                $pageId = (int) ($event['page_id'] ?? 0);
                $this->line(sprintf('--- Page: %s (ID: %d) ---', $pageName, $pageId));
                return;
            }

            if ($type === 'duration_start') {
                $this->line(sprintf(
                    '[%d/%d] Syncing %s (%s -> %s)',
                    (int) ($event['step'] ?? 0),
                    (int) ($event['total_steps'] ?? 0),
                    (string) ($event['duration'] ?? 'unknown'),
                    (string) ($event['since'] ?? ''),
                    (string) ($event['until'] ?? '')
                ));
                return;
            }

            if ($type === 'duration_success') {
                $this->info(sprintf(
                    '[%d/%d] OK: %s',
                    (int) ($event['step'] ?? 0),
                    (int) ($event['total_steps'] ?? 0),
                    (string) ($event['duration'] ?? 'unknown')
                ));
                return;
            }

            if ($type === 'duration_failed') {
                $this->warn(sprintf(
                    '[%d/%d] FAILED: %s | %s',
                    (int) ($event['step'] ?? 0),
                    (int) ($event['total_steps'] ?? 0),
                    (string) ($event['duration'] ?? 'unknown'),
                    (string) ($event['error'] ?? 'Unknown error')
                ));
            }
        });

        $this->info("Synced: {$result['synced']}");
        if ($result['failed'] > 0) {
            $this->warn("Failed: {$result['failed']}");
        }

        return Command::SUCCESS;
    }
}
