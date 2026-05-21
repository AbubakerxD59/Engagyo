<?php

namespace App\Services;

use App\Mail\FailedPostEmail;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FailedPostEmailService
{
    /**
     * Send a failed-post email immediately to the given user (testing only).
     * Does not apply daily limits or skip test-source posts.
     *
     * @return array{success: bool, message: string, email?: string}
     */
    public function sendTestToUser(User $user, Post $post): array
    {
        if (empty($user->email)) {
            return [
                'success' => false,
                'message' => 'User has no email address.',
            ];
        }

        try {
            Mail::to($user->email)->send(new FailedPostEmail($post));

            return [
                'success' => true,
                'message' => 'Failed post test email sent.',
                'email' => $user->email,
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to send failed post test email', [
                'user_id' => $user->id,
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not send email: '.$e->getMessage(),
            ];
        }
    }

    public function send(Post $post): void
    {
        if ((string) ($post->source ?? '') === 'test') {
            return;
        }

        $post->loadMissing('user');
        $user = $post->user;

        if (! $user || empty($user->email)) {
            return;
        }

        if (! $this->reserveDailySlot((int) $user->id)) {
            Log::info('Failed post email skipped: daily limit reached', [
                'user_id' => $user->id,
                'post_id' => $post->id,
                'limit' => $this->dailyLimit(),
            ]);

            return;
        }

        try {
            Mail::to($user->email)->queue(new FailedPostEmail($post));
        } catch (\Throwable $e) {
            $this->releaseDailySlot((int) $user->id);
            Log::warning('Failed to queue failed post email', [
                'post_id' => $post->id,
                'user_id' => $post->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reserve one of the user's daily failed-post email slots (thread-safe).
     */
    private function reserveDailySlot(int $userId): bool
    {
        $limit = $this->dailyLimit();
        if ($limit <= 0) {
            return false;
        }

        $cacheKey = $this->dailyCountCacheKey($userId);
        $lockKey = $cacheKey.':lock';

        return (bool) Cache::lock($lockKey, 10)->block(5, function () use ($cacheKey, $limit) {
            $count = (int) Cache::get($cacheKey, 0);
            if ($count >= $limit) {
                return false;
            }

            Cache::put($cacheKey, $count + 1, now()->endOfDay());

            return true;
        });
    }

    /**
     * Undo a reserved slot when queuing the mailable fails.
     */
    private function releaseDailySlot(int $userId): void
    {
        $cacheKey = $this->dailyCountCacheKey($userId);
        $lockKey = $cacheKey.':lock';

        Cache::lock($lockKey, 10)->block(5, function () use ($cacheKey) {
            $count = (int) Cache::get($cacheKey, 0);
            if ($count > 0) {
                Cache::put($cacheKey, $count - 1, now()->endOfDay());
            }
        });
    }

    private function dailyLimit(): int
    {
        return max(0, (int) config('mail_branding.failed_post_email_daily_limit', 10));
    }

    private function dailyCountCacheKey(int $userId): string
    {
        return 'failed_post_email_count:'.$userId.':'.now()->toDateString();
    }
}
