<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Tiktok;
use App\Models\User;
use App\Models\TikTokTestCase;
use App\Services\TikTokService;
use App\Services\PostService;
use App\Services\SocialMediaLogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TikTokTestService
{
    private $tiktokService;
    private $postService;
    private $logService;

    public function __construct()
    {
        $this->tiktokService = new TikTokService();
        $this->postService = new PostService();
        $this->logService = new SocialMediaLogService();
    }

    public function runAllTests()
    {
        $tiktok = $this->getFirstConnectedTikTok();

        if (!$tiktok) {
            $this->logService->log('tiktok', 'test', 'No connected TikTok account found for testing', [], 'error');
            return [
                'success' => false,
                'message' => 'No connected TikTok account found for testing'
            ];
        }

        $results = [];

        $results['image'] = $this->testImagePost($tiktok);
        $results['video'] = $this->testVideoPost($tiktok);

        return [
            'success' => true,
            'results' => $results
        ];
    }

    private function getFirstConnectedTikTok()
    {
        $user = User::with('tiktok')->where('id', 4)->orWhere('email', 'abmasood5900@gmail.com')->first();
        if ($user && $user->tiktok->count() > 0) {
            return $user->tiktok->first();
        }
        return Tiktok::where('user_id', 4) //test account
            ->whereNotNull('access_token')
            ->latest()
            ->first();
    }

    public function testImagePost(Tiktok $tiktok)
    {
        $testCase = TikTokTestCase::create([
            'test_type' => 'image',
            'status' => 'pending',
            'tiktok_account_id' => $tiktok->id,
            'test_data' => [
                'tiktok_username' => $tiktok->username,
                'test_timestamp' => now()->toDateTimeString()
            ],
            'ran_at' => now()
        ]);

        try {
            $user = $tiktok->user;
            $tokenResponse = TikTokService::validateToken($tiktok);

            if (!$tokenResponse['success']) {
                $testCase->update([
                    'status' => 'failed',
                    'failure_reason' => 'Token validation failed: ' . ($tokenResponse['message'] ?? 'Unknown error')
                ]);
                return ['success' => false, 'message' => $tokenResponse['message'] ?? 'Token validation failed'];
            }

            $accessToken = $tokenResponse['access_token'];
            $testImageUrl = 'https://picsum.photos/1280/720';

            // Download image to local storage
            $localImageUrl = $this->downloadFileToLocal($testImageUrl, 'image');
            if (!$localImageUrl) {
                $testCase->update([
                    'status' => 'failed',
                    'failure_reason' => 'Failed to download image to local storage'
                ]);
                return ['success' => false, 'message' => 'Failed to download image to local storage'];
            }

            $testPost = Post::create([
                'user_id' => $user->id,
                'account_id' => $tiktok->id,
                'social_type' => 'tiktok',
                'type' => 'photo',
                'source' => 'test',
                'title' => 'Test Image Post - ' . now()->format('Y-m-d H:i:s'),
                'image' => $localImageUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            // Save test_post_id before API call
            $testCase->update([
                'test_post_id' => $testPost->id
            ]);

            $postData = PostService::postTypeBody($testPost);

            $this->tiktokService->photo($testPost->id, $postData, $accessToken);

            // Check if post was updated successfully (status = 1 means success)
            $testPost->refresh();
            if ($testPost->status == 1) {
                $testCase->update([
                    'status' => 'passed',
                    'test_data' => array_merge($testCase->test_data ?? [], [
                        'post_id' => $testPost->post_id ?? null,
                        'response' => json_decode($testPost->response ?? '{}', true)
                    ])
                ]);

                $this->schedulePostDeletion($testPost->id);
                return ['success' => true, 'message' => 'Image post test passed'];
            } else {
                $responseData = json_decode($testPost->response ?? '{}', true);
                $errorMessage = $responseData['error'] ?? $responseData['message'] ?? 'Unknown error during image post publishing';

                $testCase->update([
                    'status' => 'failed',
                    'failure_reason' => $errorMessage
                ]);
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (\Exception $e) {
            // Update test_post_id if post was created but not yet saved
            if (isset($testPost) && $testPost->id) {
                $testCase->update([
                    'test_post_id' => $testPost->id,
                    'status' => 'failed',
                    'failure_reason' => 'Exception: ' . $e->getMessage()
                ]);
            } else {
                $testCase->update([
                    'status' => 'failed',
                    'failure_reason' => 'Exception: ' . $e->getMessage()
                ]);
            }
            $this->logService->log('tiktok', 'test', 'TikTok Image Test Error: ' . $e->getMessage(), [
                'test_case_id' => $testCase->id,
                'test_type' => 'image',
                'exception' => $e->getMessage()
            ], 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testVideoPost(Tiktok $tiktok)
    {
        $testCase = TikTokTestCase::create([
            'test_type' => 'video',
            'status' => 'pending',
            'tiktok_account_id' => $tiktok->id,
            'test_data' => [
                'tiktok_username' => $tiktok->username,
                'test_timestamp' => now()->toDateTimeString()
            ],
            'ran_at' => now()
        ]);

        try {
            $user = $tiktok->user;
            $tokenResponse = TikTokService::validateToken($tiktok);

            if (!$tokenResponse['success']) {
                $testCase->update([
                    'status' => 'failed',
                    'failure_reason' => 'Token validation failed: ' . ($tokenResponse['message'] ?? 'Unknown error')
                ]);
                return ['success' => false, 'message' => $tokenResponse['message'] ?? 'Token validation failed'];
            }

            $accessToken = $tokenResponse['access_token'];

            $testVideoUrl = 'videos/test_sample_video.mp4';

            $testPost = Post::create([
                'user_id' => $user->id,
                'account_id' => $tiktok->id,
                'social_type' => 'tiktok',
                'type' => 'video',
                'source' => 'test',
                'title' => 'Test Video Post - ' . now()->format('Y-m-d H:i:s'),
                'video' => $testVideoUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            // Save test_post_id before API call
            $testCase->update([
                'test_post_id' => $testPost->id
            ]);

            $postData = PostService::postTypeBody($testPost);

            $this->tiktokService->video($testPost->id, $postData, $accessToken);

            // Check if post was updated successfully (status = 1 means success)
            $testPost->refresh();
            if ($testPost->status == 1) {
                $testCase->update([
                    'status' => 'passed',
                    'test_data' => array_merge($testCase->test_data ?? [], [
                        'post_id' => $testPost->post_id ?? null,
                        'response' => json_decode($testPost->response ?? '{}', true)
                    ])
                ]);

                $this->schedulePostDeletion($testPost->id);
                return ['success' => true, 'message' => 'Video post test passed'];
            } else {
                $responseData = json_decode($testPost->response ?? '{}', true);
                $errorMessage = $responseData['error'] ?? $responseData['message'] ?? 'Unknown error during video post publishing';

                $testCase->update([
                    'status' => 'failed',
                    'failure_reason' => $errorMessage
                ]);
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (\Exception $e) {
            // Update test_post_id if post was created but not yet saved
            if (isset($testPost) && $testPost->id) {
                $testCase->update([
                    'test_post_id' => $testPost->id,
                    'status' => 'failed',
                    'failure_reason' => 'Exception: ' . $e->getMessage()
                ]);
            } else {
                $testCase->update([
                    'status' => 'failed',
                    'failure_reason' => 'Exception: ' . $e->getMessage()
                ]);
            }
            $this->logService->log('tiktok', 'test', 'TikTok Video Test Error: ' . $e->getMessage(), [
                'test_case_id' => $testCase->id,
                'test_type' => 'video',
                'exception' => $e->getMessage()
            ], 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function downloadFileToLocal($url, $type = 'image')
    {
        try {
            $fileContents = @file_get_contents($url);
            if ($fileContents === false) {
                $this->logService->log('tiktok', 'test', "Failed to download file from URL: {$url}", [
                    'url' => $url,
                    'type' => $type
                ], 'error');
                return false;
            }

            // Determine file extension
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = $type === 'video' ? 'mp4' : 'jpg';
            }

            // Create local directory
            $localDir = $type === 'video' ? 'uploads/videos' : 'uploads/images';
            $fileName = uniqid('tiktok_test_', true) . '.' . $extension;
            $localPublicPath = $localDir . '/' . $fileName;
            $fullPath = public_path($localPublicPath);
            $directory = dirname($fullPath);

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save file locally
            $bytesWritten = file_put_contents($fullPath, $fileContents);
            if ($bytesWritten === false) {
                $this->logService->log('tiktok', 'test', "Failed to save file to local storage: {$fullPath}", [
                    'path' => $fullPath,
                    'type' => $type
                ], 'error');
                return false;
            }

            // Return the full public URL (TikTok needs a publicly accessible URL)
            return url($localPublicPath);
        } catch (\Exception $e) {
            $this->logService->log('tiktok', 'test', "Exception downloading file: " . $e->getMessage(), [
                'url' => $url,
                'type' => $type,
                'exception' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    private function schedulePostDeletion($postId)
    {
        $post = Post::find($postId);
        if ($post) {
            $post->update([
                'scheduled' => 1,
                'publish_date' => Carbon::now()->addHours(24)->format('Y-m-d H:i:s')
            ]);
        }
    }
}
