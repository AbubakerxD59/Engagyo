<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\TikTokService;
use Illuminate\Console\Command;

class TikTokFetchPublishStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:fetch-publish-status {--limit=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll TikTok publish/status/fetch and store current status';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $service = new TikTokService();

        $posts = Post::with('tiktok')
            ->tiktok()
            ->whereNotNull('post_id')
            ->whereIn('status', [2, 1])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        foreach ($posts as $post) {
            if (!$post->tiktok) {
                continue;
            }

            $tokenResponse = TikTokService::validateToken($post->tiktok);
            if (empty($tokenResponse['success']) || empty($tokenResponse['access_token'])) {
                continue;
            }

            $statusResponse = $service->getPostStatus((string) $post->post_id, $tokenResponse['access_token']);
            if (empty($statusResponse) || empty($statusResponse['success'])) {
                continue;
            }

            $statusData = $statusResponse['data'] ?? [];
            $statusText = strtoupper((string) ($statusData['status'] ?? $statusData['publish_status'] ?? $statusData['state'] ?? ''));

            $rawResponse = $post->response;
            $response = is_array($rawResponse) ? $rawResponse : (json_decode((string) $rawResponse, true) ?: []);
            $response['publish_status_data'] = $statusData;
            $response['publish_status_checked_at'] = now()->toDateTimeString();
            if ($statusText !== '') {
                $response['processing_status'] = $statusText;
            }

            $isFailed = str_contains($statusText, 'FAIL') || str_contains($statusText, 'ERROR') || str_contains($statusText, 'REJECT');
            $isCompleted = str_contains($statusText, 'COMPLETE') || str_contains($statusText, 'PUBLISHED') || str_contains($statusText, 'SUCCESS');

            if ($isFailed) {
                $post->update([
                    'status' => -1,
                    'response' => json_encode(array_merge($response, [
                        'success' => false,
                        'error' => $statusData['fail_reason'] ?? $statusData['message'] ?? 'TikTok publish failed.',
                    ])),
                ]);
                continue;
            }

            if ($isCompleted) {
                $response['success'] = true;
                $response['message'] = 'TikTok reports your post is published.';
            } else {
                $response['message'] = 'Post submitted to TikTok and still processing. It may take a few minutes to appear on your profile.';
            }

            $post->update([
                'response' => json_encode($response),
            ]);
        }

        return self::SUCCESS;
    }
}

