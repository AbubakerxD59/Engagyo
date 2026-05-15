<?php

namespace App\Jobs;

use App\Models\Linkedin;
use App\Services\LinkedInPublishService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteLinkedInPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private string $ugcPostUrn,
        private int $linkedinAccountId
    ) {}

    public function handle(LinkedInPublishService $linkedInPublish): void
    {
        if ($this->ugcPostUrn === '') {
            return;
        }

        $linkedin = Linkedin::find($this->linkedinAccountId);
        if (! $linkedin) {
            return;
        }

        $result = $linkedInPublish->deletePublishedUgcPost($linkedin, $this->ugcPostUrn);
        if (! ($result['success'] ?? false)) {
            Log::warning('DeleteLinkedInPostJob: API delete failed', [
                'ugc_post_urn' => $this->ugcPostUrn,
                'linkedin_id' => $this->linkedinAccountId,
                'message' => $result['message'] ?? null,
            ]);
        }
    }
}
