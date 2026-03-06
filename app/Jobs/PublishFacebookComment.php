<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PublishFacebookComment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    private $postId;
    private $facebookPostId;
    private $accessToken;
    private $comment;

    public function __construct($postId, $facebookPostId, $accessToken, $comment)
    {
        $this->postId = $postId;
        $this->facebookPostId = $facebookPostId;
        $this->accessToken = $accessToken;
        $this->comment = $comment;
    }

    public function handle(): void
    {
        $facebookService = new FacebookService();
        $commentResponse = $facebookService->postComment($this->facebookPostId, $this->accessToken, $this->comment);

        if ($commentResponse["success"] && isset($commentResponse["data"])) {
            $commentId = $commentResponse["data"]->getGraphNode()["id"] ?? null;
            if ($commentId) {
                Post::withoutGlobalScopes()->where('id', $this->postId)->update(['comment_id' => $commentId]);
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("PublishFacebookComment job failed for post {$this->postId}: " . $exception->getMessage());
    }
}
