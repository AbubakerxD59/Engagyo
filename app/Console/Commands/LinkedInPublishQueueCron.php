<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ScheduledQueuePostPublisher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LinkedInPublishQueueCron extends Command
{
    protected $signature = 'linkedin:publish-queue';

    protected $description = 'Publish due LinkedIn posts from the schedule queue (scheduled slot posts).';

    public function handle(ScheduledQueuePostPublisher $publisher): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i');
        $posts = Post::with('user.timezone', 'linkedin')
            ->past($now)
            ->notPublished()
            ->schedule()
            ->notRss()
            ->linkedin()
            ->orderBy('publish_date')
            ->get();

        foreach ($posts as $post) {
            try {
                $publisher->publishLinkedIn($post);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                info("LinkedIn queue publish error for Post ID {$post->id}: ".$errorMessage);
                $post->update([
                    'status' => -1,
                    'response' => 'Error: '.$errorMessage,
                ]);
                $publisher->notifySchedulePublishException($post, $errorMessage);
            }
        }
    }
}
