<?php

namespace App\Jobs;

use App\Models\Youtube;
use App\Services\YouTubePostsSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncYouTubeChannelVideosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public int $youtubeId) {}

    public function handle(YouTubePostsSyncService $syncService): void
    {
        $account = Youtube::query()->find($this->youtubeId);

        if (! $account || empty($account->channel_id) || empty($account->access_token)) {
            return;
        }

        try {
            $syncService->syncLatestVideosForChannel($account);
        } catch (\Throwable $e) {
            Log::warning('YouTube channel videos sync failed after connect', [
                'youtube_id' => $account->id,
                'channel_id' => $account->channel_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
