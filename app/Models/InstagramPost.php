<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_account_id',
        'ig_user_id',
        'ig_media_id',
        'permalink_url',
        'media_type',
        'likes_count',
        'comments_count',
        'saves_count',
        'shares_count',
        'impressions_count',
        'reach_count',
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
        'engagement_rate' => 'decimal:2',
        'fetched_at' => 'datetime',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $posts
     */
    public static function persistFromAnalyticsPosts(int $instagramAccountId, string $igUserId, array $posts): void
    {
        foreach ($posts as $post) {
            if (! is_array($post)) {
                continue;
            }

            $mediaId = (string) ($post['id'] ?? $post['post_id'] ?? '');
            if ($mediaId === '') {
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
                    'instagram_account_id' => $instagramAccountId,
                    'ig_media_id' => $mediaId,
                ],
                [
                    'ig_user_id' => $igUserId,
                    'permalink_url' => $post['permalink_url'] ?? null,
                    'media_type' => $post['media_type'] ?? $post['type'] ?? null,
                    'likes_count' => (int) ($insights['post_reactions'] ?? $insights['likes'] ?? 0),
                    'comments_count' => (int) ($insights['post_comments'] ?? $insights['comments'] ?? 0),
                    'saves_count' => (int) ($insights['post_saves'] ?? $insights['saved'] ?? 0),
                    'shares_count' => (int) ($insights['post_shares'] ?? $insights['shares'] ?? 0),
                    'impressions_count' => (int) ($insights['post_impressions'] ?? $insights['impressions'] ?? 0),
                    'reach_count' => (int) ($insights['post_reach'] ?? $insights['reach'] ?? 0),
                    'engagement_rate' => (float) ($insights['post_engagement_rate'] ?? 0),
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
    public static function forCreatedDateRange(int $instagramAccountId, string $since, string $until): Collection
    {
        $from = $since.' 00:00:00';
        $to = $until.' 23:59:59';

        return self::query()
            ->where('instagram_account_id', $instagramAccountId)
            ->whereBetween('post_created_date', [$from, $to])
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

        $post['id'] = $post['id'] ?? $this->ig_media_id;
        $post['post_id'] = $post['post_id'] ?? $this->ig_media_id;
        $post['created_time'] = $post['created_time'] ?? $this->post_created_date;
        $post['permalink_url'] = $post['permalink_url'] ?? $this->permalink_url;
        $post['media_type'] = $post['media_type'] ?? $this->media_type;
        $post['full_picture'] = $post['full_picture'] ?? $post['media_url'] ?? $post['thumbnail_url'] ?? null;
        $post['message'] = $post['message'] ?? $post['caption'] ?? '';

        return $post;
    }

    public function instagramAccount()
    {
        return $this->belongsTo(InstagramAccount::class);
    }
}
