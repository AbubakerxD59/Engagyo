<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\PagePostsSyncService;
use Illuminate\Console\Command;

class SyncPagePosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insights:sync-posts {--page-id= : Sync only one local Page id} {--full-year-only : With --page-id, sync only full_year duration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch posts and post insights for pages and durations, and update page_posts';

    /**
     * Execute the console command.
     */
    public function handle(PagePostsSyncService $syncService): int
    {
        $pageIdOption = $this->option('page-id');
        if (!empty($pageIdOption)) {
            $pageId = (int) $pageIdOption;
            $page = Page::withoutGlobalScopes()->find($pageId);
            if (!$page) {
                $this->error("Page not found for id: {$pageId}");
                return Command::FAILURE;
            }

            $this->info("Starting page posts sync for single page: {$page->name} (ID: {$page->id})");

            if ((bool) $this->option('full-year-only')) {
                $result = $syncService->syncPageForFullYear($page);
                $this->info("Synced: {$result['synced']}");
                if ($result['failed'] > 0) {
                    $this->warn("Failed: {$result['failed']}");
                }

                return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
            }

            $result = $syncService->syncPageForAllDurations($page);
            $this->info("Synced: {$result['synced']}");
            if ($result['failed'] > 0) {
                $this->warn("Failed: {$result['failed']}");
            }

            return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
        }

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
