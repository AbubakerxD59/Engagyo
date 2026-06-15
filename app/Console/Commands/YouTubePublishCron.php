<?php

namespace App\Console\Commands;

use App\Jobs\PublishYouTubePost;
use App\Models\Post;
use App\Services\PostService;
use App\Services\YouTubeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class YouTubePublishCron extends Command
{
    protected $signature = 'youtube:publish';

    protected $description = 'Publish due YouTube video posts that are not calendar-queue items.';

    public function handle(): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i');

        $posts = Post::with('user', 'youtube')
            ->notPublished()
            ->past($now)
            ->youtube()
            ->notSchedule()
            ->notRss()
            ->orderBy('publish_date')
            ->get();

        foreach ($posts as $post) {
            try {
                sleep(3);
                $youtube = $post->youtube;

                if (! $youtube) {
                    $post->update([
                        'status' => -1,
                        'response' => 'YouTube account not found.',
                        'published_at' => date('Y-m-d H:i:s'),
                    ]);

                    continue;
                }

                $tokenResponse = YouTubeService::validateToken($youtube);
                if (empty($tokenResponse['success'])) {
                    $post->update([
                        'status' => -1,
                        'response' => $tokenResponse['message'] ?? 'Failed to validate YouTube access token.',
                        'published_at' => date('Y-m-d H:i:s'),
                    ]);

                    continue;
                }

                $postData = PostService::postTypeBody($post);
                PublishYouTubePost::dispatch($post->id, $postData, $tokenResponse['access_token']);
            } catch (Exception $e) {
                $post->update([
                    'status' => -1,
                    'response' => $e->getMessage(),
                    'published_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
