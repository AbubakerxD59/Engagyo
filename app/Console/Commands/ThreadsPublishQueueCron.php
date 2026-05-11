<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ScheduledQueuePostPublisher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ThreadsPublishQueueCron extends Command
{
    protected $signature = 'threads:publish-queue';

    protected $description = 'Publish due Threads posts that are not calendar-queue items (same pattern as facebook:publish).';

    public function handle(ScheduledQueuePostPublisher $publisher): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i');
        $posts = Post::with('user.timezone', 'thread')
            ->notPublished()
            ->past($now)
            ->threads()
            ->notSchedule()
            ->notRss()
            ->orderBy('publish_date')
            ->get();

        foreach ($posts as $post) {
            try {
                $publisher->publishThreads($post);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                info("Threads queue publish error for Post ID {$post->id}: ".$errorMessage);
                $post->update([
                    'status' => -1,
                    'response' => 'Error: '.$errorMessage,
                ]);
                $publisher->notifySchedulePublishException($post, $errorMessage);
            }
        }
    }
}
