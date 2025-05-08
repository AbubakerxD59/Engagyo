<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PublishFacebookPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    private $id;
    private $data;
    private $post;
    private $access_token;
    /**
     * Create a new job instance.
     */
    public function __construct($id, $data, $access_token)
    {
        $this->id = $id;
        $this->data = $data;
        $this->access_token = $access_token;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $facebookService = new facebookService();
        $facebookService->createLink($this->id, $this->access_token, $this->data);
    }

    public function failed(\Throwable $exception)
    {
        // This method will be called if the job fails after all attempts (in this case, after the first attempt).
        // You can log the failure, send a notification, or perform any other necessary actions here.
        Log::error("FetchPost job failed: " . $exception->getMessage());
    }
}
