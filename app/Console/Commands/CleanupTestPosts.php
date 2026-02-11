<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\FacebookTestCase;
use App\Services\PostService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupTestPosts extends Command
{
    protected $signature = 'facebook:cleanup-tests';

    protected $description = 'Delete test posts older than 24 hours';

    public function handle()
    {
        $this->info('Starting cleanup of old test posts...');

        try {
            $cutoffTime = Carbon::now()->subHours(24);
            
            $testPosts = Post::where('source', 'test')
                ->where('status', 1)
                ->where('published_at', '<=', $cutoffTime)
                ->get();

            $deletedCount = 0;

            foreach ($testPosts as $post) {
                try {
                    if ($post->post_id) {
                        $page = $post->page;
                        if ($page && $page->access_token) {
                            $facebookService = new \App\Services\FacebookService();
                            try {
                                $facebookService->delete($post);
                            } catch (\Exception $e) {
                                Log::warning('Failed to delete test post from Facebook: ' . $e->getMessage(), [
                                    'post_id' => $post->id,
                                    'facebook_post_id' => $post->post_id
                                ]);
                            }
                        }
                    }
                    
                    PostService::delete($post->id);
                    $deletedCount++;
                } catch (\Exception $e) {
                    Log::error('Error deleting test post: ' . $e->getMessage(), [
                        'post_id' => $post->id
                    ]);
                }
            }

            $this->info("Cleaned up {$deletedCount} test post(s)");

            Log::info('Test Posts Cleanup Completed', [
                'deleted_count' => $deletedCount,
                'cutoff_time' => $cutoffTime->toDateTimeString()
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('Exception during cleanup: ' . $e->getMessage());
            Log::error('Test Posts Cleanup Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}

