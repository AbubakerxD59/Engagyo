<?php

namespace App\Console;

use App\Console\Commands\FeedCron;
use App\Console\Commands\ResetMonthlyUsage;
use App\Console\Commands\CheckFeatureLimits;
use App\Console\Commands\ShuffleRssPosts;
use App\Console\Commands\SyncUserUsage;
use App\Console\Commands\PublishRssPostsCron;
use App\Console\Commands\PinterestPublishCron;
use App\Console\Commands\FacebookPublishCron;
use App\Console\Commands\TikTokPublishCron;
use App\Console\Commands\PublishSchedulePostCron;
use App\Console\Commands\DownloadPhotoCron;
use App\Console\Commands\RunFacebookTests;
use App\Console\Commands\CleanupTestPosts;
use App\Console\Commands\RunPinterestTests;
use App\Console\Commands\CleanupPinterestTestPosts;
use App\Console\Commands\RunTikTokTests;
use App\Console\Commands\CleanupTikTokTestPosts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The commands to register.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        FeedCron::class,
        ResetMonthlyUsage::class,
        CheckFeatureLimits::class,
        SyncUserUsage::class,
        PublishRssPostsCron::class,
        PinterestPublishCron::class,
        FacebookPublishCron::class,
        TikTokPublishCron::class,
        PublishSchedulePostCron::class,
        DownloadPhotoCron::class,
        RunFacebookTests::class,
        CleanupTestPosts::class,
        RunPinterestTests::class,
        CleanupPinterestTestPosts::class,
        RunTikTokTests::class,
        CleanupTikTokTestPosts::class,
        ShuffleRssPosts::class,
    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Command to fetch latest posts for each domain (runs daily at 03:00 when load is minimum)
        $schedule->command('rss:feed')->dailyAt('03:00');
        // Command to publish RSS posts (within 2 hour window)
        $schedule->command('rss:publish')->everyFiveMinutes();
        // Command to publish Pinterest posts
        $schedule->command('pinterest:publish')->everyFiveMinutes();
        // Command to publish Facebook posts
        $schedule->command('facebook:publish')->everyFiveMinutes();
        // Command to publish TikTok posts
        $schedule->command('tiktok:publish')->everyFiveMinutes();
        // Command to publish Scheduled posts
        $schedule->command('schedule:publish')->everyMinute();
        // Command to download photos
        $schedule->command('download:photo')->everyFiveMinutes();
        // Command to reset monthly feature usage (runs on the 1st of each month at 00:00)
        $schedule->command('usage:reset-monthly')->monthlyOn(1, '00:00');
        // Command to check feature limits and send notifications (runs daily at 09:00)
        $schedule->command('features:check-limits')->dailyAt('09:00');
        // Command to sync user usage records (runs daily at 02:00)
        $schedule->command('usage:sync')->dailyAt('02:00');
        // Command to cleanup test posts older than 24 hours (runs hourly)
        $schedule->command('facebook:cleanup-tests')->hourly();
        // Command to cleanup Pinterest test posts older than 24 hours (runs hourly)
        $schedule->command('pinterest:cleanup-tests')->hourly();
        // Command to cleanup TikTok test posts older than 24 hours (runs hourly)
        $schedule->command('tiktok:cleanup-tests')->hourly();
        // Command to shuffle rss posts
        $schedule->command('app:shuffle-rss-posts')->dailyAt('01:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
