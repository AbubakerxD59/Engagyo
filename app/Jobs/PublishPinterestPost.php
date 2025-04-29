<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use App\Services\PinterestService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PublishPinterestPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;
    private $data;
    private $post;
    private $access_token;
    /**
     * Create a new job instance.
     */
    public function __construct($id, $data, $access_token)
    {
        $this->id = $id;
        $this->data = $data;
        $this->post = new Post();
        $this->access_token = $access_token;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pinterest = new PinterestService();
        $publish = $pinterest->create($this->access_token, $this->data);
        $post = $this->post->find($this->id);
        if (isset($publish['id'])) {
            $post->update([
                "post_id" => $publish["id"],
                "status" => 1,
                "response" => "Published Successfully!"
            ]);
        } else {
            $post->update([
                "status" => -1,
                "response" => json_encode($publish)
            ]);
        }
    }
}
