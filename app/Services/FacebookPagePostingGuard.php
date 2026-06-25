<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Page;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FacebookPagePostingGuard
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_TESTING = 'testing';

    public const STATUS_STOPPED = 'stopped';

    /**
     * Detect Facebook Graph API errors that indicate a page policy / violation restriction.
     */
    public static function isViolationError(?string $message): bool
    {
        if ($message === null || $message === '') {
            return false;
        }

        $normalized = strtolower($message);

        $patterns = [
            'community standards',
            'community standard',
            'violat',
            'isn\'t allowed to post',
            'is not allowed to post',
            'not allowed to post',
            'publishing authorization',
            'restricted from publishing',
            'restricted from posting',
            'temporarily blocked',
            'page has been restricted',
            'page publishing',
            'content you\'re trying to share',
            'content you are trying to share',
            '(#368)',
            'error_subcode":1366051',
            'error_subcode":2108006',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($normalized, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    public function refreshPostingState(Page $page): Page
    {
        $page->refresh();

        if (
            $page->facebook_posting_status === self::STATUS_PAUSED
            && $page->facebook_posting_paused_until !== null
            && Carbon::now('UTC')->gte(Carbon::parse($page->facebook_posting_paused_until)->utc())
        ) {
            $page->update([
                'facebook_posting_status' => self::STATUS_TESTING,
                'facebook_posting_paused_until' => null,
            ]);
            $page->refresh();

            $this->notify(
                $page,
                'Facebook Posting Test',
                "Posting for page \"{$page->name}\" was paused due to a Facebook policy issue. The next scheduled post will be attempted as a test. If it publishes successfully, normal posting will resume."
            );

            Log::info('Facebook page posting moved to testing after pause', [
                'page_id' => $page->id,
                'facebook_page_id' => $page->page_id,
            ]);
        }

        return $page;
    }

    public function canPublish(Page $page): bool
    {
        $page = $this->refreshPostingState($page);

        return in_array($page->facebook_posting_status, [self::STATUS_ACTIVE, self::STATUS_TESTING], true);
    }

    public function blockReason(Page $page): ?string
    {
        $page = $this->refreshPostingState($page);

        return match ($page->facebook_posting_status) {
            self::STATUS_PAUSED => $this->pausedReason($page),
            self::STATUS_STOPPED => 'Facebook posting is stopped for this page due to repeated policy violations. Fix the issue in Facebook, then resume posting from Automation.',
            default => null,
        };
    }

    public function recordViolation(Page $page, ?string $errorMessage = null): void
    {
        $page->refresh();

        if ($page->facebook_posting_status === self::STATUS_TESTING) {
            $page->update([
                'facebook_posting_status' => self::STATUS_STOPPED,
                'facebook_posting_paused_until' => null,
            ]);

            $this->notify(
                $page,
                'Facebook Posting Stopped',
                "Posting for page \"{$page->name}\" has been stopped because Facebook rejected another post due to a policy violation. Resolve the issue in Facebook, then resume posting from Automation."
            );

            Log::warning('Facebook page posting stopped after repeated violation', [
                'page_id' => $page->id,
                'facebook_page_id' => $page->page_id,
                'error' => $errorMessage,
            ]);

            return;
        }

        if ($page->facebook_posting_status !== self::STATUS_ACTIVE) {
            return;
        }

        $pauseMinutes = random_int(240, 300);
        $pausedUntil = Carbon::now('UTC')->addMinutes($pauseMinutes);

        $page->update([
            'facebook_posting_status' => self::STATUS_PAUSED,
            'facebook_posting_paused_until' => $pausedUntil,
        ]);

        $hours = round($pauseMinutes / 60, 1);

        $this->notify(
            $page,
            'Facebook Posting Paused',
            "Posting for page \"{$page->name}\" has been paused for about {$hours} hours because Facebook rejected a post due to a policy violation. The next post will be attempted automatically after the pause."
        );

        Log::warning('Facebook page posting paused after violation', [
            'page_id' => $page->id,
            'facebook_page_id' => $page->page_id,
            'paused_until' => $pausedUntil->toDateTimeString(),
            'error' => $errorMessage,
        ]);
    }

    public function recordPublishSuccess(Page $page): void
    {
        $page->refresh();

        if ($page->facebook_posting_status !== self::STATUS_TESTING) {
            return;
        }

        $page->update([
            'facebook_posting_status' => self::STATUS_ACTIVE,
            'facebook_posting_paused_until' => null,
        ]);

        $this->notify(
            $page,
            'Facebook Posting Resumed',
            "Posting for page \"{$page->name}\" has resumed after a successful test post."
        );

        Log::info('Facebook page posting resumed after successful test post', [
            'page_id' => $page->id,
            'facebook_page_id' => $page->page_id,
        ]);
    }

    public function resumeByUser(Page $page): array
    {
        $page->refresh();

        if ($page->facebook_posting_status !== self::STATUS_STOPPED) {
            return [
                'success' => false,
                'message' => 'This page does not require manual posting resume.',
            ];
        }

        $page->update([
            'facebook_posting_status' => self::STATUS_ACTIVE,
            'facebook_posting_paused_until' => null,
        ]);

        $this->notify(
            $page,
            'Facebook Posting Resumed',
            "Posting for page \"{$page->name}\" was resumed manually."
        );

        return [
            'success' => true,
            'message' => 'Facebook posting resumed for this page.',
            'status' => self::STATUS_ACTIVE,
        ];
    }

    private function pausedReason(Page $page): string
    {
        if ($page->facebook_posting_paused_until === null) {
            return 'Facebook posting is temporarily paused for this page due to a policy violation.';
        }

        $until = Carbon::parse($page->facebook_posting_paused_until)->utc();
        $formatted = $until->format('M j, Y g:i A').' UTC';

        return "Facebook posting is paused for this page until {$formatted} due to a policy violation.";
    }

    private function notify(Page $page, string $title, string $message): void
    {
        $page->loadMissing('facebook');

        $accountImage = null;
        if (! empty($page->profile_image)) {
            $accountImage = $page->profile_image;
        } elseif ($page->facebook && ! empty($page->facebook->profile_image)) {
            $accountImage = $page->facebook->profile_image;
        }

        Notification::create([
            'user_id' => $page->user_id,
            'title' => $title,
            'body' => [
                'type' => 'error',
                'message' => $message,
                'social_type' => 'facebook',
                'account_image' => $accountImage,
                'account_name' => $page->name ?? '',
                'account_username' => $page->facebook?->username ?? '',
            ],
            'is_read' => false,
            'is_system' => false,
        ]);
    }
}
