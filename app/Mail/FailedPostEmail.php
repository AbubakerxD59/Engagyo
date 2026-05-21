<?php

namespace App\Mail;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class FailedPostEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Post $post
    ) {
        $this->onQueue(config('mail_branding.queue', 'default'));
        $connection = config('mail_branding.queue_connection');
        if ($connection && $connection !== 'sync') {
            $this->onConnection($connection);
        }
    }

    public function build()
    {
        $data = self::viewDataForPost($this->post);

        return $this->subject('Post failed to publish on ' . ($data['platform'] ?? 'social media') . ' — ' . email_app_name())
            ->view('emails.failed-post', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public static function viewDataForPost(Post $post): array
    {
        $post->loadMissing([
            'user.timezone',
            'page.facebook',
            'board.pinterest',
            'tiktok',
            'instagramAccount',
            'thread',
            'linkedin',
            'domain',
            'apiKey',
        ]);

        $user = $post->user;
        $platform = ucfirst((string) ($post->social_type ?? 'social media'));
        $type = strtolower((string) ($post->type ?? 'post'));

        $postTypeLabel = match ($type) {
            'link' => 'Link',
            'photo' => 'Photo',
            'video' => 'Video',
            'reel' => 'Reel',
            'story' => 'Story',
            'carousel' => 'Carousel',
            'document' => 'Document',
            'content_only' => 'Text only',
            'quote' => 'Quote',
            default => ucfirst($type),
        };

        $source = (string) ($post->source ?? '');
        $sourceLabel = match ($source) {
            'schedule' => 'Scheduled',
            'rss' => 'RSS automation',
            'api' => 'API',
            'test' => 'Test',
            default => $source !== '' ? ucfirst(str_replace('_', ' ', $source)) : 'Manual',
        };

        $description = trim(strip_tags((string) ($post->description ?? '')));
        $comment = trim(strip_tags((string) ($post->comment ?? '')));

        return [
            'user' => $user,
            'post' => $post,
            'postId' => $post->id,
            'platform' => $platform,
            'postType' => $postTypeLabel,
            'source' => $sourceLabel,
            'accountName' => (string) ($post->account_name ?? ''),
            'accountUsername' => (string) ($post->account_username ?? ''),
            'title' => trim((string) ($post->title ?? '')),
            'description' => $description !== '' ? Str::limit($description, 600) : null,
            'comment' => ($comment !== '' && $comment !== $description) ? Str::limit($comment, 400) : null,
            'url' => trim((string) ($post->url ?? '')),
            'domainName' => (string) ($post->domain_name ?? ''),
            'apiKeyName' => $source === 'api' ? (string) ($post->api_key_name ?? '') : null,
            'scheduledAt' => $post->publish_date_time,
            'failedAt' => $post->published_at_formatted,
            'errorMessage' => (string) ($post->response_message ?? 'The post could not be published. Please try again.'),
            'imageUrl' => (string) ($post->image ?? ''),
            'hasVideo' => ! empty($post->video),
            'panelUrl' => route('panel.schedule'),
        ];
    }
}
