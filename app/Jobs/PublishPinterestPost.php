<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use App\Services\PinterestService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PublishPinterestPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;
    private $data;
    private $access_token;
    private $pinterest;
    /**
     * Create a new job instance.
     */
    public function __construct($id, $data, $access_token)
    {
        $this->id = $id;
        $this->data = $data;
        $this->access_token = $access_token;
        $this->pinterest = new PinterestService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $publish = $this->pinterest->create($this->id, $this->access_token, $this->data);
    }
}
