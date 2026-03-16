<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScheduleShufflePosts extends Command
{
    protected $signature = 'schedule:shuffle';

    protected $description = 'Shuffle pending scheduled posts for pages with schedule_shuffle enabled. Runs daily.';

    public function handle()
    {
        $pages = Page::where('schedule_shuffle', 1)->get();

        foreach ($pages as $page) {
            $posts = Post::where('user_id', $page->user_id)
                ->where('account_id', $page->id)
                ->where('social_type', 'like', '%facebook%')
                ->where('status', 0)
                ->where('source', 'schedule')
                ->get();

            if ($posts->count() < 2) {
                continue;
            }

            $publishDates = $posts->pluck('publish_date')->shuffle()->values();

            DB::transaction(function () use ($posts, $publishDates) {
                foreach ($posts as $index => $post) {
                    $post->update(['publish_date' => $publishDates[$index] ?? $post->publish_date]);
                }
            });

            $this->info("Shuffled {$posts->count()} posts for page: {$page->name} (ID: {$page->id})");
        }

        return Command::SUCCESS;
    }
}
