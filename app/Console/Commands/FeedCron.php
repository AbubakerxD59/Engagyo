<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FeedCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rssfeed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to fetch RSS Feed for each domain.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
