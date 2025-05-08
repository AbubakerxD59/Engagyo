<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Jobs\FetchPost;
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
        clearLogFile();
        $domains = $domain->get();
        foreach ($domains as $key => $value) {
            $user = $value->user()->where("status", 1)->first();
            Log::info("domain: " . $value);
            Log::info("user: " . $user);
            if ($user) {
                $type = $value->type;
                Log::info("type: " . $type);
                if ($type == 'pinterest') {
                    $sub_account = $value->board()->first();
                    $account = $sub_account->pinterest()->first();
                    $mode = 1;
                } elseif ($type == 'facebook') {
                    $sub_account = $value->page()->first();
                    $account = $sub_account->facebook()->first();
                    $mode = 0;
                }
                Log::info("sub_account: " . $sub_account);
                Log::info("account: " . $account);
                if ($sub_account && $account) {
                    $data = [
                        "url" => !empty($value->category) ? $value->name . $value->category : $value->name,
                        "category" => $value->category,
                        "domain_id" => $value->id,
                        "user_id" => $user->id,
                        "account_id" => $account->id,
                        "time" => $value->time,
                        "type" => $type,
                        "mode" => $mode,
                        "exist" => false
                    ];
                    Log::info("data: " . $data);
                    FetchPost::dispatch($data);
                }
            }
        }
    }
}
