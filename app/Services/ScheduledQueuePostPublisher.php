<?php

namespace App\Services;

use App\Jobs\PublishInstagramPost;
use App\Jobs\PublishThreadsPost;
use App\Jobs\PublishYouTubePost;
use App\Models\Notification;
use App\Models\Post;

class ScheduledQueuePostPublisher
{
    /**
     * Used when an unexpected exception escapes platform publish logic (e.g. from this class).
     */
    public function notifySchedulePublishException(Post $post, string $errorMessage): void
    {
        $platform = ucfirst((string) $post->social_type);
        $this->errorNotification(
            $post->user_id,
            'Scheduled Post Publishing Failed',
            "Failed to publish scheduled {$platform} post. ".$errorMessage,
            $post->social_type,
            $post->account_profile,
            $post->account_name,
            $post->account_username
        );
    }

    public function publishInstagram(Post $post): void
    {
        $social_type = $post->social_type;
        $account_image = $post->account_profile;
        $ig = $post->instagramAccount;

        if (! $ig) {
            $errorMessage = 'Error: Instagram account not found.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Instagram post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        if (! $ig->validToken()) {
            $errorMessage = 'Error: Instagram access token expired. Please reconnect Instagram.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Instagram post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        try {
            PublishInstagramPost::dispatch($post->id);
        } catch (\Exception $e) {
            $errorMessage = 'Error preparing post: '.$e->getMessage();
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Instagram post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
        }
    }

    public function publishThreads(Post $post): void
    {
        $social_type = $post->social_type;
        $account_image = $post->account_profile;
        $thread = $post->thread;

        if (! $thread) {
            $errorMessage = 'Error: Threads account not found.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Threads post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        if (! $thread->validToken()) {
            $errorMessage = 'Error: Threads access token expired. Please reconnect Threads.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Threads post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        try {
            PublishThreadsPost::dispatch($post->id);
        } catch (\Exception $e) {
            $errorMessage = 'Error preparing post: '.$e->getMessage();
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled Threads post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
        }
    }

    public function publishLinkedIn(Post $post): void
    {
        $social_type = $post->social_type;
        $account_image = $post->account_profile;
        $linkedin = $post->linkedin;

        if (! $linkedin) {
            $errorMessage = 'Error: LinkedIn account not found.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled LinkedIn post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        if (! $linkedin->validToken()) {
            $errorMessage = 'Error: LinkedIn access token expired. Please reconnect LinkedIn.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled LinkedIn post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        try {
            $linkedInPublishService = new LinkedInPublishService;
            $linkedInPublishService->publishQueuedPost($post->id);
        } catch (\Exception $e) {
            $errorMessage = 'Error preparing post: '.$e->getMessage();
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled LinkedIn post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
        }
    }

    public function publishYouTube(Post $post): void
    {
        $social_type = $post->social_type;
        $account_image = $post->account_profile;
        $youtube = $post->youtube;

        if (! $youtube) {
            $errorMessage = 'Error: YouTube account not found.';
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled YouTube post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        $tokenResponse = YouTubeService::validateToken($youtube);
        if (empty($tokenResponse['success'])) {
            $errorMessage = 'Error: '.($tokenResponse['message'] ?? 'YouTube access token expired. Please reconnect YouTube.');
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled YouTube post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);

            return;
        }

        try {
            PublishYouTubePost::dispatch($post->id);
        } catch (\Exception $e) {
            $errorMessage = 'Error preparing post: '.$e->getMessage();
            $post->update([
                'status' => -1,
                'response' => $errorMessage,
            ]);
            $this->errorNotification($post->user_id, 'Scheduled Post Publishing Failed', 'Failed to publish scheduled YouTube post. '.$errorMessage, $social_type, $account_image, $post->account_name, $post->account_username);
        }
    }

    private function errorNotification($userId, $title, $message, $social_type, $account_image, $account_name = null, $account_username = null): void
    {
        $body = ['type' => 'error', 'message' => $message, 'social_type' => $social_type, 'account_image' => $account_image];
        if ($account_name !== null) {
            $body['account_name'] = $account_name;
        }
        if ($account_username !== null) {
            $body['account_username'] = $account_username;
        }
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }
}
