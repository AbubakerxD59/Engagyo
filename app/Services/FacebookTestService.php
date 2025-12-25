<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Page;
use App\Models\User;
use App\Models\FacebookTestCase;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacebookTestService
{
    private $facebookService;

    public function __construct()
    {
        $this->facebookService = new FacebookService();
    }

    public function runAllTests()
    {
        $page = $this->getFirstConnectedPage();

        if (!$page) {
            Log::error('Facebook Test: No connected Facebook page found');
            return [
                'success' => false,
                'message' => 'No connected Facebook page found for testing'
            ];
        }

        $results = [];

        $results['image'] = $this->testImagePost($page);
        $results['quote'] = $this->testQuotePost($page);
        $results['link'] = $this->testLinkPost($page);
        $results['video'] = $this->testVideoPost($page);

        return [
            'success' => true,
            'results' => $results
        ];
    }

    private function getFirstConnectedPage()
    {
        return Page::with('facebook')
            ->whereNotNull('access_token')
            ->where('user_id', 4) //test account
            ->where('fb_id', '297172726820241') // test page
            ->where('status', 1)
            ->first();
    }

    public function testImagePost(Page $page)
    {
        $testCase = FacebookTestCase::create([
            'test_type' => 'image',
            'status' => 'pending',
            'facebook_page_id' => $page->id,
            'test_data' => [
                'page_name' => $page->name,
                'test_timestamp' => now()->toDateTimeString()
            ],
            'ran_at' => now()
        ]);

        try {
            $user = $page->user;
            $tokenResponse = FacebookService::validateToken($page);

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
                'account_id' => $page->id,
                'social_type' => 'facebook',
                'type' => 'photo',
                'source' => 'test',
                'title' => 'Test Image Post - ' . now()->format('Y-m-d H:i:s'),
                'image' => $testImageUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            $postData = [
                'url' => $testImageUrl,
                'message' => 'Test Image Post - Automated Test'
            ];

            $response = $this->facebookService->photo($testPost->id, $accessToken, $postData);

            if ($response['success']) {
                $testPost->refresh();
                $testCase->update([
                    'status' => 'passed',
                    'test_post_id' => $testPost->id,
                    'test_data' => array_merge($testCase->test_data ?? [], [
                        'post_id' => $testPost->post_id,
                        'response' => $response
                    ])
                ]);

                $this->schedulePostDeletion($testPost->id);
                return ['success' => true, 'message' => 'Image post test passed'];
            } else {
                $testCase->update([
                    'status' => 'failed',
                    'test_post_id' => $testPost->id,
                    'failure_reason' => $response['message'] ?? 'Unknown error during image post publishing'
                ]);
                return ['success' => false, 'message' => $response['message'] ?? 'Image post test failed'];
            }
        } catch (\Exception $e) {
            $testCase->update([
                'status' => 'failed',
                'failure_reason' => 'Exception: ' . $e->getMessage()
            ]);
            Log::error('Facebook Image Test Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testQuotePost(Page $page)
    {
        $testCase = FacebookTestCase::create([
            'test_type' => 'quote',
            'status' => 'pending',
            'facebook_page_id' => $page->id,
            'test_data' => [
                'page_name' => $page->name,
                'test_timestamp' => now()->toDateTimeString()
            ],
            'ran_at' => now()
        ]);

        try {
            $user = $page->user;
            $tokenResponse = FacebookService::validateToken($page);

            if (!$tokenResponse['success']) {
                $testCase->update([
                    'status' => 'failed',
                    'failure_reason' => 'Token validation failed: ' . ($tokenResponse['message'] ?? 'Unknown error')
                ]);
                return ['success' => false, 'message' => $tokenResponse['message'] ?? 'Token validation failed'];
            }

            $accessToken = $tokenResponse['access_token'];
            $quoteText = 'This is a test quote post. "The only way to do great work is to love what you do." - Steve Jobs';

            $testPost = Post::create([
                'user_id' => $user->id,
                'account_id' => $page->id,
                'social_type' => 'facebook',
                'type' => 'content_only',
                'source' => 'test',
                'title' => $quoteText,
                'status' => 0,
                'scheduled' => 0
            ]);

            $postData = [
                'message' => $quoteText
            ];

            $response = $this->facebookService->contentOnly($testPost->id, $accessToken, $postData);

            if ($response['success']) {
                $testPost->refresh();
                $testCase->update([
                    'status' => 'passed',
                    'test_post_id' => $testPost->id,
                    'test_data' => array_merge($testCase->test_data ?? [], [
                        'post_id' => $testPost->post_id,
                        'response' => $response
                    ])
                ]);

                $this->schedulePostDeletion($testPost->id);
                return ['success' => true, 'message' => 'Quote post test passed'];
            } else {
                $testCase->update([
                    'status' => 'failed',
                    'test_post_id' => $testPost->id,
                    'failure_reason' => $response['message'] ?? 'Unknown error during quote post publishing'
                ]);
                return ['success' => false, 'message' => $response['message'] ?? 'Quote post test failed'];
            }
        } catch (\Exception $e) {
            $testCase->update([
                'status' => 'failed',
                'failure_reason' => 'Exception: ' . $e->getMessage()
            ]);
            Log::error('Facebook Quote Test Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testLinkPost(Page $page)
    {
        $testCase = FacebookTestCase::create([
            'test_type' => 'link',
            'status' => 'pending',
            'facebook_page_id' => $page->id,
            'test_data' => [
                'page_name' => $page->name,
                'test_timestamp' => now()->toDateTimeString()
            ],
            'ran_at' => now()
        ]);

        try {
            $user = $page->user;
            $tokenResponse = FacebookService::validateToken($page);

            if (!$tokenResponse['success']) {
                $testCase->update([
                    'status' => 'failed',
                    'failure_reason' => 'Token validation failed: ' . ($tokenResponse['message'] ?? 'Unknown error')
                ]);
                return ['success' => false, 'message' => $tokenResponse['message'] ?? 'Token validation failed'];
            }

            $accessToken = $tokenResponse['access_token'];
            $testUrl = 'https://www.example.com/test-link';

            $testPost = Post::create([
                'user_id' => $user->id,
                'account_id' => $page->id,
                'social_type' => 'facebook',
                'type' => 'link',
                'source' => 'test',
                'title' => 'Test Link Post',
                'url' => $testUrl,
                'description' => 'This is a test link post for automated testing',
                'status' => 0,
                'scheduled' => 0
            ]);

            $postData = [
                'link' => $testUrl,
                'message' => 'Test Link Post - Automated Test'
            ];

            $response = $this->facebookService->createLink($testPost->id, $accessToken, $postData);

            if ($response['success']) {
                $testPost->refresh();
                $testCase->update([
                    'status' => 'passed',
                    'test_post_id' => $testPost->id,
                    'test_data' => array_merge($testCase->test_data ?? [], [
                        'post_id' => $testPost->post_id,
                        'response' => $response
                    ])
                ]);

                $this->schedulePostDeletion($testPost->id);
                return ['success' => true, 'message' => 'Link post test passed'];
            } else {
                $testCase->update([
                    'status' => 'failed',
                    'test_post_id' => $testPost->id,
                    'failure_reason' => $response['message'] ?? 'Unknown error during link post publishing'
                ]);
                return ['success' => false, 'message' => $response['message'] ?? 'Link post test failed'];
            }
        } catch (\Exception $e) {
            $testCase->update([
                'status' => 'failed',
                'failure_reason' => 'Exception: ' . $e->getMessage()
            ]);
            Log::error('Facebook Link Test Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testVideoPost(Page $page)
    {
        $testCase = FacebookTestCase::create([
            'test_type' => 'video',
            'status' => 'pending',
            'facebook_page_id' => $page->id,
            'test_data' => [
                'page_name' => $page->name,
                'test_timestamp' => now()->toDateTimeString()
            ],
            'ran_at' => now()
        ]);

        try {
            $user = $page->user;
            $tokenResponse = FacebookService::validateToken($page);

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
                'account_id' => $page->id,
                'social_type' => 'facebook',
                'type' => 'video',
                'source' => 'test',
                'title' => 'Test Video Post - Automated Test',
                'video' => $testVideoUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            $postData = [
                'file_url' => $testVideoUrl,
                'description' => 'Test Video Post - Automated Test'
            ];

            $response = $this->facebookService->video($testPost->id, $accessToken, $postData);

            if ($response['success']) {
                $testPost->refresh();
                $testCase->update([
                    'status' => 'passed',
                    'test_post_id' => $testPost->id,
                    'test_data' => array_merge($testCase->test_data ?? [], [
                        'post_id' => $testPost->post_id,
                        'response' => $response
                    ])
                ]);

                $this->schedulePostDeletion($testPost->id);
                return ['success' => true, 'message' => 'Video post test passed'];
            } else {
                $testCase->update([
                    'status' => 'failed',
                    'test_post_id' => $testPost->id,
                    'failure_reason' => $response['message'] ?? 'Unknown error during video post publishing'
                ]);
                return ['success' => false, 'message' => $response['message'] ?? 'Video post test failed'];
            }
        } catch (\Exception $e) {
            $testCase->update([
                'status' => 'failed',
                'failure_reason' => 'Exception: ' . $e->getMessage()
            ]);
            Log::error('Facebook Video Test Error: ' . $e->getMessage());
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
