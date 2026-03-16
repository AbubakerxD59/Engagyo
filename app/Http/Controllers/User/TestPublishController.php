<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\FacebookService;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestPublishController extends Controller
{
    /**
     * Test route for publishing a Facebook post. Displays each step (payload, response, etc.)
     * Route: GET /panel/test/publish-facebook/{id}
     */
    public function publishFacebook(Request $request, $id)
    {
        $steps = [];
        $stepId = 1;

        // Step 1: Load post from database
        $post = Post::withoutGlobalScopes()
            ->with('page.facebook')
            ->where('id', $id)
            ->first();

        if (!$post) {
            return view('user.test-publish-facebook', [
                'error' => 'Post not found.',
                'steps' => [],
                'postId' => $id,
            ]);
        }

        // Verify user owns the post
        if ($post->user_id !== Auth::guard('user')->id()) {
            return view('user.test-publish-facebook', [
                'error' => 'Unauthorized. You do not own this post.',
                'steps' => [],
                'postId' => $id,
            ]);
        }

        if ($post->social_type !== 'facebook') {
            return view('user.test-publish-facebook', [
                'error' => 'Post is not a Facebook post. Social type: ' . ($post->social_type ?? 'unknown'),
                'steps' => [],
                'postId' => $id,
            ]);
        }

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 1: Post loaded from database',
            'status' => 'success',
            'data' => [
                'id' => $post->id,
                'type' => $post->type,
                'social_type' => $post->social_type,
                'account_id' => $post->account_id,
                'status' => $post->status,
                'title' => $post->title,
                'url' => $post->url,
                'comment' => $post->comment,
                'page' => $post->page ? [
                    'id' => $post->page->id,
                    'name' => $post->page->name,
                    'page_id' => $post->page->page_id,
                ] : null,
            ],
        ];

        // Step 2: Validate token
        $page = $post->page;
        $tokenResponse = FacebookService::validateToken($page);

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 2: Token validation',
            'status' => $tokenResponse['success'] ? 'success' : 'error',
            'data' => [
                'success' => $tokenResponse['success'],
                'message' => $tokenResponse['message'] ?? null,
                'access_token' => $tokenResponse['success'] ? substr($tokenResponse['access_token'], 0, 30) . '...' : null,
            ],
        ];

        if (!$tokenResponse['success']) {
            return view('user.test-publish-facebook', [
                'steps' => $steps,
                'postId' => $id,
                'post' => $post,
            ]);
        }

        $accessToken = $tokenResponse['access_token'];

        // Step 3: Build payload
        $payload = PostService::postTypeBody($post);

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 3: API payload built',
            'status' => 'success',
            'data' => $payload,
        ];

        // Step 4 & 5: Call Facebook API
        $facebookService = new FacebookService();
        $apiEndpoint = $this->getApiEndpoint($post, $payload);
        $method = $this->getPublishMethod($post->type);

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 4: API request',
            'status' => 'info',
            'data' => [
                'method' => $method,
                'endpoint' => $apiEndpoint,
                'payload' => $payload,
            ],
        ];

        $publishResponse = [];

        switch ($post->type) {
            case 'link':
                $publishResponse = $facebookService->createLink($post->id, $accessToken, $payload);
                break;
            case 'content_only':
                $publishResponse = $facebookService->contentOnly($post->id, $accessToken, $payload);
                break;
            case 'photo':
                $publishResponse = $facebookService->photo($post->id, $accessToken, $payload);
                break;
            case 'video':
                $publishResponse = $facebookService->video($post->id, $accessToken, $payload);
                break;
            default:
                return view('user.test-publish-facebook', [
                    'steps' => array_merge($steps, [[
                        'id' => $stepId++,
                        'title' => 'Step 5: API response',
                        'status' => 'error',
                        'data' => ['error' => 'Unsupported post type: ' . $post->type],
                    ]]),
                    'postId' => $id,
                    'post' => $post,
                ]);
        }

        // Format response for display
        $responseData = [
            'success' => $publishResponse['success'],
            'message' => $publishResponse['message'] ?? null,
        ];

        if ($publishResponse['success'] && isset($publishResponse['data'])) {
            try {
                $graphNode = $publishResponse['data']->getGraphNode();
                $responseData['graph_node'] = is_array($graphNode) ? $graphNode : (array) $graphNode;
                $responseData['post_id'] = $responseData['graph_node']['id'] ?? null;
            } catch (\Exception $e) {
                $responseData['raw'] = method_exists($publishResponse['data'], 'getDecodedBody')
                    ? ($publishResponse['data']->getDecodedBody() ?? [])
                    : [];
                $responseData['error'] = $e->getMessage();
            }
        }

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 5: API response',
            'status' => $publishResponse['success'] ? 'success' : 'error',
            'data' => $responseData,
        ];

        // Step 6: Comment (if post has comment and publish succeeded)
        if ($publishResponse['success'] && !empty($post->comment) && $post->type !== 'video') {
            $postId = $responseData['post_id'] ?? null;
            if ($postId) {
                $commentResponse = $facebookService->postComment($postId, $accessToken, $post->comment);
                $commentData = [
                    'success' => $commentResponse['success'],
                    'message' => $commentResponse['message'] ?? null,
                    'comment' => $post->comment,
                ];
                if ($commentResponse['success'] && isset($commentResponse['data'])) {
                    $cn = $commentResponse['data']->getGraphNode();
                    $commentData['graph_node'] = is_array($cn) ? $cn : (array) $cn;
                }
                $steps[] = [
                    'id' => $stepId++,
                    'title' => 'Step 6: Post comment',
                    'status' => $commentResponse['success'] ? 'success' : 'error',
                    'data' => $commentData,
                ];
            }
        }

        return view('user.test-publish-facebook', [
            'steps' => $steps,
            'postId' => $id,
            'post' => $post,
        ]);
    }

    private function getApiEndpoint(Post $post, array $payload): string
    {
        $pageId = $post->page?->page_id ?? '{page_id}';
        return match ($post->type) {
            'link', 'content_only' => $post->type === 'link' ? "/{$pageId}/feed" : '/me/feed',
            'photo' => "/{$pageId}/photos",
            'video' => "/{$pageId}/videos",
            default => 'unknown',
        };
    }

    private function getPublishMethod(string $type): string
    {
        return match ($type) {
            'link' => 'createLink',
            'content_only' => 'contentOnly',
            'photo' => 'photo',
            'video' => 'video',
            default => 'unknown',
        };
    }
}
