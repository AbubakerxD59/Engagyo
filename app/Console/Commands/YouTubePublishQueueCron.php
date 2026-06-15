<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ScheduledQueuePostPublisher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class YouTubePublishQueueCron extends Command
{
    protected $signature = 'youtube:publish-queue';

    protected $description = 'Publish due YouTube posts that are not calendar-queue items (same pattern as linkedin:publish-queue).';

    public function handle(ScheduledQueuePostPublisher $publisher): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i');
        $posts = Post::with('user.timezone', 'youtube')
            ->notPublished()
            ->past($now)
            ->youtube()
            ->notSchedule()
            ->notRss()
            ->orderBy('publish_date')
            ->get();

        foreach ($posts as $post) {
            try {
                $publisher->publishYouTube($post);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                info("YouTube queue publish error for Post ID {$post->id}: ".$errorMessage);
                $post->update([
                    'status' => -1,
                    'response' => 'Error: '.$errorMessage,
                ]);
                $publisher->notifySchedulePublishException($post, $errorMessage);
            }
        }
    }
}
