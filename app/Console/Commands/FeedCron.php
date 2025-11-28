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
                    $mode = 1;
                } elseif ($type == 'facebook') {
                    $sub_account = $value->page;
                    $account = $sub_account ? $sub_account->facebook : null;
                    $account_id = $sub_account->id;
                    $mode = 0;
                }
                if ($sub_account && $account) {
                    $sub_account->update([
                        "last_fetch" => date("Y-m-d H:i")
                    ]);
                    foreach ($times as $time) {
                        $data = [
                            "url" => !empty($value->category) ? $value->name . $value->category : $value->name,
                            "category" => $value->category,
                            "domain_id" => $value->id,
                            "user_id" => $user->id,
                            "account_id" => $account_id,
                            "time" => $time,
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
}
