<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Page;
use App\Models\User;
use App\Models\FacebookTestCase;
use App\Services\FacebookService;
use App\Services\PostService;
use App\Services\SocialMediaLogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacebookTestService
{
    private $facebookService;
    private $postService;
    private $logService;

    public function __construct()
    {
        $this->facebookService = new FacebookService();
        $this->postService = new PostService();
        $this->logService = new SocialMediaLogService();
    }

    public function runAllTests()
    {
        $page = $this->getFirstConnectedPage();

        if (!$page) {
            $this->logService->log('facebook', 'test', 'No connected Facebook page found for testing', [], 'error');
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
        $user = User::with('pages.facebook')->where('id', 4)->orWhere('email', 'abmasood5900@gmail.com')->first();
        if ($user) {
            $page = $user->pages()->latest()->first();
            return $page;
        }
        return Page::with('facebook')
            ->where('user_id', 4) //test account
            ->latest()
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
                'title' => 'Test Image Post - Automated Test',
                'image' => $testImageUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            // Save test_post_id before API call
            $testCase->update([
                'test_post_id' => $testPost->id
            ]);

            $postData = PostService::postTypeBody($testPost);

            $response = $this->facebookService->photo($testPost->id, $accessToken, $postData);

            if ($response['success']) {
                $testPost->refresh();
                $testCase->update([
                    'status' => 'passed',
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
                    'failure_reason' => $response['message'] ?? 'Unknown error during image post publishing'
                ]);
                return ['success' => false, 'message' => $response['message'] ?? 'Image post test failed'];
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
            $this->logService->log('facebook', 'test', 'Facebook Image Test Error: ' . $e->getMessage(), [
                'test_case_id' => $testCase->id,
                'test_type' => 'image',
                'exception' => $e->getMessage()
            ], 'error');
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

            // Save test_post_id before API call
            $testCase->update([
                'test_post_id' => $testPost->id
            ]);

            $postData = PostService::postTypeBody($testPost);

            $response = $this->facebookService->contentOnly($testPost->id, $accessToken, $postData);

            if ($response['success']) {
                $testPost->refresh();
                $testCase->update([
                    'status' => 'passed',
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
                    'failure_reason' => $response['message'] ?? 'Unknown error during quote post publishing'
                ]);
                return ['success' => false, 'message' => $response['message'] ?? 'Quote post test failed'];
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
            $this->logService->log('facebook', 'test', 'Facebook Quote Test Error: ' . $e->getMessage(), [
                'test_case_id' => $testCase->id,
                'test_type' => 'quote',
                'exception' => $e->getMessage()
            ], 'error');
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
                'title' => 'Test Link Post - Automated Test',
                'url' => $testUrl,
                'description' => 'This is a test link post for automated testing',
                'status' => 0,
                'scheduled' => 0
            ]);

            // Save test_post_id before API call
            $testCase->update([
                'test_post_id' => $testPost->id
            ]);

            $postData = PostService::postTypeBody($testPost);

            $response = $this->facebookService->createLink($testPost->id, $accessToken, $postData);

            if ($response['success']) {
                $testPost->refresh();
                $testCase->update([
                    'status' => 'passed',
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
                    'failure_reason' => $response['message'] ?? 'Unknown error during link post publishing'
                ]);
                return ['success' => false, 'message' => $response['message'] ?? 'Link post test failed'];
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
            $this->logService->log('facebook', 'test', 'Facebook Link Test Error: ' . $e->getMessage(), [
                'test_case_id' => $testCase->id,
                'test_type' => 'link',
                'exception' => $e->getMessage()
            ], 'error');
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

            $testVideoUrl = 'videos/test_sample_video.mp4';

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

            // Save test_post_id before API call
            $testCase->update([
                'test_post_id' => $testPost->id
            ]);

            $postData = PostService::postTypeBody($testPost);

            $response = $this->facebookService->video($testPost->id, $accessToken, $postData);

            if ($response['success']) {
                $testPost->refresh();
                $testCase->update([
                    'status' => 'passed',
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
            $this->logService->log('facebook', 'test', 'Facebook Video Test Error: ' . $e->getMessage(), [
                'test_case_id' => $testCase->id,
                'test_type' => 'video',
                'exception' => $e->getMessage()
            ], 'error');
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
