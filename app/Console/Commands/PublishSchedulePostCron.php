<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Notification;
use App\Services\PostService;
use Illuminate\Console\Command;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Jobs\PublishPinterestPost;
use App\Services\PinterestService;

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
    public function handle(Post $post, PinterestService $pinterestService, FacebookService $facebookService)
    {
        echo "PublishSchedulePostCron started\n";
        $now = date("Y-m-d H:i");
        $query = $post->with("user", "page.facebook", "board.pinterest")->notPublished()->past($now)->schedule();

        $posts = $query->get();
        echo "Total posts: " . $posts->count() . "\n";

        foreach ($posts as $key => $post) {
            echo "Processing Post ID: " . $post->id . "\n";
            $social_type = $post->social_type;
            $account_image = $post->account_profile;
            try {
                // for facebook posts
                if ($post->social_type == "facebook") {
                    $this->processFacebookPost($post);
                }
                // for pinterest posts
                if ($post->social_type == "pinterest") {
                    $this->processPinterestPost($post);
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                info("Schedule Publish Error for Post ID {$post->id}: " . $errorMessage);
                $post->update([
                    "status" => -1,
                    "response" => "Error: " . $errorMessage
                ]);
                // Create error notification (cron job)
                $platform = ucfirst($post->social_type);
                $this->errorNotification($post->user_id, "Scheduled Post Publishing Failed", "Failed to publish scheduled {$platform} post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
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

        if (!$page) {
            $errorMessage = "Error: Facebook page not found.";
            $post->update([
                "status" => -1,
                "response" => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "Scheduled Post Publishing Failed", "Failed to publish scheduled Facebook post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
            return;
        }

        $facebook = $page->facebook;

        if (!$facebook) {
            $errorMessage = "Error: Facebook account not found. Please reconnect your Facebook account.";
            $post->update([
                "status" => -1,
                "response" => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "Scheduled Post Publishing Failed", "Failed to publish scheduled Facebook post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
            return;
        }

        // Use the static validateToken method for proper error handling
        $tokenResponse = FacebookService::validateToken($page);

        if (!$tokenResponse['success']) {
            $errorMessage = $tokenResponse["message"] ?? "Error: Failed to validate Facebook access token.";
            $post->update([
                "status" => -1,
                "response" => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "Scheduled Post Publishing Failed", "Failed to publish scheduled Facebook post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
            return;
        }

        $access_token = $tokenResponse['access_token'];

        try {
            echo "Dispatching Facebook post for Post ID: " . $post->id . "\n";
            $postData = PostService::postTypeBody($post);
            echo "Post Data: " . json_encode($postData) . "\n";
            PublishFacebookPost::dispatch($post->id, $postData, $access_token, $post->type, $post->comment);
        } catch (\Exception $e) {
            $errorMessage = "Error preparing post: " . $e->getMessage();
            $post->update([
                "status" => -1,
                "response" => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "Scheduled Post Publishing Failed", "Failed to publish scheduled Facebook post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
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

        if (!$board) {
            $errorMessage = "Error: Pinterest board not found.";
            $post->update([
                "status" => -1,
                "response" => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "Scheduled Post Publishing Failed", "Failed to publish scheduled Pinterest post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
            return;
        }

        $pinterest = $board->pinterest;

        if (!$pinterest) {
            $errorMessage = "Error: Pinterest account not found. Please reconnect your Pinterest account.";
            $post->update([
                "status" => -1,
                "response" => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "Scheduled Post Publishing Failed", "Failed to publish scheduled Pinterest post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
            return;
        }

        // Use the static validateToken method for proper error handling
        $tokenResponse = PinterestService::validateToken($board);

        if (!$tokenResponse['success']) {
            $errorMessage = $tokenResponse["message"] ?? "Error: Failed to validate Pinterest access token.";
            $post->update([
                "status" => -1,
                "response" => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "Scheduled Post Publishing Failed", "Failed to publish scheduled Pinterest post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
            return;
        }

        $access_token = $tokenResponse['access_token'];

        try {
            $postData = PostService::postTypeBody($post);
            PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);
        } catch (\Exception $e) {
            $errorMessage = "Error preparing post: " . $e->getMessage();
            $post->update([
                "status" => -1,
                "response" => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "Scheduled Post Publishing Failed", "Failed to publish scheduled Pinterest post. " . $errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
        }
    }
}
