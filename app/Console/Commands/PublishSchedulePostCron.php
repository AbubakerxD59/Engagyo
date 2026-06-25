<?php

namespace App\Console\Commands;

use App\Jobs\PublishFacebookPost;
use App\Jobs\PublishPinterestPost;
use App\Models\Notification;
use App\Models\Post;
use App\Services\FacebookPagePostingGuard;
use App\Services\FacebookService;
use App\Services\PinterestService;
use App\Services\PostService;
use App\Services\ScheduledQueuePostPublisher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PublishSchedulePostCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to publish scheduled posts.';

    /**
     * Create a success notification
     */
    private function successNotification($userId, $title, $message, $social_type, $account_image, $account_name = null, $account_username = null)
    {
        $body = ['type' => 'success', 'message' => $message, 'social_type' => $social_type, 'account_image' => $account_image];
        if ($account_name !== null) {
            $body['account_name'] = $account_name;
        }
        if ($account_username !== null) {
            $body['account_username'] = $account_username;
        }
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    /**
     * Create an error notification
     */
    private function errorNotification($userId, $title, $message, $social_type, $account_image, $account_name = null, $account_username = null)
    {
        $body = ['type' => 'error', 'message' => $message, 'social_type' => $social_type, 'account_image' => $account_image];
        if ($account_name !== null) {
            $body['account_name'] = $account_name;
        }
        if ($account_username !== null) {
            $body['account_username'] = $account_username;
        }
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    /**
     * Execute the console command.
     */
    public function handle(ScheduledQueuePostPublisher $queuePublisher)
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i');
        $posts = Post::with('user.timezone', 'page.facebook', 'board.pinterest', 'instagramAccount', 'thread', 'linkedin', 'youtube')
            ->past($now)
            ->notPublished()
            ->schedule()
            ->whereIn('social_type', ['facebook', 'pinterest', 'instagram', 'threads', 'linkedin', 'youtube'])
            ->orderBy('publish_date')
            ->get();
        foreach ($posts as $key => $post) {
            $social_type = $post->social_type;
            $account_image = $post->account_profile;
            try {
                switch ($post->social_type) {
                    case 'facebook':
                        $this->processFacebookPost($post);
                        break;
                    case 'pinterest':
                        $this->processPinterestPost($post);
                        break;
                    case 'instagram':
                        $queuePublisher->publishInstagram($post);
                        break;
                    case 'threads':
                        $queuePublisher->publishThreads($post);
                        break;
                    case 'linkedin':
                        $queuePublisher->publishLinkedIn($post);
                        break;
                    case 'youtube':
                        $queuePublisher->publishYouTube($post);
                        break;
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                info("Schedule Publish Error for Post ID {$post->id}: " . $errorMessage);
                $post->update([
                    'status' => -1,
                    'response' => 'Error: ' . $errorMessage,
                ]);
                // Create error notification (cron job)
                $platform = ucfirst($post->social_type);
                $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', "Failed to publish scheduled {$platform} post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
            }
        }
    }

    /**
     * Process Facebook scheduled post
     */
    private function processFacebookPost(Post $post)
    {
        $page = $post->page;
        $social_type = $post->social_type;
        $account_image = $post->account_profile;

        if (! $page) {
            $errorMessage = 'Error: Facebook page not found.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Facebook post. ' . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        $facebook = $page->facebook;

        if (! $facebook) {
            $errorMessage = 'Error: Facebook account not found. Please reconnect your Facebook account.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Facebook post. ' . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        // Use the static validateToken method for proper error handling
        $tokenResponse = FacebookService::validateToken($page);

        if (! $tokenResponse['success']) {
            $errorMessage = $tokenResponse['message'] ?? 'Error: Failed to validate Facebook access token.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Facebook post. ' . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        $access_token = $tokenResponse['access_token'];

        $postingGuard = new FacebookPagePostingGuard;
        if (! $postingGuard->canPublish($page)) {
            info("Schedule Publish: Post ID {$post->id} skipped - {$postingGuard->blockReason($page)}");

            return;
        }

        try {
            $postData = PostService::postTypeBody($post);
            PublishFacebookPost::dispatch($post->id, $postData, $access_token, $post->type, $post->comment);
        } catch (\Exception $e) {
            $errorMessage = 'Error preparing post: ' . $e->getMessage();
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Facebook post. ' . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
        }
    }

    /**
     * Process Pinterest scheduled post
     */
    private function processPinterestPost(Post $post)
    {
        $board = $post->board;
        $social_type = $post->social_type;
        $account_image = $post->account_profile;

        if (! $board) {
            $errorMessage = 'Error: Pinterest board not found.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Pinterest post. ' . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        $pinterest = $board->pinterest;

        if (! $pinterest) {
            $errorMessage = 'Error: Pinterest account not found. Please reconnect your Pinterest account.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Pinterest post. ' . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        // Use the static validateToken method for proper error handling
        $tokenResponse = PinterestService::validateToken($board);

        if (! $tokenResponse['success']) {
            $errorMessage = $tokenResponse['message'] ?? 'Error: Failed to validate Pinterest access token.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Pinterest post. ' . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        $access_token = $tokenResponse['access_token'];

        try {
            $postData = PostService::postTypeBody($post);
            PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);
        } catch (\Exception $e) {
            $errorMessage = 'Error preparing post: ' . $e->getMessage();
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Pinterest post. ' . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
        }
    }
}
