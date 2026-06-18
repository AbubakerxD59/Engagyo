<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubePost extends Model
{
    use HasFactory;

    protected $fillable = [
        'youtube_id',
        'youtube_video_id',
        'permalink_url',
        'title',
        'view_count',
        'like_count',
        'comment_count',
        'share_count',
        'estimated_minutes_watched',
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
    public static function persistFromAnalyticsPosts(int $youtubeId, array $posts): void
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
                    'youtube_id' => $youtubeId,
                    'youtube_video_id' => $videoId,
                ],
                [
                    'permalink_url' => $post['permalink_url'] ?? null,
                    'title' => $post['message'] ?? $post['title'] ?? null,
                    'view_count' => (int) ($insights['view_count'] ?? 0),
                    'like_count' => (int) ($insights['like_count'] ?? 0),
                    'comment_count' => (int) ($insights['comment_count'] ?? 0),
                    'share_count' => (int) ($insights['share_count'] ?? 0),
                    'estimated_minutes_watched' => (int) ($insights['estimated_minutes_watched'] ?? 0),
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
    public static function latestForAccount(int $youtubeId): Collection
    {
        return self::query()
            ->where('youtube_id', $youtubeId)
            ->orderByDesc('post_created_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, self>
     */
    public static function forCreatedDateRange(int $youtubeId, string $since, string $until): Collection
    {
        return self::filterCollectionForDateRange(
            self::latestForAccount($youtubeId),
            $since,
            $until
        );
    }

    /**
     * @param  Collection<int, self>  $posts
     * @return Collection<int, self>
     */
    public static function filterCollectionForDateRange(Collection $posts, string $since, string $until): Collection
    {
        $sinceDt = Carbon::parse($since)->startOfDay();
        $untilDt = Carbon::parse($until)->endOfDay();

        return $posts->filter(function (self $post) use ($sinceDt, $untilDt) {
            $created = $post->resolvedCreatedAt();

            return $created && $created->between($sinceDt, $untilDt);
        })->values();
    }

    public function resolvedCreatedAt(): ?Carbon
    {
        if ($this->post_created_date) {
            return $this->post_created_date->copy();
        }

        $post = is_array($this->post_data) ? $this->post_data : [];
        if (empty($post['created_time'])) {
            return null;
        }

        try {
            return Carbon::parse((string) $post['created_time']);
        } catch (\Throwable) {
            return null;
        }
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

        $post['id'] = $post['id'] ?? $this->youtube_video_id;
        $post['created_time'] = $post['created_time'] ?? $this->post_created_date;
        $post['permalink_url'] = $post['permalink_url'] ?? $this->permalink_url;
        $post['message'] = $post['message'] ?? $this->title;
        $post['type'] = $post['type'] ?? 'video';

        return $post;
    }

    public function youtube()
    {
        return $this->belongsTo(Youtube::class);
    }
}
