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
        Log::info("cronjob running" . now());
        $domains = $domain->get();
        foreach ($domains as $key => $value) {
            $user = $value->user()->where("status", 1)->first();
            if ($user) {
                $type = $value->type;
                if ($type == 'pinterest') {
                    $sub_account = $value->board()->first();
                    $account = $sub_account->pinterest()->first();
                    $account_id = $sub_account->board_id;
                    $mode = 1;
                } elseif ($type == 'facebook') {
                    $sub_account = $value->page()->first();
                    $account = $sub_account->facebook()->first();
                    $account_id = $sub_account->page_id;
                    $mode = 0;
                }
                if ($sub_account && $account) {
                    $sub_account->update([
                        "last_fetch" => date("Y-m-d H:i A")
                    ]);
                    $data = [
                        "url" => !empty($value->category) ? $value->name . $value->category : $value->name,
                        "category" => $value->category,
                        "domain_id" => $value->id,
                        "user_id" => $user->id,
                        "account_id" => $account_id,
                        "time" => $value->time,
                        "type" => $type,
                        "mode" => $mode,
                        "exist" => false
                    ];
                    FetchPost::dispatch($data);
                }
            }
        }
    }
}
