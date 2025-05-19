<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RefreshPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $post;
    private $data;
    private $user_id;
    /**
     * Create a new job instance.
     */
    public function __construct($data, $user_id)
    {
        $this->post = new Post();
        $this->data = $data;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $post = new Post();
        $type = $this->data["type"];
        $account = $this->data["account_id"];
        $domain = $this->data["domain_id"];
        $currentPublish = $this->data["publish_date"];
        $nextPosts = $post->userSearch($this->user_id)->accounts($account)->domainSearch($domain);
        if ($type == 'pinterest') {
            $nextPosts = $nextPosts->pinterest();
        } elseif ($type == 'facebook') {
            $nextPosts = $nextPosts->facebook();
        }
        foreach ($nextPosts as $key => $post) {
            if ($key > 0) {
                $currentPublish = date("Y-m-d H:i", strtotime($currentPublish . '+1 days'));
            }
            $post->update([
                "publish_date" => $currentPublish
            ]);
        }
    }
}
