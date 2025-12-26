<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\TikTokTestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupTikTokTestPosts extends Command
{
    protected $signature = 'tiktok:cleanup-tests';

    protected $description = 'Delete TikTok test posts older than 24 hours';

    public function handle()
    {
        $this->info('Starting cleanup of old TikTok test posts...');

        try {
            $cutoffTime = Carbon::now()->subHours(24);
            
            $testPosts = Post::where('source', 'test')
                ->where('social_type', 'tiktok')
                ->where('status', 1)
                ->where('published_at', '<=', $cutoffTime)
                ->get();

            $deletedCount = 0;

            foreach ($testPosts as $post) {
                try {
                    // Note: TikTok delete API is disabled for now, so we just delete from database
                    // if ($post->post_id) {
                    //     $tiktok = $post->tiktok;
                    //     if ($tiktok && $tiktok->access_token) {
                    //         $tiktokService = new \App\Services\TikTokService();
                    //         try {
                    //             $tiktokService->delete($post);
                    //         } catch (\Exception $e) {
                    //             Log::warning('Failed to delete test post from TikTok: ' . $e->getMessage(), [
                    //                 'post_id' => $post->id,
                    //                 'tiktok_post_id' => $post->post_id
                    //             ]);
                    //         }
                    //     }
                    // }
                    
                    $post->delete();
                    $deletedCount++;
                } catch (\Exception $e) {
                    Log::error('Error deleting TikTok test post: ' . $e->getMessage(), [
                        'post_id' => $post->id
                    ]);
                }
            }

            $this->info("Cleaned up {$deletedCount} TikTok test post(s)");

            Log::info('TikTok Test Posts Cleanup Completed', [
                'deleted_count' => $deletedCount,
                'cutoff_time' => $cutoffTime->toDateTimeString()
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('Exception during cleanup: ' . $e->getMessage());
            Log::error('TikTok Test Posts Cleanup Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}

