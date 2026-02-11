<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\Notification;
use App\Services\FeedService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FeedCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rss:feed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to fetch RSS Feed for each domain.';

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
    public function handle(Domain $domain)
    {
        $domains = $domain->with("user", "board.pinterest", "page.facebook")->get();
        echo "Total domains: " . $domains->count() . "\n";
        foreach ($domains as $key => $value) {
            $type = $value->type;
            $times = $value->time;
            $user = $value->user;

            if ($user) {
                echo "User: " . $user->id . "\n";
                $account_image = null;
                if ($type == 'pinterest') {
                    echo "Pinterest\n";
                    $sub_account = $value->board;
                    $account = $sub_account ? $sub_account->pinterest : null;
                    $account_id = $sub_account ? $sub_account->id : null;
                    $account_image = $account ? $account->profile_image : null;
                } elseif ($type == 'facebook') {
                    echo "Facebook\n";
                    $sub_account = $value->page;
                    $account = $sub_account ? $sub_account->facebook : null;
                    $account_id = $sub_account ? $sub_account->id : null;
                    $account_image = $account ? ($account->page?->profile_image ?? $account->profile_image) : null;
                }

                if ($sub_account && $account) {
                    echo "Sub account: " . $sub_account->id . "\n";
                    // Check if RSS automation is paused for this page or board
                    if ($sub_account->rss_paused) {
                        echo "RSS paused\n";
                        $accountName = $type == 'pinterest' ? $sub_account->name : $sub_account->name;
                        Log::info("RSS Feed: Skipping domain {$value->id} - RSS automation is paused for {$type} account '{$accountName}'.");
                        continue;
                    }

                    $sub_account->update([
                        "last_fetch" => date("Y-m-d h:i A")
                    ]);
                    echo "Last fetch updated: " . date("Y-m-d H:i A") . "\n";
                    // Parse domain name to extract protocol, host, and category
                    $domainName = $value->name;
                    $parsedUrl = parse_url($domainName);

                    // Extract protocol (default to https if not specified)
                    $protocol = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'https';

                    // Extract host
                    if (isset($parsedUrl['host'])) {
                        $urlDomain = $parsedUrl['host'];
                        $category = isset($parsedUrl['path']) &&
                            !str_contains($parsedUrl['path'], 'rss') &&
                            !str_contains($parsedUrl['path'], 'feed')
                            ? $parsedUrl['path']
                            : ($value->category ?? null);
                    } else {
                        // If no host, use path as domain (for backward compatibility)
                        $urlDomain = ltrim($parsedUrl['path'] ?? $domainName, '/');
                        $category = $value->category ?? null;
                    }

                    // Always set exist to false to fetch posts from Feed
                    $exist = false;

                    // Determine the link URL (always use domain + category if available)
                    $link = !empty($category) ? $urlDomain . $category : $urlDomain;

                    $data = [
                        "protocol" => $protocol,
                        "url" => $link,
                        "category" => $category,
                        "domain_id" => $value->id,
                        "user_id" => $user->id,
                        "account_id" => $account_id,
                        "type" => "link",
                        "social_type" => $type,
                        "source" => "rss",
                        "time" => $times,
                        "exist" => false // Always set to false
                    ];

                    // Always use FeedService to fetch posts from Feed
                    $feedService = new FeedService($data);
                    $feedUrl = $feedService->fetch();
                    if (!$feedUrl['success']) {
                        $errorMessage = $feedUrl['message'] ?? 'Unknown error';
                        Log::warning("Failed to fetch feed for domain {$value->id}: " . $errorMessage);
                        // Create error notification (cron job)
                        $platform = ucfirst($type);
                        $this->errorNotification($user->id, "RSS Feed Fetch Failed", "Failed to fetch {$platform} RSS feed for domain '{$urlDomain}'. " . $errorMessage, $type, $account_image);
                    } else {
                        // Create success notification (cron job)
                        $platform = ucfirst($type);
                        $postCount = isset($feedUrl['items']) ? count($feedUrl['items']) : 0;
                        if ($postCount > 0) {
                            $this->successNotification($user->id, "RSS Feed Fetched", "Successfully fetched {$postCount} new post(s) from {$platform} RSS feed for domain '{$urlDomain}'.", $type, $account_image);
                        }
                    }
                }
            }
        }
    }
}
