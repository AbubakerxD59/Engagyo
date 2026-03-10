<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\FacebookService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishPendingCommentsCron extends Command
{
    protected $signature = 'facebook:publish-pending-comments';

    protected $description = 'Publish comments on Facebook posts that were published recently but their comment failed or was skipped (e.g. videos)';

    public function handle(): void
    {
        $this->publishPendingComments();

        sleep(30);

        $this->publishPendingComments();
    }

    private function publishPendingComments(): void
    {
        $since = Carbon::now('UTC')->subMinutes(30);

        $posts = Post::withoutGlobalScopes()
            ->with('page.facebook')
            ->where('social_type', 'like', '%facebook%')
            ->where('status', 1)
            ->whereNotNull('post_id')
            ->where('post_id', '!=', '')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->whereNull('comment_id')
            ->where('published_at', '>=', $since)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No pending comments to publish.');
            return;
        }

        $this->info("Found {$posts->count()} post(s) with pending comments.");

        foreach ($posts as $post) {
            try {
                $page = $post->page;

                if (!$page) {
                    $this->warn("Post #{$post->id}: page not found, skipping.");
                    continue;
                }

                $tokenResponse = FacebookService::validateToken($page);
                if (!$tokenResponse['success']) {
                    $this->warn("Post #{$post->id}: token invalid — {$tokenResponse['message']}");
                    continue;
                }

                $facebookService = new FacebookService();
                $commentResponse = $facebookService->postComment(
                    $post->post_id,
                    $tokenResponse['access_token'],
                    $post->comment
                );

                if ($commentResponse['success'] && isset($commentResponse['data'])) {
                    $commentId = $commentResponse['data']->getGraphNode()['id'] ?? null;
                    if ($commentId) {
                        $post->update(['comment_id' => $commentId]);
                        $this->info("Post #{$post->id}: comment published successfully (comment_id: {$commentId}).");
                    }
                } else {
                    $this->warn("Post #{$post->id}: comment failed — " . ($commentResponse['message'] ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                Log::error("PublishPendingComments: Post #{$post->id} error: " . $e->getMessage());
                $this->error("Post #{$post->id}: " . $e->getMessage());
            }
        }
    }
}
