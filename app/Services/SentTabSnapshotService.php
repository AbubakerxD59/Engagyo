<?php

namespace App\Services;

use App\Models\Board;
use App\Models\FacebookPost;
use App\Models\InstagramAccount;
use App\Models\InstagramPost;
use App\Models\Page;
use App\Models\PinterestPin;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadPost;
use App\Models\Tiktok;
use App\Models\TiktokPost;
use App\Models\Youtube;
use App\Models\YoutubePost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SentTabSnapshotService
{
    public static function purgeForPost(Post $post): void
    {
        if ((int) $post->status !== 1) {
            return;
        }

        $externalId = trim((string) ($post->post_id ?? ''));
        if ($externalId === '') {
            return;
        }

        $userId = (int) ($post->user_id ?? Auth::id() ?? 0);
        $social = strtolower((string) $post->social_type);

        if ($social === 'facebook' && $post->page) {
            self::purgeFacebook($post->page, $externalId);

            return;
        }

        if ($social === 'pinterest' && $post->board) {
            self::purgePinterest($userId, $post->board, $externalId);

            return;
        }

        if (str_contains($social, 'tiktok') && $post->tiktok) {
            self::purgeTiktok($userId, $post->tiktok, $externalId);

            return;
        }

        if (str_contains($social, 'thread') && $post->thread) {
            self::purgeThreads($userId, $post->thread, $externalId);

            return;
        }

        if (str_contains($social, 'instagram') && $post->instagramAccount) {
            self::purgeInstagram($userId, $post->instagramAccount, $externalId);

            return;
        }

        if (str_contains($social, 'youtube') && $post->youtube) {
            self::purgeYoutube($userId, $post->youtube, $externalId);
        }
    }

    public static function purgeFacebook(Page $page, string $externalPostId): void
    {
        if ($externalPostId === '') {
            return;
        }

        FacebookPost::query()
            ->where('fb_page_id', $page->page_id)
            ->where('fb_post_id', $externalPostId)
            ->delete();

        $duration = 'full_year';
        $until = now()->format('Y-m-d');
        $since = now()->subYear()->format('Y-m-d');
        Cache::forget(self::facebookDurationPostsCacheKey((string) $page->page_id, $duration, $since, $until));
    }

    public static function purgePinterest(int $userId, Board $board, string $pinId): void
    {
        if ($pinId === '') {
            return;
        }

        PinterestPin::query()
            ->where('board_id', $board->id)
            ->where('pinterest_pin_id', $pinId)
            ->delete();

        if ($userId > 0) {
            $duration = 'full_year';
            $until = now()->format('Y-m-d');
            $since = now()->subYear()->format('Y-m-d');
            Cache::forget(self::pinterestSentPostsCacheKey($userId, (int) $board->id, $duration, $since, $until));
        }
    }

    public static function purgeTiktok(int $userId, Tiktok $tiktok, string $externalPostId): void
    {
        if ($externalPostId === '') {
            return;
        }

        TiktokPost::query()
            ->where('tiktok_id', $tiktok->id)
            ->where(function ($q) use ($externalPostId) {
                $q->where('tiktok_video_id', $externalPostId)
                    ->orWhere('share_url', 'like', '%'.$externalPostId.'%');
            })
            ->delete();

        if ($userId > 0) {
            $duration = 'full_year';
            $until = now()->format('Y-m-d');
            $since = now()->subYear()->format('Y-m-d');
            Cache::forget(self::sentPostsCacheKey($userId, (int) $tiktok->id, $duration, $since, $until));
        }
    }

    public static function purgeInstagram(int $userId, InstagramAccount $account, string $mediaId): void
    {
        if ($mediaId === '') {
            return;
        }

        InstagramPost::query()
            ->where('instagram_account_id', $account->id)
            ->where('ig_media_id', $mediaId)
            ->delete();
    }

    public static function purgeThreads(int $userId, Thread $thread, string $externalPostId): void
    {
        if ($externalPostId === '') {
            return;
        }

        ThreadPost::query()
            ->where('thread_id', $thread->id)
            ->where('threads_post_id', $externalPostId)
            ->delete();

        if ($userId > 0) {
            $duration = 'full_year';
            $until = now()->format('Y-m-d');
            $since = now()->subYear()->format('Y-m-d');
            Cache::forget(self::sentPostsCacheKey($userId, (int) $thread->id, $duration, $since, $until));
        }
    }

    public static function purgeYoutube(int $userId, Youtube $youtube, string $videoId): void
    {
        if ($videoId === '') {
            return;
        }

        YoutubePost::query()
            ->where('youtube_id', $youtube->id)
            ->where('youtube_video_id', $videoId)
            ->delete();

        if ($userId > 0) {
            $duration = 'full_year';
            $until = now()->format('Y-m-d');
            $since = now()->subYear()->format('Y-m-d');
            Cache::forget(self::analyticsPostsCacheKey($userId, (int) $youtube->id, $duration, $since, $until));
        }
    }

    private static function analyticsPostsCacheKey(int $userId, int $youtubeId, string $duration, string $since, string $until): string
    {
        return implode(':', [
            'analytics_posts',
            'v2',
            'user',
            $userId,
            'platform',
            'youtube',
            'account',
            $youtubeId,
            'duration',
            $duration,
            'since',
            $since,
            'until',
            $until,
        ]);
    }

    private static function sentPostsCacheKey(int $userId, int $pageId, string $duration, string $since, string $until): string
    {
        return implode(':', [
            'schedule_sent_posts',
            'v1',
            'user',
            $userId,
            'page',
            $pageId,
            'duration',
            $duration,
            'since',
            $since,
            'until',
            $until,
        ]);
    }

    private static function pinterestSentPostsCacheKey(int $userId, int $boardId, string $duration, string $since, string $until): string
    {
        return implode(':', [
            'schedule_sent_pinterest_pins',
            'v1',
            'user',
            $userId,
            'board',
            $boardId,
            'duration',
            $duration,
            'since',
            $since,
            'until',
            $until,
        ]);
    }

    private static function facebookDurationPostsCacheKey(string $pageId, string $duration, string $since, string $until): string
    {
        return implode(':', [
            'facebook_posts_by_duration',
            'v1',
            'page',
            $pageId,
            'duration',
            $duration,
            'since',
            $since,
            'until',
            $until,
        ]);
    }
}
