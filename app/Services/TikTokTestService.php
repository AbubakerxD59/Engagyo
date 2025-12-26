<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Tiktok;
use App\Models\User;
use App\Models\TikTokTestCase;
use App\Services\TikTokService;
use App\Services\PostService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TikTokTestService
{
    private $tiktokService;
    private $postService;

    public function __construct()
    {
        $this->tiktokService = new TikTokService();
        $this->postService = new PostService();
    }

    public function runAllTests()
    {
        $tiktok = $this->getFirstConnectedTikTok();

        if (!$tiktok) {
            Log::error('TikTok Test: No connected TikTok account found');
            return [
                'success' => false,
                'message' => 'No connected TikTok account found for testing'
            ];
        }

        $results = [];

        $results['image'] = $this->testImagePost($tiktok);
        $results['link'] = $this->testLinkPost($tiktok);
        $results['video'] = $this->testVideoPost($tiktok);

        return [
            'success' => true,
            'results' => $results
        ];
    }

    private function getFirstConnectedTikTok()
    {
        $user = User::with('tiktoks')->where('id', 4)->orWhere('email', 'abmasood5900@gmail.com')->first();
        if ($user && $user->tiktoks->count() > 0) {
            return $user->tiktoks->first();
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

            $testPost = Post::create([
                'user_id' => $user->id,
                'account_id' => $tiktok->id,
                'social_type' => 'tiktok',
                'type' => 'photo',
                'source' => 'test',
                'title' => 'Test Image Post - ' . now()->format('Y-m-d H:i:s'),
                'image' => $testImageUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            $postData = [
                'url' => $testImageUrl,
                'title' => 'Test Image Post - Automated Test'
            ];

            $this->tiktokService->photo($testPost->id, $postData, $accessToken);

            // Check if post was updated successfully (status = 1 means success)
            $testPost->refresh();
            if ($testPost->status == 1) {
                $testCase->update([
                    'status' => 'passed',
                    'test_post_id' => $testPost->id,
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
                    'test_post_id' => $testPost->id,
                    'failure_reason' => $errorMessage
                ]);
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (\Exception $e) {
            $testCase->update([
                'status' => 'failed',
                'failure_reason' => 'Exception: ' . $e->getMessage()
            ]);
            Log::error('TikTok Image Test Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testLinkPost(Tiktok $tiktok)
    {
        $testCase = TikTokTestCase::create([
            'test_type' => 'link',
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
            $testUrl = 'https://www.example.com/test-link';
            $testImageUrl = 'https://picsum.photos/1280/720';

            $testPost = Post::create([
                'user_id' => $user->id,
                'account_id' => $tiktok->id,
                'social_type' => 'tiktok',
                'type' => 'link',
                'source' => 'test',
                'title' => 'Test Link Post - ' . now()->format('Y-m-d H:i:s'),
                'url' => $testUrl,
                'image' => $testImageUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            $postData = [
                'link' => $testUrl,
                'url' => $testImageUrl,
                'title' => 'Test Link Post - Automated Test'
            ];

            $this->tiktokService->link($testPost->id, $postData, $accessToken);

            // Check if post was updated successfully (status = 1 means success)
            $testPost->refresh();
            if ($testPost->status == 1) {
                $testCase->update([
                    'status' => 'passed',
                    'test_post_id' => $testPost->id,
                    'test_data' => array_merge($testCase->test_data ?? [], [
                        'post_id' => $testPost->post_id ?? null,
                        'response' => json_decode($testPost->response ?? '{}', true)
                    ])
                ]);

                $this->schedulePostDeletion($testPost->id);
                return ['success' => true, 'message' => 'Link post test passed'];
            } else {
                $responseData = json_decode($testPost->response ?? '{}', true);
                $errorMessage = $responseData['error'] ?? $responseData['message'] ?? 'Unknown error during link post publishing';
                
                $testCase->update([
                    'status' => 'failed',
                    'test_post_id' => $testPost->id,
                    'failure_reason' => $errorMessage
                ]);
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (\Exception $e) {
            $testCase->update([
                'status' => 'failed',
                'failure_reason' => 'Exception: ' . $e->getMessage()
            ]);
            Log::error('TikTok Link Test Error: ' . $e->getMessage());
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

            $testVideoUrl = 'https://sample-videos.com/video123/mp4/720/big_buck_bunny_720p_1mb.mp4';

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

            $postData = [
                'file_url' => $testVideoUrl,
                'title' => 'Test Video Post - Automated Test'
            ];

            $this->tiktokService->video($testPost->id, $postData, $accessToken);

            // Check if post was updated successfully (status = 1 means success)
            $testPost->refresh();
            if ($testPost->status == 1) {
                $testCase->update([
                    'status' => 'passed',
                    'test_post_id' => $testPost->id,
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
                    'test_post_id' => $testPost->id,
                    'failure_reason' => $errorMessage
                ]);
                return ['success' => false, 'message' => $errorMessage];
            }
        } catch (\Exception $e) {
            $testCase->update([
                'status' => 'failed',
                'failure_reason' => 'Exception: ' . $e->getMessage()
            ]);
            Log::error('TikTok Video Test Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
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

