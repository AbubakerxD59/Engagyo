<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Jobs\DownloadPhoto;
use Illuminate\Console\Command;

class DownloadPhotoCton extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:photo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cronjob to download photos';

    /**
     * Execute the console command.
     */
    public $max_tries = 3;
    public function handle()
    {
        $pending_photos = Photo::pending()->available($this->max_tries)->get();
        foreach ($pending_photos as $photo) {
            DownloadPhoto::dispatch($photo);
            sleep(rand(120, 180));
        }
    }
}
