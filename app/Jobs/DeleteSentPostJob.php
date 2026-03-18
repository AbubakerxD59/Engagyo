<?php

namespace App\Jobs;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteSentPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private string $facebookPostId,
        private int $pageId
    ) {}

    public function handle(FacebookService $facebookService): void
    {
        $page = Page::with('facebook')->find($this->pageId);
        if (!$page || empty($page->access_token)) {
            return;
        }

        $facebookService->deleteFromFacebook($this->facebookPostId, $page, null);

        $ourPost = Post::where('post_id', $this->facebookPostId)->where('account_id', $page->id)->first();
        if ($ourPost) {
            $user = User::find($ourPost->user_id);
            if ($user && $this->verifyPostAccountBelongsToUser($ourPost, $user)) {
                $user->decrementFeatureUsage('scheduled_posts_per_account', 1);
            }
            $ourPost->photo()->delete();
            $ourPost->delete();
        }
    }

    private function verifyPostAccountBelongsToUser(Post $post, User $user): bool
    {
        if ($post->user_id !== $user->id) {
            return false;
        }
        $page = Page::where('id', $post->account_id)->where('user_id', $user->id)->first();
        return $page !== null;
    }
}
