<?php

namespace App\Jobs;

use App\Models\InstagramAccount;
use App\Services\InstagramGraphService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteInstagramPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private string $mediaId,
        private int $instagramAccountId
    ) {}

    public function handle(InstagramGraphService $instagramGraph): void
    {
        if ($this->mediaId === '') {
            return;
        }

        $account = InstagramAccount::find($this->instagramAccountId);
        if (! $account) {
            return;
        }

        if (! $instagramGraph->deleteMedia($account, $this->mediaId)) {
            Log::warning('DeleteInstagramPostJob: API delete failed or unsupported', [
                'media_id' => $this->mediaId,
                'instagram_account_id' => $this->instagramAccountId,
            ]);
        }
    }
}
