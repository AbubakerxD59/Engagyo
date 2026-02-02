<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Post;
use App\Models\Notification;
use App\Services\PostService;
use Illuminate\Console\Command;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Jobs\PublishPinterestPost;
use App\Services\PinterestService;
use Carbon\Carbon;

class PublishRssPostsCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rss:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish pending RSS posts that are due for publishing (within 2 hours window).';

    /**
     * Create a success notification
     */
    private function successNotification($userId, $title, $message, $social_type, $account_image)
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => ['type' => 'success', 'message' => $message, 'social_type' => $social_type, 'account_image' => $account_image],
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    /**
     * Create an error notification
     */
    private function errorNotification($userId, $title, $message, $social_type, $account_image)
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => ['type' => 'error', 'message' => $message, 'social_type' => $social_type, 'account_image' => $account_image],
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    /**
     * Execute the console command.
     */
    public function handle(PinterestService $pinterestService, FacebookService $facebookService)
    {
        $now = Carbon::now();
        $twoHoursAgo = $now->copy()->subHours(2);

        // Get all pending RSS posts that are due (publish_date is before now but not older than 2 hours)
        $posts = Post::with("user", "page.facebook", "board.pinterest")
            ->where('source', 'rss')
            ->where('status', 0) // Pending
            ->where('publish_date', '<=', $now)
            ->where('publish_date', '>=', $twoHoursAgo)
            ->get();

        info("RSS Publish Cron: Found {$posts->count()} RSS posts to process.");

        foreach ($posts as $post) {
            $social_type = $post->social_type;
            $account_image = $post->account_profile;
            try {
                $this->processPost($post, $pinterestService, $facebookService);
            } catch (Exception $e) {
                // Log the error and update post status
                $errorMessage = $e->getMessage();
                info("RSS Publish Cron Error for Post ID {$post->id}: " . $errorMessage);
                $post->update([
                    'status' => -1,
                    'response' => "Error: " . $errorMessage
                ]);
                // Create error notification (cron job)
                $platform = ucfirst($post->social_type);
                $this->errorNotification($post->user_id, "RSS Post Publishing Failed", "Failed to publish {$platform} RSS post. " . $errorMessage, $social_type, $account_image);
            }
        }

        $this->info("RSS posts publishing completed.");
    }

    /**
     * Process a single RSS post
     */
    private function processPost(Post $post, PinterestService $pinterestService, FacebookService $facebookService)
    {
        // Handle Facebook posts
        if ($post->social_type === 'facebook') {
            $this->processFacebookPost($post, $facebookService);
        }

        // Handle Pinterest posts
        if ($post->social_type === 'pinterest') {
            $this->processPinterestPost($post, $pinterestService);
        }
    }

    /**
     * Process Facebook RSS post
     */
    private function processFacebookPost(Post $post, FacebookService $facebookService)
    {
        $page = $post->page;
        $social_type = $post->social_type;
        $account_image = $post->account_profile;

        // Check if page exists
        if (!$page) {
            $errorMessage = "Error: Facebook page not found. The page may have been disconnected.";
            $post->update([
                'status' => -1,
                'response' => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "RSS Post Publishing Failed", "Failed to publish Facebook RSS post. " . $errorMessage, $social_type, $account_image);
            return;
        }

        // Check if RSS automation is paused for this page
        if ($page->rss_paused) {
            $errorMessage = "RSS automation is paused for this Facebook page. Enable RSS automation to publish posts.";
            $post->update([
                'status' => -1,
                'response' => $errorMessage
            ]);
            info("RSS Publish: Post ID {$post->id} skipped - RSS automation is paused for Facebook page '{$page->name}'.");
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "RSS Post Publishing Failed", "Failed to publish Facebook RSS post. " . $errorMessage, $social_type, $account_image);
            return;
        }

        // Validate and refresh access token if needed using the static validateToken method
        $tokenResponse = FacebookService::validateToken($page);

        if (!$tokenResponse['success']) {
            $errorMessage = $tokenResponse['message'] ?? "Error: Failed to validate Facebook access token.";
            $post->update([
                'status' => -1,
                'response' => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "RSS Post Publishing Failed", "Failed to publish Facebook RSS post. " . $errorMessage, $social_type, $account_image);
            return;
        }

        $access_token = $tokenResponse['access_token'];

        // Prepare post data
        try {
            $postData = PostService::postTypeBody($post);
        } catch (Exception $e) {
            $post->update([
                'status' => -1,
                'response' => "Error preparing post data: " . $e->getMessage()
            ]);
            return;
        }

        // Dispatch the publish job
        PublishFacebookPost::dispatch($post->id, $postData, $access_token, $post->type, $post->comment);

        info("RSS Publish: Dispatched Facebook post ID {$post->id} to page '{$page->name}'.");
    }

    /**
     * Process Pinterest RSS post
     */
    private function processPinterestPost(Post $post, PinterestService $pinterestService)
    {
        $board = $post->board;
        $social_type = $post->social_type;
        $account_image = $post->account_profile;

        // Check if board exists
        if (!$board) {
            $errorMessage = "Error: Pinterest board not found. The board may have been disconnected.";
            $post->update([
                'status' => -1,
                'response' => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "RSS Post Publishing Failed", "Failed to publish Pinterest RSS post. " . $errorMessage, $social_type, $account_image);
            return;
        }

        // Check if RSS automation is paused for this board
        if ($board->rss_paused) {
            $errorMessage = "RSS automation is paused for this Pinterest board. Enable RSS automation to publish posts.";
            $post->update([
                'status' => -1,
                'response' => $errorMessage
            ]);
            info("RSS Publish: Post ID {$post->id} skipped - RSS automation is paused for Pinterest board '{$board->name}'.");
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "RSS Post Publishing Failed", "Failed to publish Pinterest RSS post. " . $errorMessage, $social_type, $account_image);
            return;
        }

        // Validate and refresh access token if needed using the new validateToken method
        $tokenResponse = PinterestService::validateToken($board);

        if (!$tokenResponse['success']) {
            $errorMessage = $tokenResponse['message'] ?? "Error: Failed to validate Pinterest access token.";
            $post->update([
                'status' => -1,
                'response' => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "RSS Post Publishing Failed", "Failed to publish Pinterest RSS post. " . $errorMessage, $social_type, $account_image);
            return;
        }

        $access_token = $tokenResponse['access_token'];

        // Prepare post data
        try {
            $postData = PostService::postTypeBody($post);
        } catch (Exception $e) {
            $errorMessage = "Error preparing post data: " . $e->getMessage();
            $post->update([
                'status' => -1,
                'response' => $errorMessage
            ]);
            // Create error notification (cron job)
            $this->errorNotification($post->user_id, "RSS Post Publishing Failed", "Failed to publish Pinterest RSS post. " . $errorMessage, $social_type, $account_image);
            return;
        }

        // Dispatch the publish job
        PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);

        info("RSS Publish: Dispatched Pinterest post ID {$post->id} to board '{$board->name}'.");
    }
}
