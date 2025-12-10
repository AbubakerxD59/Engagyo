<?php

namespace App\Console\Commands;

use App\Models\Domain;
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
     * Execute the console command.
     */
    public function handle(Domain $domain)
    {
        $domains = $domain->with("user", "board.pinterest", "page.facebook")->get();
        foreach ($domains as $key => $value) {
            $type = $value->type;
            $times = $value->time;
            $user = $value->user;

            if ($user) {
                if ($type == 'pinterest') {
                    $sub_account = $value->board;
                    $account = $sub_account ? $sub_account->pinterest : null;
                    $account_id = $sub_account->id;
                } elseif ($type == 'facebook') {
                    $sub_account = $value->page;
                    $account = $sub_account ? $sub_account->facebook : null;
                    $account_id = $sub_account->id;
                }

                if ($sub_account && $account) {
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
                    
                    // Update last_fetch on the account (board/page)
                    $sub_account->update([
                        "last_fetch" => date("Y-m-d H:i A")
                    ]);
                    
                    foreach ($times as $time) {
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
                            "time" => $time,
                            "exist" => false // Always set to false
                        ];
                        
                        // Always use FeedService to fetch posts from Feed
                        $feedService = new FeedService($data);
                        $feedUrl = $feedService->fetch();
                        if (!$feedUrl['success']) {
                            Log::warning("Failed to fetch feed for domain {$value->id}: " . ($feedUrl['message'] ?? 'Unknown error'));
                        }
                    }
                }
            }
        }
    }
}
