<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\PinterestTestCase;
use App\Services\PostService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupPinterestTestPosts extends Command
{
    protected $signature = 'pinterest:cleanup-tests';

    protected $description = 'Delete Pinterest test posts older than 24 hours';

    public function handle()
    {
        $this->info('Starting cleanup of old Pinterest test posts...');

        try {
            $cutoffTime = Carbon::now()->subHours(24);
            
            $testPosts = Post::where('source', 'test')
                ->where('social_type', 'pinterest')
                ->where('status', 1)
                ->where('published_at', '<=', $cutoffTime)
                ->get();

            $deletedCount = 0;

            foreach ($testPosts as $post) {
                try {
                    if ($post->post_id) {
                        $board = $post->board;
                        if ($board && $board->pinterest && $board->pinterest->access_token) {
                            $pinterestService = new \App\Services\PinterestService();
                            try {
                                $pinterestService->delete($post);
                            } catch (\Exception $e) {
                                Log::warning('Failed to delete test post from Pinterest: ' . $e->getMessage(), [
                                    'post_id' => $post->id,
                                    'pinterest_post_id' => $post->post_id
                                ]);
                            }
                        }
                    }
                    
                    PostService::delete($post->id);
                    $deletedCount++;
                } catch (\Exception $e) {
                    Log::error('Error deleting Pinterest test post: ' . $e->getMessage(), [
                        'post_id' => $post->id
                    ]);
                }
            }

            $this->info("Cleaned up {$deletedCount} Pinterest test post(s)");

            Log::info('Pinterest Test Posts Cleanup Completed', [
                'deleted_count' => $deletedCount,
                'cutoff_time' => $cutoffTime->toDateTimeString()
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('Exception during cleanup: ' . $e->getMessage());
            Log::error('Pinterest Test Posts Cleanup Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}

