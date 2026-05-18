<?php

namespace App\Console\Commands;

use App\Services\InstagramMediaSyncService;
use Illuminate\Console\Command;

class SyncInstagramPosts extends Command
{
    protected $signature = 'insights:sync-instagram-posts';

    protected $description = 'Fetch Instagram media and post-level insights for all connected accounts';

    public function handle(InstagramMediaSyncService $syncService): void
    {
        $this->info('Starting Instagram media sync...');

        $result = $syncService->syncAll(function (array $event): void {
            $type = $event['type'] ?? '';

            if ($type === 'start') {
                $this->line(sprintf(
                    'Total accounts: %d | Total steps: %d',
                    (int) ($event['total_accounts'] ?? 0),
                    (int) ($event['total_steps'] ?? 0)
                ));

                return;
            }

            if ($type === 'account_start') {
                $this->line(sprintf(
                    '--- Instagram: @%s (ID: %d) ---',
                    (string) ($event['username'] ?? 'unknown'),
                    (int) ($event['instagram_account_id'] ?? 0)
                ));

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
    }
}
