<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Jobs\DownloadPhoto;
use Illuminate\Console\Command;

class DownloadPhotoCron extends Command
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
        info("download:photo");
        $pending_photos = Photo::with("post")->pending()->available($this->max_tries)->past()->limit(1)->get();
        info("pending_photos: " . json_encode($pending_photos));
        foreach ($pending_photos as $photo) {
            info('photo:' . json_encode($photo));
            DownloadPhoto::dispatch($photo);
        }
    }
}
