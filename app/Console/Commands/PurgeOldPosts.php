<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PurgeOldPosts extends Command
{
    protected $signature = 'posts:purge-old';

    protected $description = 'Delete posts older than the specified number of days along with their local files and S3 videos';

    public function handle()
    {
        $days = 30;
        $limit =  200;
        $dryRun = false;
        $cutoff = Carbon::now()->subDays($days);

        $modeLabel = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$modeLabel}Purging up to {$limit} posts created before {$cutoff->toDateTimeString()} ({$days} days ago)...");

        $totalEligible = Post::withoutGlobalScopes()
            ->where('created_at', '<', $cutoff)
            ->count();

        $posts = Post::withoutGlobalScopes()
            ->where('created_at', '<', $cutoff)
            ->oldest()
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            $this->info("{$modeLabel}No posts older than {$days} days found. Nothing to do.");
            return 0;
        }

        $this->info("{$modeLabel}Found {$totalEligible} eligible post(s). Processing {$posts->count()} this run (limit: {$limit}).");

        $deletedPosts = 0;
        $deletedPhotos = 0;
        $deletedLocalFiles = 0;
        $deletedS3Files = 0;
        $errors = 0;

        foreach ($posts as $post) {
            try {
                $rawImage = $post->getRawOriginal('image');
                if (!empty($rawImage) && !str_contains($rawImage, 'http')) {
                    $localPath = public_path('uploads/' . $rawImage);
                    if (File::exists($localPath)) {
                        if (!$dryRun) {
                            File::delete($localPath);
                        }
                        $deletedLocalFiles++;
                        $this->line("{$modeLabel}  Deleted local image: uploads/{$rawImage}");
                    }
                }

                if (!empty($post->video)) {
                    $videoFilename = basename($post->video);
                    $localVideoPath = public_path('uploads/' . $videoFilename);
                    if (File::exists($localVideoPath)) {
                        if (!$dryRun) {
                            File::delete($localVideoPath);
                        }
                        $deletedLocalFiles++;
                        $this->line("{$modeLabel}  Deleted local video: uploads/{$videoFilename}");
                    }

                    try {
                        $s3Disk = Storage::disk('s3');
                        if ($s3Disk->exists($post->video)) {
                            if (!$dryRun) {
                                $s3Disk->delete($post->video);
                            }
                            $deletedS3Files++;
                            $this->line("{$modeLabel}  Deleted S3 video: {$post->video}");
                        }
                    } catch (\Exception $e) {
                        $this->warn("  Failed to delete S3 video [{$post->video}]: {$e->getMessage()}");
                        Log::warning('PurgeOldPosts: S3 delete failed', [
                            'post_id' => $post->id,
                            'video' => $post->video,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $photoCount = Photo::where('post_id', $post->id)->count();
                if ($photoCount > 0) {
                    if (!$dryRun) {
                        Photo::where('post_id', $post->id)->delete();
                    }
                    $deletedPhotos += $photoCount;
                }

                if (!$dryRun) {
                    Post::withoutGlobalScopes()->where('id', $post->id)->delete();
                }
                $deletedPosts++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("  Error processing post #{$post->id}: {$e->getMessage()}");
                Log::error('PurgeOldPosts: Error processing post', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info("{$modeLabel}Purge complete:");
        $this->info("  Posts deleted:        {$deletedPosts}");
        $this->info("  Photos deleted:       {$deletedPhotos}");
        $this->info("  Local files deleted:  {$deletedLocalFiles}");
        $this->info("  S3 files deleted:     {$deletedS3Files}");
        $remaining = $totalEligible - $deletedPosts;
        if ($remaining > 0) {
            $this->info("  Remaining eligible:   {$remaining} (will be processed in future runs)");
        }
        if ($errors > 0) {
            $this->warn("  Errors encountered:   {$errors}");
        }

        Log::info('PurgeOldPosts completed', [
            'dry_run' => $dryRun,
            'days' => $days,
            'posts_deleted' => $deletedPosts,
            'photos_deleted' => $deletedPhotos,
            'local_files_deleted' => $deletedLocalFiles,
            's3_files_deleted' => $deletedS3Files,
            'errors' => $errors,
        ]);

        return 0;
    }
}
