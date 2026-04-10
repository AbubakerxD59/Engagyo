<?php

namespace App\Console\Commands;

use App\Jobs\PublishInstagramPost;
use App\Models\InstagramAccount;
use App\Models\Post;
use App\Models\User;
use App\Services\FacebookService;
use App\Services\TimezoneService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TestInstagramImagePublish extends Command
{
    protected $signature = 'instagram:test-image
        {user_id : users.id that owns the Instagram account}
        {instagram_account_id : instagram_accounts.id}
        {image : Path to a JPG/PNG/WebP file (absolute or relative to project root)}
        {--caption= : Optional caption (stored as post comment; merged with title for Instagram)}';

    protected $description = 'Copy a local image to public/uploads, create a Post, and publish to Instagram via PublishInstagramPost. Meta must fetch image_url over public HTTPS — set APP_URL or INSTAGRAM_IMAGE_PUBLIC_BASE_URL.';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $igAccountId = (int) $this->argument('instagram_account_id');
        $imagePath = $this->argument('image');

        if (! is_file($imagePath)) {
            $resolved = base_path($imagePath);
            if (is_file($resolved)) {
                $imagePath = $resolved;
            } else {
                $this->error('Image file not found: '.$this->argument('image'));

                return self::FAILURE;
            }
        }

        $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $this->error('Use jpg, jpeg, png, or webp.');

            return self::FAILURE;
        }

        $user = User::find($userId);
        if (! $user) {
            $this->error("User not found: {$userId}");

            return self::FAILURE;
        }

        $ig = InstagramAccount::where('id', $igAccountId)->where('user_id', $userId)->first();
        if (! $ig) {
            $this->error("Instagram account {$igAccountId} not found for user {$userId}.");

            return self::FAILURE;
        }

        if (empty($ig->ig_user_id)) {
            $this->error('Instagram account has no ig_user_id. Reconnect the account.');

            return self::FAILURE;
        }

        $uploadsDir = public_path('uploads');
        if (! is_dir($uploadsDir)) {
            File::makeDirectory($uploadsDir, 0755, true);
        }

        $fileName = strtotime(date('Y-m-d H:i:s')).random_int(1000, 999999).'.'.$ext;
        $dest = $uploadsDir.DIRECTORY_SEPARATOR.$fileName;
        if (! @copy($imagePath, $dest)) {
            $this->error("Could not copy image to {$dest}");

            return self::FAILURE;
        }

        $nowLocal = now()->format('Y-m-d H:i');
        $publishUtc = TimezoneService::toUtc($nowLocal, $user);

        $comment = (string) ($this->option('caption') ?? '');

        $post = Post::create([
            'user_id' => $userId,
            'account_id' => $igAccountId,
            'social_type' => 'instagram',
            'type' => 'photo',
            'source' => 'test',
            'title' => 'Instagram image test',
            'comment' => $comment,
            'image' => $fileName,
            'publish_date' => $publishUtc,
            'scheduled' => 0,
            'status' => 0,
        ]);

        $this->info("Created post id={$post->id}; file public/uploads/{$fileName}");

        $tokenResult = FacebookService::validateToken($ig);
        if (! ($tokenResult['success'] ?? false)) {
            $this->error($tokenResult['message'] ?? 'Token validation failed.');

            return self::FAILURE;
        }

        $token = $tokenResult['access_token'];
        PublishInstagramPost::dispatchSync($post->id, $token);

        $post->refresh();
        $this->line('status: '.$post->status);
        $this->line('response: '.$post->response);

        return (int) $post->status === 1 ? self::SUCCESS : self::FAILURE;
    }
}
