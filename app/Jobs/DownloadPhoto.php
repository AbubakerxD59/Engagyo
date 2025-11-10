<?php

namespace App\Jobs;

use App\Services\DownloadPhotoService;
use Exception;
use App\Models\Photo;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DownloadPhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 1;
    private $data;
    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = new DownloadPhotoService();
        $photo = Photo::with("post")->findOrFail($this->data['id']);
        info('here');
        $response = $service->fetch($this->data);
        info('response: ' . json_encode($response));
        if ($response['success']) {
            $status = empty($response['data']) ? "pending" : "fetched";
            if ($status == "fetched") {
                $photo->post->update([
                    "image" => $response['data']
                ]);
            }
            $photo->update([
                "status" => $status,
                "tries" => $photo->tries + 1,
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
