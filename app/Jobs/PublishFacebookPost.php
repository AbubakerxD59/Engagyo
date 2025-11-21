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
    private $type;
    private $comment;
    /**
     * Create a new job instance.
     */
    public function __construct($id, $data, $access_token, $type, $comment = null)
    {
        $this->id = $id;
        $this->data = $data;
        $this->access_token = $access_token;
        $this->type = $type;
        $this->comment = $comment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $facebookService = new facebookService();
        if ($this->type == "link") {
            $publish_response = $facebookService->createLink($this->id, $this->access_token, $this->data);
        } elseif ($this->type == "content_only") {
            $publish_response = $facebookService->contentOnly($this->id, $this->access_token, $this->data);
        } elseif ($this->type == "photo") {
            $publish_response = $facebookService->photo($this->id, $this->access_token, $this->data);
        } elseif ($this->type == "video") {
            $publish_response = $facebookService->video($this->id, $this->access_token, $this->data);
        }
        if ($publish_response["success"]) {
            $post_id = $publish_response["data"]->getGraphNode() ? $publish_response["data"]->getGraphNode()["id"] : null;
            if ($post_id) {
                $facebookService->postComment($post_id, $this->access_token, $this->comment);
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("FetchPost job failed: " . $exception->getMessage());
    }
}
