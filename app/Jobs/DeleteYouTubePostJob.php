<?php

namespace App\Jobs;

use App\Models\Youtube;
use App\Services\YouTubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteYouTubePostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private string $videoId,
        private int $youtubeAccountId
    ) {}

    public function handle(): void
    {
        if ($this->videoId === '') {
            return;
        }

        $youtube = Youtube::find($this->youtubeAccountId);
        if (! $youtube) {
            return;
        }

        try {
            (new YouTubeService())->deletePublishedVideo($youtube, $this->videoId);
        } catch (\Throwable $e) {
            Log::warning('DeleteYouTubePostJob: API delete failed', [
                'video_id' => $this->videoId,
                'youtube_account_id' => $this->youtubeAccountId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
