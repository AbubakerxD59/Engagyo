<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Board;
use App\Models\User;
use App\Models\PinterestTestCase;
use App\Services\PinterestService;
use App\Services\PostService;
use App\Services\SocialMediaLogService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PinterestTestService
{
    private $pinterestService;
    private $postService;
    private $logService;

    public function __construct()
    {
        $this->pinterestService = new PinterestService();
        $this->postService = new PostService();
        $this->logService = new SocialMediaLogService();
    }

    public function runAllTests()
    {
        $board = $this->getFirstConnectedBoard();

        if (!$board) {
            $this->logService->log('pinterest', 'test', 'No connected Pinterest board found for testing', [], 'error');
            return [
                'success' => false,
                'message' => 'No connected Pinterest board found for testing'
            ];
        }

        $results = [];

        $results['image'] = $this->testImagePost($board);
        $results['link'] = $this->testLinkPost($board);
        $results['video'] = $this->testVideoPost($board);

        return [
            'success' => true,
            'results' => $results
        ];
    }

    private function getFirstConnectedBoard()
    {
        $user = User::with('boards.pinterest')->where('id', 4)->orWhere('email', 'abmasood5900@gmail.com')->first();
        if ($user) {
            $board = $user->boards->where('name', 'New Board')->first();
            if ($board) {
                return $board;
            }
        }
        return Board::with('pinterest')
            ->where('user_id', 4) //test account
            ->where('status', 1)
            ->latest()
            ->first();
    }

    public function testImagePost(Board $board)
    {
        $testCase = PinterestTestCase::create([
            'test_type' => 'image',
            'status' => 'pending',
            'pinterest_board_id' => $board->id,
            'test_data' => [
                'board_name' => $board->name,
                'test_timestamp' => now()->toDateTimeString()
            ],
            'ran_at' => now()
        ]);

        try {
            $user = $board->user;
            $tokenResponse = PinterestService::validateToken($board);

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
                'account_id' => $board->id,
                'social_type' => 'pinterest',
                'type' => 'photo',
                'source' => 'test',
                'title' => 'Test Image Post - ' . now()->format('Y-m-d H:i:s'),
                'image' => $testImageUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            $postData = $this->postService->postTypeBody($testPost);

            $this->pinterestService->create($testPost->id, $postData, $accessToken);

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
            $this->logService->log('pinterest', 'test', 'Pinterest Image Test Error: ' . $e->getMessage(), [
                'test_case_id' => $testCase->id,
                'test_type' => 'image',
                'exception' => $e->getMessage()
            ], 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testLinkPost(Board $board)
    {
        $testCase = PinterestTestCase::create([
            'test_type' => 'link',
            'status' => 'pending',
            'pinterest_board_id' => $board->id,
            'test_data' => [
                'board_name' => $board->name,
                'test_timestamp' => now()->toDateTimeString()
            ],
            'ran_at' => now()
        ]);

        try {
            $user = $board->user;
            $tokenResponse = PinterestService::validateToken($board);

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
                'account_id' => $board->id,
                'social_type' => 'pinterest',
                'type' => 'link',
                'source' => 'test',
                'title' => 'Test Link Post - ' . now()->format('Y-m-d H:i:s'),
                'url' => $testUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            $postData = $this->postService->postTypeBody($testPost);

            $this->pinterestService->create($testPost->id, $postData, $accessToken);

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
            $this->logService->log('pinterest', 'test', 'Pinterest Link Test Error: ' . $e->getMessage(), [
                'test_case_id' => $testCase->id,
                'test_type' => 'link',
                'exception' => $e->getMessage()
            ], 'error');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function testVideoPost(Board $board)
    {
        $testCase = PinterestTestCase::create([
            'test_type' => 'video',
            'status' => 'pending',
            'pinterest_board_id' => $board->id,
            'test_data' => [
                'board_name' => $board->name,
                'test_timestamp' => now()->toDateTimeString()
            ],
            'ran_at' => now()
        ]);

        try {
            $user = $board->user;
            $tokenResponse = PinterestService::validateToken($board);

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
                'account_id' => $board->id,
                'social_type' => 'pinterest',
                'type' => 'video',
                'source' => 'test',
                'title' => 'Test Video Post - ' . now()->format('Y-m-d H:i:s'),
                'video' => $testVideoUrl,
                'status' => 0,
                'scheduled' => 0
            ]);

            $postData = $this->postService->postTypeBody($testPost);

            $this->pinterestService->video($testPost->id, $postData, $accessToken);

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
            $this->logService->log('pinterest', 'test', 'Pinterest Video Test Error: ' . $e->getMessage(), [
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
