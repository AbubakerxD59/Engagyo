<?php

namespace App\Jobs;

use App\Services\FeedService;
use App\Services\TestFeedService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FetchPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    private $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('fetch post');
        $feedService = new FeedService($this->data);
        $feedService->fetch();
    }

    public function failed(\Throwable $exception)
    {
        // This method will be called if the job fails after all attempts (in this case, after the first attempt).
        // You can log the failure, send a notification, or perform any other necessary actions here.
        Log::error("FetchPost job failed: " . $exception->getMessage());
    }
}
