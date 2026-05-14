<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PinterestPin extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
        'pinterest_pin_id',
        'title',
        'description',
        'link',
        'media_url',
        'pin_type',
        'impressions_count',
        'saves_count',
        'outbound_clicks_count',
        'pin_clicks_count',
        'video_views_count',
        'comments_count',
        'reactions_count',
        'engagement_rate',
        'post_data',
        'post_insights',
        'pin_created_at',
        'fetched_at',
    ];

    protected $casts = [
        'post_data' => 'array',
        'post_insights' => 'array',
        'pin_created_at' => 'datetime',
        'fetched_at' => 'datetime',
        'engagement_rate' => 'decimal:2',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $pins
     */
    public static function persistFromAnalyticsPins(int $boardId, array $pins): void
    {
        foreach ($pins as $post) {
            if (! is_array($post)) {
                continue;
            }
            $pinId = (string) ($post['id'] ?? '');
            if ($pinId === '') {
                continue;
            }
            $insights = is_array($post['insights'] ?? null) ? $post['insights'] : [];
            $impressions = (int) ($insights['post_impressions'] ?? 0);
            $saves = (int) ($insights['pin_saves'] ?? 0);
            $outbound = (int) ($insights['outbound_clicks'] ?? 0);
            $pinClicks = (int) ($insights['pin_clicks'] ?? 0);
            $videoViews = (int) ($insights['video_mrc_view'] ?? 0);
            $comments = (int) ($insights['total_comments'] ?? 0);
            $reactions = (int) ($insights['total_reactions'] ?? 0);
            $engagements = $saves + $outbound + $pinClicks + $comments + $reactions;
            $rate = $impressions > 0 ? round(($engagements / $impressions) * 100, 2) : 0.0;

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
                    'board_id' => $boardId,
                    'pinterest_pin_id' => $pinId,
                ],
                [
                    'title' => $post['title'] ?? null,
                    'description' => $post['description'] ?? null,
                    'link' => $post['link'] ?? null,
                    'media_url' => $post['full_picture'] ?? null,
                    'pin_type' => $post['type'] ?? null,
                    'impressions_count' => $impressions,
                    'saves_count' => $saves,
                    'outbound_clicks_count' => $outbound,
                    'pin_clicks_count' => $pinClicks,
                    'video_views_count' => $videoViews,
                    'comments_count' => $comments,
                    'reactions_count' => $reactions,
                    'engagement_rate' => $rate,
                    'post_data' => $post,
                    'post_insights' => $insights,
                    'pin_created_at' => $createdAt,
                    'fetched_at' => now(),
                ]
            );
        }
    }

    /**
     * Pins stored for a board (metrics reflect last sync window; not filtered by pin_created_at).
     *
     * @return Collection<int, self>
     */
    public static function latestForBoard(int $boardId, int $limit = 120): Collection
    {
        return self::query()
            ->where('board_id', $boardId)
            ->orderByDesc('pin_created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, self>
     */
    public static function forCreatedDateRange(int $boardId, string $since, string $until): Collection
    {
        return self::query()
            ->where('board_id', $boardId)
            ->whereBetween('pin_created_at', [$since.' 00:00:00', $until.' 23:59:59'])
            ->orderByDesc('pin_created_at')
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
        $post['id'] = $post['id'] ?? $this->pinterest_pin_id;
        $post['created_time'] = $post['created_time'] ?? $this->pin_created_at;
        $post['permalink_url'] = $post['permalink_url'] ?? null;
        $post['full_picture'] = $post['full_picture'] ?? $this->media_url;
        $post['message'] = $post['message'] ?? (string) ($this->description ?? $this->title ?? '');

        return $post;
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
