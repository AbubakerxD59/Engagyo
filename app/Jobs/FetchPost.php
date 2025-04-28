<?php

namespace App\Jobs;

use App\Services\FeedService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data,)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        dd($this->job);
        $feedService = new FeedService();
        $feedService->fetch($this->data["urlDomain"], $this->data["domain"], $this->data["user"], $this->data["account_id"], $this->data["type"], $this->data["time"], $this->data["mode"]);
    }
}
