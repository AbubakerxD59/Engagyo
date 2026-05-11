<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ScheduledQueuePostPublisher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class InstagramPublishQueueCron extends Command
{
    protected $signature = 'instagram:publish-queue';

    protected $description = 'Publish due Instagram posts from the schedule queue (scheduled slot posts).';

    public function handle(ScheduledQueuePostPublisher $publisher): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i');
        $posts = Post::with('user.timezone', 'instagramAccount')
            ->past($now)
            ->notPublished()
            ->schedule()
            ->notRss()
            ->instagram()
            ->orderBy('publish_date')
            ->get();

        foreach ($posts as $post) {
            try {
                $publisher->publishInstagram($post);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                info("Instagram queue publish error for Post ID {$post->id}: ".$errorMessage);
                $post->update([
                    'status' => -1,
                    'response' => 'Error: '.$errorMessage,
                ]);
                $publisher->notifySchedulePublishException($post, $errorMessage);
            }
        }
    }
}
