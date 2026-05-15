<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Tiktok;
use App\Services\TikTokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteTikTokPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private string $publishId,
        private int $tiktokAccountId,
        private string $postType = 'video'
    ) {}

    public function handle(): void
    {
        if ($this->publishId === '') {
            return;
        }

        $tiktok = Tiktok::find($this->tiktokAccountId);
        if (! $tiktok) {
            return;
        }

        $stub = new Post([
            'post_id' => $this->publishId,
            'type' => $this->postType,
            'account_id' => $tiktok->id,
        ]);
        $stub->setRelation('tiktok', $tiktok);

        $service = new TikTokService;
        if (! $service->delete($stub)) {
            Log::warning('DeleteTikTokPostJob: API delete failed or unsupported', [
                'publish_id' => $this->publishId,
                'tiktok_id' => $this->tiktokAccountId,
            ]);
        }
    }
}
