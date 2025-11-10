<?php

namespace App\Jobs;

use App\Services\DownloadPhotoService;
use Exception;
use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DownloadPhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $max_tries = 3;
    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = new DownloadPhotoService();
        $pending_photos = Photo::pending()->available($this->max_tries)->get();
        foreach ($pending_photos as $photo) {
            $response = $service->fetch($photo);
            if ($response['success']) {
                $photo->update([
                    "status" => "fetched",
                    "response" => $response['data']
                ]);
            } else {
                $photo->update([
                    "status" => "failed",
                    "response" => $response['message']
                ]);
            }
        }
    }
}
