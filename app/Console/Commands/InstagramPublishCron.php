<?php

namespace App\Console\Commands;

use App\Jobs\PublishInstagramPost;
use App\Models\Post;
use App\Services\FacebookService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class InstagramPublishCron extends Command
{
    protected $signature = 'instagram:publish';

    protected $description = 'Publish Instagram posts that are due (non-scheduled queue / publish-now flow).';

    public function handle(Post $post): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i');

        $posts = $post->with('instagramAccount.linkedPage')
            ->notPublished()
            ->past($now)
            ->instagram()
            ->notSchedule()
            ->notRss()
            ->get();

        foreach ($posts as $post) {
            try {
                sleep(3);
                $ig = $post->instagramAccount;
                $page = $ig?->linkedPage;

                if (! $ig || ! $page) {
                    $post->update([
                        'status' => -1,
                        'response' => 'Instagram account or linked Facebook Page not found.',
                        'published_at' => date('Y-m-d H:i:s'),
                    ]);
                    continue;
                }

                $tokenResponse = FacebookService::validateToken($page);
                if (! $tokenResponse['success']) {
                    $post->update([
                        'status' => -1,
                        'response' => $tokenResponse['message'] ?? 'Failed to validate Facebook access token.',
                        'published_at' => date('Y-m-d H:i:s'),
                    ]);
                    continue;
                }

                PublishInstagramPost::dispatch($post->id, $tokenResponse['access_token']);
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
