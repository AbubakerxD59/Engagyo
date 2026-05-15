<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiktokPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'tiktok_id',
        'tiktok_video_id',
        'share_url',
        'title',
        'view_count',
        'like_count',
        'comment_count',
        'share_count',
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
    ];

    /**
     * @param  array<int, array<string, mixed>>  $posts
     */
    public static function persistFromAnalyticsPosts(int $tiktokId, array $posts): void
    {
        foreach ($posts as $post) {
            if (! is_array($post)) {
                continue;
            }

            $videoId = (string) ($post['id'] ?? '');
            if ($videoId === '') {
                continue;
            }

            $insights = is_array($post['insights'] ?? null) ? $post['insights'] : [];

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
                    'tiktok_id' => $tiktokId,
                    'tiktok_video_id' => $videoId,
                ],
                [
                    'share_url' => $post['permalink_url'] ?? $post['share_url'] ?? null,
                    'title' => $post['message'] ?? $post['title'] ?? null,
                    'view_count' => (int) ($insights['view_count'] ?? 0),
                    'like_count' => (int) ($insights['like_count'] ?? 0),
                    'comment_count' => (int) ($insights['comment_count'] ?? 0),
                    'share_count' => (int) ($insights['share_count'] ?? 0),
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
    public static function forCreatedDateRange(int $tiktokId, string $since, string $until): Collection
    {
        return self::query()
            ->where('tiktok_id', $tiktokId)
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

        $post['id'] = $post['id'] ?? $this->tiktok_video_id;
        $post['created_time'] = $post['created_time'] ?? $this->post_created_date;
        $post['permalink_url'] = $post['permalink_url'] ?? $this->share_url;

        return $post;
    }

    public function tiktok()
    {
        return $this->belongsTo(Tiktok::class);
    }
}
