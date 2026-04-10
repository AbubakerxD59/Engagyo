<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\PublishInstagramPost;
use App\Models\InstagramAccount;
use App\Models\Post;
use App\Models\Page;
use App\Models\User;
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
            case 'reel':
                $publishResponse = $facebookService->reel($post->id, $accessToken, $payload);
                break;
            case 'story':
                $publishResponse = $facebookService->story($post->id, $accessToken, $payload);
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
            if (is_array($publishResponse['data'])) {
                $responseData['post_id'] = $publishResponse['data']['post_id'] ?? null;
                $responseData['video_id'] = $publishResponse['data']['video_id'] ?? null;
                $responseData['raw'] = $publishResponse['data']['raw'] ?? $publishResponse['data'];
            } else {
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
        }

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 5: API response',
            'status' => $publishResponse['success'] ? 'success' : 'error',
            'data' => $responseData,
        ];

        // Step 6: Comment (if post has comment and publish succeeded)
        if ($publishResponse['success'] && !empty($post->comment) && !in_array($post->type, ['video', 'reel', 'story'], true)) {
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
            'reel' => "/{$pageId}/video_reels (+ rupload.facebook.com)",
            'story' => "/{$pageId}/photo_stories or /{$pageId}/video_stories",
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
            'reel' => 'reel',
            'story' => 'story',
            default => 'unknown',
        };
    }

    /**
     * Show a simple form to upload a file and test publishing it as a Facebook Story.
     * Route: GET /panel/test/story-upload
     */
    public function showStoryUploadForm()
    {
        $user = Auth::guard('user')->user();
        $pages = Page::where('user_id', $user->id)->get();

        return view('user.test-story-upload', [
            'pages' => $pages,
        ]);
    }

    /**
     * Handle the story test upload: create a temporary Post of type "story" and
     * redirect to the existing publishFacebook test route to display all steps.
     * Route: POST /panel/test/story-upload
     */
    public function handleStoryUpload(Request $request)
    {
        $user = Auth::guard('user')->user();

        $validated = $request->validate([
            'page_id' => 'required|integer|exists:pages,id',
            'file' => 'required|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/x-matroska,video/quicktime,video/mpeg,video/webm',
            'caption' => 'nullable|string|max:1000',
        ]);

        $page = Page::where('id', $validated['page_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $isVideo = in_array($ext, ['mp4', 'mkv', 'mov', 'mpeg', 'webm'], true);

        $image = null;
        $video = null;
        if ($isVideo) {
            // Use the same helper as schedule flow for uploading videos
            $video = saveToS3($file);
        } else {
            // Use the same helper as schedule flow for images
            $image = saveImage($file);
        }

        $data = [
            'user_id' => $user->id,
            'account_id' => $page->id,
            'social_type' => 'facebook',
            'type' => 'story',
            'source' => 'test',
            'title' => $validated['caption'] ?? null,
            'comment' => null,
            'image' => $image,
            'video' => $video,
            'status' => 0,
            'publish_date' => now()->format('Y-m-d H:i:s'),
        ];

        $post = PostService::create($data);

        return redirect()->route('panel.test.publish.facebook', ['id' => $post->id]);
    }

    /**
     * Test Instagram photo publish (Content Publishing API). GET form.
     * Route: GET /panel/test/instagram-image
     */
    public function showInstagramImageTestForm()
    {
        $user = Auth::guard('user')->user();
        $accounts = $this->instagramAccountsForPanelUser($user);

        return view('user.test-instagram-image', [
            'accounts' => $accounts,
            'steps' => [],
        ]);
    }

    /**
     * Accept image upload, create Post, run PublishInstagramPost synchronously, show steps.
     * Route: POST /panel/test/instagram-image
     */
    public function handleInstagramImageTest(Request $request)
    {
        $user = Auth::guard('user')->user();
        $steps = [];
        $stepId = 1;

        $validated = $request->validate([
            'instagram_account_id' => 'required|integer|exists:instagram_accounts,id',
            'image' => 'required|file|mimetypes:image/jpeg,image/png,image/webp',
            'caption' => 'nullable|string|max:2200',
        ]);

        $ig = InstagramAccount::where('id', $validated['instagram_account_id'])->first();
        if (! $ig) {
            return view('user.test-instagram-image', [
                'accounts' => $this->instagramAccountsForPanelUser($user),
                'error' => 'Instagram account not found.',
                'steps' => [],
            ]);
        }

        if (! $this->userOwnsInstagramAccount($user, $ig)) {
            return view('user.test-instagram-image', [
                'accounts' => $this->instagramAccountsForPanelUser($user),
                'error' => 'You do not have access to this Instagram account.',
                'steps' => [],
            ]);
        }

        if (empty($ig->ig_user_id)) {
            return view('user.test-instagram-image', [
                'accounts' => $this->instagramAccountsForPanelUser($user),
                'error' => 'This account has no Instagram user id. Reconnect it in Accounts.',
                'steps' => [],
            ]);
        }

        $fileName = saveImage($request->file('image'));
        $caption = $validated['caption'] ?? '';

        $post = PostService::create([
            'user_id' => $user->id,
            'account_id' => $ig->id,
            'social_type' => 'instagram',
            'type' => 'photo',
            'source' => 'test',
            'title' => 'Instagram image test',
            'comment' => $caption,
            'image' => $fileName,
            'publish_date' => now()->format('Y-m-d H:i'),
            'scheduled' => 0,
        ]);

        $post->load('instagramAccount');

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 1: Post created',
            'status' => 'success',
            'data' => [
                'post_id' => $post->id,
                'image_file' => $fileName,
                'social_type' => $post->social_type,
                'type' => $post->type,
                'account_id' => $post->account_id,
                'instagram_username' => $ig->username,
                'publish_date_utc' => $post->publish_date,
            ],
        ];

        $imageUrl = PostService::instagramGraphImageUrl($post);
        $payload = PostService::postTypeBody($post);

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 2: Image URL for Graph API (must be public HTTPS)',
            'status' => $imageUrl ? 'success' : 'error',
            'data' => [
                'image_url' => $imageUrl,
                'hint' => 'Set APP_URL or INSTAGRAM_IMAGE_PUBLIC_BASE_URL so Meta can fetch the file.',
            ],
        ];

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 3: Publish payload (caption + image_url)',
            'status' => 'info',
            'data' => $payload,
        ];

        $tokenResponse = FacebookService::validateToken($ig);
        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 4: Access token',
            'status' => $tokenResponse['success'] ? 'success' : 'error',
            'data' => [
                'success' => $tokenResponse['success'],
                'message' => $tokenResponse['message'] ?? null,
                'access_token_preview' => ($tokenResponse['success'] ?? false)
                    ? substr((string) $tokenResponse['access_token'], 0, 24).'...'
                    : null,
            ],
        ];

        if (! ($tokenResponse['success'] ?? false)) {
            return view('user.test-instagram-image', [
                'accounts' => $this->instagramAccountsForPanelUser($user),
                'steps' => $steps,
                'post' => $post->fresh(),
            ]);
        }

        PublishInstagramPost::dispatchSync($post->id, $tokenResponse['access_token']);

        $post->refresh();
        $responseDecoded = json_decode((string) $post->response, true);

        $steps[] = [
            'id' => $stepId++,
            'title' => 'Step 5: Publish job finished',
            'status' => (int) $post->status === 1 ? 'success' : 'error',
            'data' => [
                'status' => $post->status,
                'post_id_graph' => $post->post_id,
                'response' => $responseDecoded ?? $post->response,
            ],
        ];

        return view('user.test-instagram-image', [
            'accounts' => $this->instagramAccountsForPanelUser($user),
            'steps' => $steps,
            'post' => $post,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, InstagramAccount>
     */
    private function instagramAccountsForPanelUser(User $user)
    {
        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);
        $q = InstagramAccount::query()->where('user_id', $ownerId);

        if ($user->isTeamMember()) {
            $igIds = $user->getTeamMemberAccountIdsByType('instagram');
            if (empty($igIds)) {
                return collect();
            }
            $q->whereIn('id', array_map('intval', $igIds));
        }

        return $q->orderBy('username')->get();
    }

    private function userOwnsInstagramAccount(User $user, InstagramAccount $ig): bool
    {
        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);
        if ((int) $ig->user_id !== $ownerId) {
            return false;
        }
        if ($user->isTeamMember()) {
            $igIds = $user->getTeamMemberAccountIdsByType('instagram');

            return in_array((int) $ig->id, array_map('intval', $igIds), true);
        }

        return true;
    }
}
