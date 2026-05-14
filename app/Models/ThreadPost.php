<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreadPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'threads_post_id',
        'permalink_url',
        'media_type',
        'impressions_count',
        'reach_count',
        'reactions_count',
        'comments_count',
        'shares_count',
        'clicks_count',
        'engagement_rate',
        'post_data',
        'post_created_date',
        'post_insights',
        'fetched_at',
    ];

    protected $casts = [
        'post_data' => 'array',
        'post_insights' => 'array',
        'post_created_date' => 'datetime',
        'fetched_at' => 'datetime',
        'engagement_rate' => 'decimal:2',
    ];

    /**
     * Persist each normalized Threads API post as its own row (same pattern as FacebookPost).
     *
     * @param  array<int, array<string, mixed>>  $posts
     */
    public static function persistFromAnalyticsPosts(int $threadId, array $posts): void
    {
        foreach ($posts as $post) {
            if (! is_array($post)) {
                continue;
            }

            $threadsPostId = (string) ($post['id'] ?? '');
            if ($threadsPostId === '') {
                continue;
            }

            $insights = is_array($post['insights'] ?? null) ? $post['insights'] : [];
            $views = (int) ($insights['views'] ?? $insights['post_impressions'] ?? 0);
            $likes = (int) ($insights['likes'] ?? $insights['post_reactions'] ?? 0);
            $replies = (int) ($insights['replies'] ?? 0);
            $reposts = (int) ($insights['reposts'] ?? 0);
            $quotes = (int) ($insights['quotes'] ?? $insights['post_clicks'] ?? 0);
            $shares = (int) ($insights['shares'] ?? 0);
            $rate = (float) ($insights['post_engagement_rate'] ?? 0);

            $createdAt = null;
            if (! empty($post['created_time'])) {
                try {
                    $createdAt = Carbon::parse((string) $post['created_time']);
                } catch (\Throwable) {
                    $createdAt = null;
                }
            }

            self::updateOrCreate(
                [
                    'thread_id' => $threadId,
                    'threads_post_id' => $threadsPostId,
                ],
                [
                    'permalink_url' => $post['permalink_url'] ?? null,
                    'media_type' => $post['media_type'] ?? null,
                    'impressions_count' => $views,
                    'reach_count' => (int) ($insights['post_reach'] ?? $views),
                    'reactions_count' => $likes,
                    'comments_count' => $replies,
                    'shares_count' => $shares + $reposts,
                    'clicks_count' => $quotes,
                    'engagement_rate' => $rate,
                    'post_data' => $post,
                    'post_insights' => $insights,
                    'post_created_date' => $createdAt,
                    'fetched_at' => now(),
                ]
            );
        }
    }

    /**
     * @return Collection<int, self>
     */
    public static function forCreatedDateRange(int $threadId, string $since, string $until): Collection
    {
        return self::query()
            ->where('thread_id', $threadId)
            ->whereBetween('post_created_date', [$since.' 00:00:00', $until.' 23:59:59'])
            ->orderByDesc('post_created_date')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAnalyticsPostArray(): array
    {
        $post = is_array($this->post_data) ? $this->post_data : [];
        $insights = is_array($this->post_insights) ? $this->post_insights : [];

        if (! isset($post['insights']) || ! is_array($post['insights'])) {
            $post['insights'] = $insights;
        }

        $post['id'] = $post['id'] ?? $this->threads_post_id;
        $post['created_time'] = $post['created_time'] ?? $this->post_created_date;
        $post['permalink_url'] = $post['permalink_url'] ?? $this->permalink_url;
        $post['media_type'] = $post['media_type'] ?? $this->media_type;

        return $post;
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }
}
