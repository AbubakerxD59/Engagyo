<?php

namespace App\Console\Commands;

use App\Models\FacebookPost;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncFacebookPostCreatedDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facebook:sync-post-created-date {--chunk=500 : Chunk size for batch processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync facebook_posts.post_created_date from post_data.created_time';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = max(100, (int) $this->option('chunk'));
        $updated = 0;
        $skipped = 0;

        $this->info('Starting sync of facebook_posts.post_created_date ...');

        FacebookPost::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (&$updated, &$skipped) {
                foreach ($rows as $row) {
                    $postData = is_array($row->post_data) ? $row->post_data : [];
                    $createdTime = $postData['created_time'] ?? null;

                    if (is_array($createdTime)) {
                        $createdTime = $createdTime['date'] ?? null;
                    } elseif (is_object($createdTime) && method_exists($createdTime, 'format')) {
                        $createdTime = $createdTime->format('Y-m-d H:i:s');
                    }

                    if (! is_string($createdTime) || trim($createdTime) === '') {
                        $skipped++;
                        continue;
                    }

                    try {
                        $normalized = Carbon::parse($createdTime)->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        $skipped++;
                        continue;
                    }

                    $row->post_created_date = $normalized;
                    $row->save();
                    $updated++;
                }
            });

        $this->info("Done. Updated: {$updated}, Skipped: {$skipped}");

        return Command::SUCCESS;
    }
}
