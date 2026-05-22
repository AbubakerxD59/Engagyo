<?php

namespace App\Services;

use App\Mail\WeeklyAccountsReportEmail;
use App\Models\Board;
use App\Models\InstagramAccount;
use App\Models\Linkedin;
use App\Models\Page;
use App\Models\Post;
use App\Models\Thread;
use App\Models\Tiktok;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WeeklyAccountsReportService
{
    /**
     * @return array{success: bool, message: string, email?: string}
     */
    public function sendTestToUser(User $user): array
    {
        if (empty($user->email)) {
            return [
                'success' => false,
                'message' => 'User has no email address.',
            ];
        }

        try {
            Mail::to($user->email)->send(new WeeklyAccountsReportEmail($user));

            return [
                'success' => true,
                'message' => 'Weekly accounts report test email sent.',
                'email' => $user->email,
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to send weekly accounts report test email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not send email: '.$e->getMessage(),
            ];
        }
    }

    public function queueForUser(User $user): void
    {
        if (empty($user->email)) {
            return;
        }

        Mail::to($user->email)->queue(new WeeklyAccountsReportEmail($user));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportForUser(User $user, ?Carbon $periodEnd = null): array
    {
        $periodEnd = $periodEnd ?? now();
        $periodStart = $periodEnd->copy()->subDays(7);

        $owner = $user->getEffectiveUser() ?? $user;
        $postCountsByAccount = $this->loadPostCountsByAccount($user);
        $platforms = $this->loadPlatforms($user, $owner, $postCountsByAccount);
        $packageSummary = $this->packageAccountSummary($owner, $platforms);

        $postStats = $this->weeklyPostStats($user, $periodStart, $periodEnd);
        $accountPostTotals = $this->aggregateAccountPostTotals($platforms);

        return [
            'user' => $user,
            'periodLabel' => $periodStart->format('M j').' – '.$periodEnd->format('M j, Y'),
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'summary' => array_merge($packageSummary, $postStats, $accountPostTotals, [
                'totalAccounts' => $this->countAccounts($platforms),
            ]),
            'platforms' => $platforms,
            'accountsUrl' => route('panel.accounts'),
            'scheduleUrl' => route('panel.schedule'),
        ];
    }

    /**
     * @return array<string, array{total: int, published: int, failed: int, pending: int}>
     */
    private function loadPostCountsByAccount(User $user): array
    {
        $creatorIds = $user->schedulePostCreatorUserIds();

        $rows = Post::withoutGlobalScopes()
            ->whereIn('user_id', $creatorIds)
            ->where('source', '!=', 'test')
            ->selectRaw('account_id')
            ->selectRaw('social_type')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as published_count')
            ->selectRaw('SUM(CASE WHEN status = -1 THEN 1 ELSE 0 END) as failed_count')
            ->selectRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending_count')
            ->groupBy('account_id', 'social_type')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $platform = $this->normalizeSocialTypeToPlatform($row->social_type);
            if ($platform === null) {
                continue;
            }

            $key = $platform.'_'.(int) $row->account_id;

            if (! isset($map[$key])) {
                $map[$key] = [
                    'total' => 0,
                    'published' => 0,
                    'failed' => 0,
                    'pending' => 0,
                ];
            }

            $map[$key]['total'] += (int) $row->total_count;
            $map[$key]['published'] += (int) $row->published_count;
            $map[$key]['failed'] += (int) $row->failed_count;
            $map[$key]['pending'] += (int) $row->pending_count;
        }

        return $map;
    }

    /**
     * @param  array<string, array{total: int, published: int, failed: int, pending: int}>  $postCountsByAccount
     * @return array{total: int, published: int, failed: int, pending: int}
     */
    private function postStatsForAccount(array $postCountsByAccount, string $platformKey, int $accountId): array
    {
        $key = $platformKey.'_'.$accountId;

        return $postCountsByAccount[$key] ?? [
            'total' => 0,
            'published' => 0,
            'failed' => 0,
            'pending' => 0,
        ];
    }

    private function normalizeSocialTypeToPlatform(?string $socialType): ?string
    {
        $normalized = strtolower((string) $socialType);

        foreach (['facebook', 'pinterest', 'tiktok', 'instagram', 'threads', 'linkedin'] as $platform) {
            if (str_contains($normalized, $platform)) {
                return $platform;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $platforms
     * @return array{totalPosts: int, publishedPostsAll: int, failedPostsAll: int, pendingPostsAll: int}
     */
    private function aggregateAccountPostTotals(array $platforms): array
    {
        $totals = [
            'totalPosts' => 0,
            'publishedPostsAll' => 0,
            'failedPostsAll' => 0,
            'pendingPostsAll' => 0,
        ];

        foreach ($platforms as $platform) {
            foreach ($platform['accounts'] as $account) {
                $posts = $account['posts'] ?? [];
                $totals['totalPosts'] += (int) ($posts['total'] ?? 0);
                $totals['publishedPostsAll'] += (int) ($posts['published'] ?? 0);
                $totals['failedPostsAll'] += (int) ($posts['failed'] ?? 0);
                $totals['pendingPostsAll'] += (int) ($posts['pending'] ?? 0);
            }
        }

        return $totals;
    }

    /**
     * @param  array<string, array{total: int, published: int, failed: int, pending: int}>  $postCountsByAccount
     * @return array<int, array<string, mixed>>
     */
    private function loadPlatforms(User $user, User $owner, array $postCountsByAccount): array
    {
        $definitions = [
            [
                'key' => 'facebook',
                'label' => 'Facebook Pages',
                'logo' => social_logo('facebook'),
                'type' => 'page',
                'load' => fn () => Page::query()
                    ->where('user_id', $owner->id)
                    ->with('facebook')
                    ->orderBy('name')
                    ->get(),
                'map' => function ($page) use ($postCountsByAccount) {
                    return [
                        'name' => (string) $page->name,
                        'subtitle' => $page->facebook ? '@'.$page->facebook->username : null,
                        'imageUrl' => $this->absoluteAssetUrl($page->profile_image),
                        'posts' => $this->postStatsForAccount($postCountsByAccount, 'facebook', (int) $page->id),
                    ];
                },
            ],
            [
                'key' => 'pinterest',
                'label' => 'Pinterest Boards',
                'logo' => social_logo('pinterest'),
                'type' => 'board',
                'load' => fn () => Board::query()
                    ->where('user_id', $owner->id)
                    ->with('pinterest')
                    ->orderBy('name')
                    ->get(),
                'map' => function ($board) use ($postCountsByAccount) {
                    return [
                        'name' => (string) $board->name,
                        'subtitle' => $board->pinterest ? '@'.$board->pinterest->username : null,
                        'imageUrl' => $this->absoluteAssetUrl(
                            ($board->pinterest && $board->pinterest->profile_image)
                                ? $board->pinterest->profile_image
                                : social_logo('pinterest')
                        ),
                        'posts' => $this->postStatsForAccount($postCountsByAccount, 'pinterest', (int) $board->id),
                    ];
                },
            ],
            [
                'key' => 'tiktok',
                'label' => 'TikTok Accounts',
                'logo' => social_logo('tiktok'),
                'type' => 'tiktok',
                'load' => fn () => Tiktok::query()
                    ->where('user_id', $owner->id)
                    ->orderBy('username')
                    ->get(),
                'map' => function ($tiktok) use ($postCountsByAccount) {
                    return [
                        'name' => (string) ($tiktok->display_name ?: $tiktok->username),
                        'subtitle' => '@'.$tiktok->username,
                        'imageUrl' => $this->absoluteAssetUrl($tiktok->profile_image ?: social_logo('tiktok')),
                        'posts' => $this->postStatsForAccount($postCountsByAccount, 'tiktok', (int) $tiktok->id),
                    ];
                },
            ],
            [
                'key' => 'instagram',
                'label' => 'Instagram Accounts',
                'logo' => social_logo('instagram'),
                'type' => 'instagram',
                'load' => fn () => InstagramAccount::query()
                    ->where('user_id', $owner->id)
                    ->orderBy('username')
                    ->get(),
                'map' => function ($ig) use ($postCountsByAccount) {
                    return [
                        'name' => (string) ($ig->name ?: $ig->username),
                        'subtitle' => $ig->username ? '@'.$ig->username : null,
                        'imageUrl' => $this->absoluteAssetUrl($ig->profile_image ?: social_logo('instagram')),
                        'posts' => $this->postStatsForAccount($postCountsByAccount, 'instagram', (int) $ig->id),
                    ];
                },
            ],
            [
                'key' => 'threads',
                'label' => 'Threads Accounts',
                'logo' => social_logo('threads'),
                'type' => 'threads',
                'load' => fn () => Thread::query()
                    ->where('user_id', $owner->id)
                    ->orderBy('username')
                    ->get(),
                'map' => function ($thread) use ($postCountsByAccount) {
                    return [
                        'name' => (string) $thread->username,
                        'subtitle' => '@'.$thread->username,
                        'imageUrl' => $this->absoluteAssetUrl($thread->profile_image ?: social_logo('threads')),
                        'posts' => $this->postStatsForAccount($postCountsByAccount, 'threads', (int) $thread->id),
                    ];
                },
            ],
            [
                'key' => 'linkedin',
                'label' => 'LinkedIn Accounts',
                'logo' => social_logo('linkedin'),
                'type' => 'linkedin',
                'load' => fn () => Linkedin::query()
                    ->where('user_id', $owner->id)
                    ->orderBy('username')
                    ->get(),
                'map' => function ($linkedin) use ($postCountsByAccount) {
                    return [
                        'name' => (string) $linkedin->username,
                        'subtitle' => $linkedin->email ?: null,
                        'imageUrl' => $this->absoluteAssetUrl($linkedin->profile_image ?: social_logo('linkedin')),
                        'posts' => $this->postStatsForAccount($postCountsByAccount, 'linkedin', (int) $linkedin->id),
                    ];
                },
            ],
        ];

        $platforms = [];

        foreach ($definitions as $definition) {
            $items = $definition['load']();
            if ($user->isTeamMember()) {
                $allowedIds = $user->getTeamMemberAccountIdsByType($definition['type']);
                $items = $items->whereIn('id', $allowedIds)->values();
            }

            $accounts = $items->map($definition['map'])->values()->all();

            $platforms[] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'logo' => $this->absoluteAssetUrl($definition['logo']),
                'count' => count($accounts),
                'accounts' => $accounts,
            ];
        }

        return $platforms;
    }

    /**
     * @param  array<int, array<string, mixed>>  $platforms
     */
    private function countAccounts(array $platforms): int
    {
        return (int) collect($platforms)->sum('count');
    }

    /**
     * Package limit uses boards + pages + TikTok (same as usage sync).
     *
     * @param  array<int, array<string, mixed>>  $platforms
     * @return array{packageTotal: int, limit: ?int, remaining: ?int}
     */
    private function packageAccountSummary(User $owner, array $platforms): array
    {
        $byKey = collect($platforms)->keyBy('key');
        $packageTotal = (int) ($byKey->get('facebook')['count'] ?? 0)
            + (int) ($byKey->get('pinterest')['count'] ?? 0)
            + (int) ($byKey->get('tiktok')['count'] ?? 0);

        $limit = null;
        $remaining = null;

        $owner->loadMissing('userPackages.package.features');
        $activePackage = $owner->activeUserPackage;
        if ($activePackage?->package) {
            $feature = $activePackage->package->features()
                ->where('key', 'social_accounts')
                ->wherePivot('is_enabled', true)
                ->first();
            if ($feature) {
                $limitValue = $feature->pivot->limit_value ?? null;
                if ($limitValue !== null && (int) $limitValue > 0) {
                    $limit = (int) $limitValue;
                    $remaining = max(0, $limit - $packageTotal);
                }
            }
        }

        return [
            'packageTotal' => $packageTotal,
            'limit' => $limit,
            'remaining' => $remaining,
        ];
    }

    /**
     * @return array{publishedPosts: int, failedPosts: int, queuedPosts: int}
     */
    private function weeklyPostStats(User $user, Carbon $periodStart, Carbon $periodEnd): array
    {
        $creatorIds = $user->schedulePostCreatorUserIds();
        $start = $periodStart->copy()->startOfDay()->utc();
        $end = $periodEnd->copy()->endOfDay()->utc();

        $base = Post::withoutGlobalScopes()
            ->whereIn('user_id', $creatorIds)
            ->where('source', '!=', 'test');

        $publishedPosts = (clone $base)
            ->where('status', 1)
            ->whereBetween('published_at', [$start, $end])
            ->count();

        $failedPosts = (clone $base)
            ->where('status', -1)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('published_at', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->whereNull('published_at')
                            ->whereBetween('updated_at', [$start, $end]);
                    });
            })
            ->count();

        $queuedPosts = (clone $base)
            ->where('status', 0)
            ->count();

        return [
            'publishedPosts' => $publishedPosts,
            'failedPosts' => $failedPosts,
            'queuedPosts' => $queuedPosts,
        ];
    }

    private function absoluteAssetUrl(?string $path): string
    {
        if ($path === null || $path === '') {
            return rtrim(config('app.url'), '/').'/assets/img/noimage.png';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}
