<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\Page;
use App\Models\Post;
use App\Models\Board;
use App\Models\Facebook;
use App\Models\Timeslot;
use App\Models\Pinterest;
use Illuminate\Http\Request;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Jobs\PublishPinterestPost;
use App\Services\PinterestService;
use App\Services\TikTokService;
use App\Jobs\PublishTikTokPost;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tiktok;
use App\Services\PostService;
use App\Services\FeatureUsageService;
use App\Enums\DraftEnum;
use App\Services\SocialMediaLogService;
use Illuminate\Support\Facades\Auth;

class  ScheduleController extends Controller
{
    protected $facebookService;
    protected $pinterestService;
    protected $tiktokService;
    protected $featureUsageService;
    protected $source;
    protected $logService;
    public function __construct(FeatureUsageService $featureUsageService)
    {
        $this->facebookService = new FacebookService();
        $this->pinterestService = new PinterestService();
        $this->tiktokService = new TikTokService();
        $this->featureUsageService = $featureUsageService;
        $this->source = "schedule";
        $this->logService = new SocialMediaLogService();
    }

    /**
     * Verify that a post's account belongs to the user
     * 
     * @param Post $post
     * @param User $user
     * @return bool
     */
    private function verifyPostAccountBelongsToUser(Post $post, User $user): bool
    {
        // Ensure post belongs to user
        if ($post->user_id !== $user->id) {
            return false;
        }

        // Check based on social type
        switch ($post->social_type) {
            case 'pinterest':
                $board = Board::where('id', $post->account_id)
                    ->where('user_id', $user->id)
                    ->first();
                return $board !== null;

            case 'facebook':
                $page = Page::where('id', $post->account_id)
                    ->where('user_id', $user->id)
                    ->first();
                return $page !== null;

            case 'tiktok':
                $tiktok = Tiktok::where('id', $post->account_id)
                    ->where('user_id', $user->id)
                    ->first();
                return $tiktok !== null;

            default:
                return false;
        }
    }

    /**
     * Check if user can create scheduled posts
     * 
     * @param User $user
     * @param int $newPostsCount Number of new posts to be created
     * @return array ['allowed' => bool, 'message' => string, 'usage' => int, 'limit' => int|null, 'remaining' => int|null]
     */
    private function checkScheduledPostsLimit(User $user, int $newPostsCount = 1): array
    {
        // Check if user can use the feature
        if (!$user->canUseFeature('scheduled_posts_per_account')) {
            return [
                'allowed' => false,
                'message' => 'Scheduled posts feature is not available in your package.',
                'usage' => 0,
                'limit' => null,
                'remaining' => null,
            ];
        }

        // Get usage stats
        $usageStats = $this->featureUsageService->getUsageStats($user, 'scheduled_posts_per_account');

        if (empty($usageStats)) {
            return [
                'allowed' => true,
                'message' => 'Feature limit check passed.',
                'usage' => 0,
                'limit' => null,
                'remaining' => null,
            ];
        }

        $currentUsage = $usageStats['current_usage'] ?? 0;
        $limit = $usageStats['limit'] ?? null;
        $isUnlimited = $usageStats['is_unlimited'] ?? false;

        // If unlimited, allow
        if ($isUnlimited || $limit === null) {
            return [
                'allowed' => true,
                'message' => 'Feature limit check passed.',
                'usage' => $currentUsage,
                'limit' => null,
                'remaining' => null,
            ];
        }

        // Check if adding new posts would exceed the limit
        $totalAfterAdding = $currentUsage + $newPostsCount;

        if ($totalAfterAdding > $limit) {
            $remaining = max(0, $limit - $currentUsage);
            return [
                'allowed' => false,
                'message' => "You have reached your limit of {$limit} scheduled posts per account. You have {$remaining} remaining. Please upgrade your package to schedule more posts.",
                'usage' => $currentUsage,
                'limit' => $limit,
                'remaining' => $remaining,
            ];
        }

        return [
            'allowed' => true,
            'message' => 'Feature limit check passed.',
            'usage' => $currentUsage,
            'limit' => $limit,
            'remaining' => $limit - $totalAfterAdding,
        ];
    }
    public function index()
    {
        $user = User::with("boards.pinterest", "pages.facebook", "tiktok")->find(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        return view("user.schedule.index", compact("accounts"));
    }
    /**
     * Summary of accountStatus
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountStatus(Request $request)
    {
        $type = $request->type;
        $id = $request->id;
        $status = $request->status;
        if ($type == "facebook") {
            $page = Page::find($id);
            if ($page) {
                $page->schedule_status = $status == 1 ? "active" : "inactive";
                $page->save();
                $response = [
                    "success" => true,
                    "message" => "Status changed Successfully!"
                ];
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        } else if ($type == "pinterest") {
            $board = Board::find($id);
            if ($board) {
                $board->schedule_status = $status == 1 ? "active" : "inactive";
                $board->save();
                $response = [
                    "success" => true,
                    "message" => "Status changed Successfully!"
                ];
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        } else if ($type == "tiktok") {
            $tiktok = Tiktok::find($id);
            if ($tiktok) {
                $tiktok->schedule_status = $status == 1 ? "active" : "inactive";
                $tiktok->save();
                $response = [
                    "success" => true,
                    "message" => "Status changed Successfully!"
                ];
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        }
        return response()->json($response);
    }
    /**
     * Summary of processPost
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPost(Request $request)
    {
        $action = $request->get("action");
        $link = $request->link;
        if ($link) { //link post
            if ($action == "publish") {
                $response = $this->publishLink($request);
            }
            if ($action == "queue") {
                $response = $this->queueLink($request);
            }
            if ($action == "schedule") {
                $response = $this->scheduleLink($request);
            }
            if ($action == "draft") {
                $response = $this->draftLink($request);
            }
        } else { //no link
            if ($action == "publish") {
                $response = $this->publishPost($request);
            }
            if ($action == "queue") {
                $response = $this->queuePost($request);
            }
            if ($action == "schedule") {
                $response = $this->schedulePost($request);
            }
            if ($action == "draft") {
                $response = $this->draftPost($request);
            }
        }
        return response()->json($response);
    }
    /**
     * Publish Post
     * @param Request $request
     * @return array
     */
    private function publishPost($request): array
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->findOrFail(Auth::guard('user')->id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $file = $request->file("files") ? true : false;
            $image = $video = null;
            if ($file) {
                $is_video = $request->video;
                if ($is_video) {
                    $video = saveToS3($request->file("files"));
                } else {
                    $image = saveImage($request->file("files"));
                }
            }
            // Count total posts to be created
            $totalPostsToCreate = count($accounts);
            
            // Check scheduled posts limit before creating any posts
            /** @var User $user */
            $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
            if (!$limitCheck['allowed']) {
                return [
                    "success" => false,
                    "message" => $limitCheck['message']
                ];
            }

            foreach ($accounts as $account) {
                if ($account->type == "facebook") {

                    Facebook::where("id", $account->fb_id)->firstOrFail();
                    // store in db
                    if ($file) {
                        $type = !empty($image) ?  "photo" : "video";
                    } else {
                        $type = "content_only";
                    }
                    $data = [
                        "user_id" => $user->id,
                        "account_id" => $account->id,
                        "social_type" => "facebook",
                        "type" => $type,
                        "source" => $this->source,
                        "title" => $content,
                        "comment" => $comment,
                        "image" => $image,
                        "video" => $video,
                        "status" => 0,
                        "publish_date" => date("Y-m-d H:i"),
                    ];
                    $post = PostService::create($data);

                    // Verify account belongs to user before incrementing
                    if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                        $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                    }

                    // Log post creation
                    $this->logService->logPost('facebook', $type, $post->id, ['action' => 'publish'], 'pending');

                    // Use validateToken for proper error handling
                    $tokenResponse = FacebookService::validateToken($account);
                    if (!$tokenResponse['success']) {
                        $this->logService->logPost('facebook', $type, $post->id, ['action' => 'publish'], 'failed');
                        return array(
                            "success" => false,
                            "message" => $tokenResponse["message"] ?? "Failed to validate Facebook access token."
                        );
                    }
                    $access_token = $tokenResponse['access_token'];
                    $postData = PostService::postTypeBody($post);
                    $this->logService->log('facebook', 'publish_post', 'Post published successfully', [
                        'data' => $postData
                    ]);
                    PublishFacebookPost::dispatch($post->id, $postData, $access_token, $type, $comment);
                }
                if ($account->type == "pinterest") {
                    $pinterest = Pinterest::where("id", $account->pin_id)->firstOrFail();
                    if ($file) {
                        // store in db
                        $type = !empty($image) ? "photo" : "video";
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "pinterest",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = PinterestService::validateToken($account);
                        if (!$tokenResponse['success']) {
                            return array(
                                "success" => false,
                                "message" => $tokenResponse["message"] ?? "Failed to validate Pinterest access token."
                            );
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishPinterestPost::dispatch($post->id, $postData, $access_token, $type);
                    }
                }
                if ($account->type == "tiktok") {
                    $tiktok = Tiktok::where("id", $account->id)->firstOrFail();

                    // Check if this is a TikTok-specific post (from modal)
                    $tiktokAccountId = $request->get("tiktok_account_id");
                    if ($tiktokAccountId && $tiktokAccountId == $account->id && $file) {
                        // This is a TikTok post from the modal - use TikTok-specific fields
                        $type = !empty($image) ? "photo" : "video";
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "tiktok",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $request->get("content") ?: $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        // Store TikTok-specific metadata in response field
                        $tiktokMetadata = [
                            "privacy_level" => $request->get("tiktok_privacy_level"),
                            "disable_comment" => !$request->get("tiktok_allow_comment", 0),
                            "disable_duet" => !$request->get("tiktok_allow_duet", 0),
                            "disable_stitch" => !$request->get("tiktok_allow_stitch", 0),
                            "commercial_content_toggle" => $request->get("tiktok_commercial_toggle", 0),
                            "your_brand" => $request->get("tiktok_your_brand", 0),
                            "branded_content" => $request->get("tiktok_branded_content", 0),
                        ];
                        $post->update(["response" => json_encode($tiktokMetadata)]);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = TikTokService::validateToken($account);
                        if (!$tokenResponse['success']) {
                            return array(
                                "success" => false,
                                "message" => $tokenResponse["message"] ?? "Failed to validate TikTok access token."
                            );
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        // Merge TikTok metadata into postData
                        $postData = array_merge($postData, $tiktokMetadata);
                        PublishTikTokPost::dispatch($post->id, $postData, $access_token, $type);
                    } elseif ($file) {
                        // Legacy TikTok post (non-modal) - keep existing behavior
                        $type = !empty($image) ? "photo" : "video";
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "tiktok",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = TikTokService::validateToken($account);
                        if (!$tokenResponse['success']) {
                            return array(
                                "success" => false,
                                "message" => $tokenResponse["message"] ?? "Failed to validate TikTok access token."
                            );
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishTikTokPost::dispatch($post->id, $postData, $access_token, $type);
                    }
                }
            }
            $response = array(
                "success" => true,
                "message" => "Your posts are being Published!"
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }

    /**
     * Draft Post - Upload posts as drafts to draft-active platforms
     * @param Request $request
     * @return array
     */
    private function draftPost($request): array
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook", "tiktok")->findOrFail(Auth::guard('user')->id());
            // Get scheduled active accounts
            $accounts = $user->getScheduledActiveAccounts();

            // Filter accounts for draft-active platforms only
            $draftActivePlatforms = DraftEnum::getDraftActivePlatformValues();
            $draftAccounts = $accounts->filter(function ($account) use ($draftActivePlatforms) {
                return in_array($account->type, $draftActivePlatforms);
            });

            if ($draftAccounts->isEmpty()) {
                return [
                    "success" => false,
                    "message" => "No draft-active accounts found. Please connect a TikTok account to use draft functionality."
                ];
            }

            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $file = $request->file("files") ? true : false;
            $image = $video = null;

            if ($file) {
                $is_video = $request->video;
                if ($is_video) {
                    $video = saveToS3($request->file("files"));
                } else {
                    $image = saveImage($request->file("files"));
                }
            }

            // Count total posts to be created
            $totalPostsToCreate = count($draftAccounts);
            
            // Check scheduled posts limit before creating any posts
            /** @var User $user */
            $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
            if (!$limitCheck['allowed']) {
                return [
                    "success" => false,
                    "message" => $limitCheck['message']
                ];
            }

            foreach ($draftAccounts as $account) {
                // Handle TikTok draft posts
                if ($account->type == "tiktok" && DraftEnum::isDraftActiveFor("tiktok")) {
                    if ($file) {
                        // Determine post type
                        $type = !empty($image) ? "photo" : "video";

                        // Store in db
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "tiktok",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "url" => $image, // For photo posts
                            "file_url" => $video, // For video posts
                            "status" => 0, // Draft status
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Log draft post creation
                        $this->logService->logDraft('tiktok', $type, $post->id, ['action' => 'draft'], 'pending');

                        // Use validateToken for proper error handling
                        $tokenResponse = TikTokService::validateToken($account);
                        if (!$tokenResponse['success']) {
                            $this->logService->logDraft('tiktok', $type, $post->id, ['action' => 'draft'], 'failed');
                            return array(
                                "success" => false,
                                "message" => $tokenResponse["message"] ?? "Failed to validate TikTok access token."
                            );
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);

                        // Dispatch draft upload
                        if ($type == "video") {
                            $this->tiktokService->uploadVideoDraft($post->id, $postData, $access_token, 'PULL_FROM_URL');
                        } elseif ($type == "photo") {
                            $this->tiktokService->uploadPhotoDraft($post->id, $postData, $access_token);
                        }
                    } else {
                        // No file provided for draft post
                        return [
                            "success" => false,
                            "message" => "Draft posts require a file (image or video)."
                        ];
                    }
                }
            }

            $response = array(
                "success" => true,
                "message" => "Your posts are being uploaded as drafts!"
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }

    /**
     * Draft Link - Upload link posts as drafts to draft-active platforms
     * @param Request $request
     * @return array
     */
    private function draftLink($request): array
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook", "tiktok")->findOrFail(Auth::guard('user')->id());
            // Get scheduled active accounts
            $accounts = $user->getScheduledActiveAccounts();

            // Filter accounts for draft-active platforms only
            $draftActivePlatforms = DraftEnum::getDraftActivePlatformValues();
            $draftAccounts = $accounts->filter(function ($account) use ($draftActivePlatforms) {
                return in_array($account->type, $draftActivePlatforms);
            });

            if ($draftAccounts->isEmpty()) {
                return [
                    "success" => false,
                    "message" => "No draft-active accounts found. Please connect a TikTok account to use draft functionality."
                ];
            }

            $content = $request->get("content") ?? null;
            $link = $request->get("link") ?? null;
            $comment = $request->get("comment") ?? null;
            $file = $request->file("files") ? true : false;
            $image = null;

            if ($file) {
                $image = saveImage($request->file("files"));
            }

            // Count total posts to be created
            $totalPostsToCreate = count($draftAccounts);
            
            // Check scheduled posts limit before creating any posts
            /** @var User $user */
            $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
            if (!$limitCheck['allowed']) {
                return [
                    "success" => false,
                    "message" => $limitCheck['message']
                ];
            }

            foreach ($draftAccounts as $account) {
                // Handle TikTok draft link posts
                if ($account->type == "tiktok" && DraftEnum::isDraftActiveFor("tiktok")) {
                    if ($file && $image) {
                        // For TikTok, fetch title and thumbnail from link and convert to photo post
                        $linkInfo = $this->fetchTikTokLinkInfo($link);
                        if (!$linkInfo || empty($linkInfo['image'])) {
                            return [
                                "success" => false,
                                "message" => "Failed to fetch title and thumbnail from the link. Please ensure the link is accessible."
                            ];
                        }

                        // Store in db as photo post (not link)
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "tiktok",
                            "type" => "photo", // Changed from "link" to "photo"
                            "source" => $this->source,
                            "title" => !empty($linkInfo['title']) ? $linkInfo['title'] : $content,
                            "comment" => $comment,
                            "url" => $linkInfo['image'], // Store thumbnail image URL
                            "image" => $linkInfo['image'], // Store thumbnail image URL
                            "link" => $link, // Keep original link for reference
                            "status" => 0, // Draft status
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = TikTokService::validateToken($account);
                        if (!$tokenResponse['success']) {
                            return array(
                                "success" => false,
                                "message" => $tokenResponse["message"] ?? "Failed to validate TikTok access token."
                            );
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);

                        // Upload as photo draft
                        $this->tiktokService->uploadPhotoDraft($post->id, $postData, $access_token);
                    } else {
                        return [
                            "success" => false,
                            "message" => "Draft link posts require an image file."
                        ];
                    }
                }
                // Add other draft-active platforms here in the future
            }

            $response = array(
                "success" => true,
                "message" => "Your link posts are being uploaded as drafts!"
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }

    // queue post
    private function queuePost($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::guard('user')->id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $file = $request->file("files") ? true : false;
            $image = $request->file("files");
            $image = $video = null;
            if ($file) {
                $is_video = $request->video;
                if ($is_video) {
                    $video = saveToS3($request->file("files"));
                } else {
                    $image = saveImage($request->file("files"));
                }
            }

            foreach ($accounts as $account) {
                $wouldReceivePost = ($account->type === 'facebook') || ($account->type === 'pinterest' && $file) || ($account->type === 'tiktok' && $file);
                if ($wouldReceivePost && count($account->timeslots) === 0) {
                    return [
                        'success' => false,
                        'message' => 'Please select at least 1 timeslot for the selected account(s).'
                    ];
                }
            }

            $postsToCreate = 0;
            foreach ($accounts as $account) {
                if (count($account->timeslots) > 0) {
                    if ($account->type == "facebook") {
                        $postsToCreate++;
                    } elseif ($account->type == "pinterest" && $file) {
                        $postsToCreate++;
                    } elseif ($account->type == "tiktok" && $file) {
                        $postsToCreate++;
                    }
                }
            }

            // Check scheduled posts limit before creating any posts
            if ($postsToCreate > 0) {
                /** @var User $user */
                $limitCheck = $this->checkScheduledPostsLimit($user, $postsToCreate);
                if (!$limitCheck['allowed']) {
                    return [
                        "success" => false,
                        "message" => $limitCheck['message']
                    ];
                }
            }

            foreach ($accounts as $account) {
                if (count($account->timeslots) > 0) {
                    if ($account->type == "facebook") {
                        Facebook::where("id", $account->fb_id)->firstOrFail();
                        $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "facebook", "source" => "schedule"], $account->timeslots);
                        // store in db
                        if ($file) {
                            $type = !empty($image) ?  "photo" : "video";
                        } else {
                            $type = "content_only";
                        }
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "facebook",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => $nextTime,
                        ];
                        $post = PostService::create($data);
                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        // Log queued post
                        $this->logService->logQueuedPost('facebook', $post->id, ['type' => $type, 'publish_date' => $nextTime]);
                    }
                    if ($account->type == "pinterest") {
                        Pinterest::where("id", $account->pin_id)->firstOrFail();
                        if ($file) {
                            $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "pinterest", "source" => "schedule"], $account->timeslots);
                            // store in db
                            $type = !empty($image) ? "photo" : "video";
                            $data = [
                                "user_id" => $user->id,
                                "account_id" => $account->id,
                                "social_type" => "pinterest",
                                "type" => $type,
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "image" => $image,
                                "video" => $video,
                                "status" => 0,
                                "publish_date" => $nextTime,
                            ];
                            $post = PostService::create($data);
                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                            // Log queued post
                            $this->logService->logQueuedPost('pinterest', $post->id, ['type' => $type, 'publish_date' => $nextTime]);
                        }
                    }
                    if ($account->type == "tiktok") {
                        Tiktok::where("id", $account->id)->firstOrFail();
                        if ($file) {
                            $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "tiktok", "source" => "schedule"], $account->timeslots);
                            // store in db
                            $type = !empty($image) ? "photo" : "video";
                            $data = [
                                "user_id" => $user->id,
                                "account_id" => $account->id,
                                "social_type" => "tiktok",
                                "type" => $type,
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "image" => $image,
                                "video" => $video,
                                "status" => 0,
                                "publish_date" => $nextTime,
                            ];
                            $post = PostService::create($data);
                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                            // Log queued post
                            $this->logService->logQueuedPost('tiktok', $post->id, ['type' => $type, 'publish_date' => $nextTime]);
                        }
                    }
                    $response = array(
                        "success" => true,
                        "message" => "Your posts are queued for Later!"
                    );
                } else {
                    $response = array(
                        "success" => false,
                        "message" => "Please select atleast 1 posting hour for " . $account->name . " account!"
                    );
                }
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return $response;
    }
    // schedule post
    private function schedulePost($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::guard('user')->id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $schedule_date = $request->schedule_date;
            $schedule_time = $request->schedule_time;
            $file = $request->file("files") ? true : false;
            $image = $request->file("files");
            $image = $video = null;
            if ($file) {
                $is_video = $request->video;
                if ($is_video) {
                    $video = saveToS3($request->file("files"));
                } else {
                    $image = saveImage($request->file("files"));
                }
            }

            // Count how many posts will be created
            $postsToCreate = 0;
            foreach ($accounts as $account) {
                if ($account->type == "facebook") {
                    $postsToCreate++;
                } elseif ($account->type == "pinterest" && $file) {
                    $postsToCreate++;
                } elseif ($account->type == "tiktok" && $file) {
                    $postsToCreate++;
                }
            }

            // Check scheduled posts limit before creating any posts
            if ($postsToCreate > 0) {
                /** @var User $user */
                $limitCheck = $this->checkScheduledPostsLimit($user, $postsToCreate);
                if (!$limitCheck['allowed']) {
                    return [
                        "success" => false,
                        "message" => $limitCheck['message']
                    ];
                }
            }

            foreach ($accounts as $account) {
                $scheduleDateTime = date("Y-m-d", strtotime($schedule_date)) . " " . date("H:i", strtotime($schedule_time));
                if ($account->type == "facebook") {
                    Facebook::where("id", $account->fb_id)->firstOrFail();
                    // store in db
                    if ($file) {
                        $type = !empty($image) ?  "photo" : "video";
                    } else {
                        $type = "content_only";
                    }
                    $data = [
                        "user_id" => $user->id,
                        "account_id" => $account->id,
                        "social_type" => "facebook",
                        "type" => $type,
                        "source" => $this->source,
                        "title" => $content,
                        "comment" => $comment,
                        "image" => $image,
                        "video" => $video,
                        "status" => 0,
                        "publish_date" => $scheduleDateTime,
                        "scheduled" => 1
                    ];
                    $post = PostService::create($data);
                    // Verify account belongs to user before incrementing
                    if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                        $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                    }
                    // Log scheduled post
                    $this->logService->logScheduledPost('facebook', $post->id, $scheduleDateTime, ['type' => $type]);
                }
                if ($account->type == "pinterest") {
                    Pinterest::where("id", $account->pin_id)->firstOrFail();
                    if ($file) {
                        // store in db
                        $type = !empty($image) ? "photo" : "video";
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "pinterest",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => $scheduleDateTime,
                            "scheduled" => 1
                        ];
                        $post = PostService::create($data);
                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        // Log scheduled post
                        $this->logService->logScheduledPost('pinterest', $post->id, $scheduleDateTime, ['type' => $type]);
                    }
                }
                if ($account->type == "tiktok") {
                    Tiktok::where("id", $account->id)->firstOrFail();
                    if ($file) {
                        // store in db
                        $type = !empty($image) ? "photo" : "video";
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "tiktok",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => $scheduleDateTime,
                            "scheduled" => 1
                        ];
                        $post = PostService::create($data);
                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        // Log scheduled post
                        $this->logService->logScheduledPost('tiktok', $post->id, $scheduleDateTime, ['type' => $type]);
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Your posts are scheduled for Later!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return $response;
    }
    // publish link post
    private function publishLink($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::guard('user')->id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url) && !empty($image)) {
                // Count total posts to be created
                $totalPostsToCreate = count($accounts);
                
                // Check scheduled posts limit before creating any posts
                /** @var User $user */
                $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
                if (!$limitCheck['allowed']) {
                    return [
                        "success" => false,
                        "message" => $limitCheck['message']
                    ];
                }

                foreach ($accounts as $account) {
                    if ($account->type == "facebook") {
                        Facebook::where("id", $account->fb_id)->firstOrFail();
                        // store in db
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "facebook",
                            "type" => "link",
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "url" => $url,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = FacebookService::validateToken($account);
                        if (!$tokenResponse['success']) {
                            return array(
                                "success" => false,
                                "message" => $tokenResponse["message"] ?? "Failed to validate Facebook access token."
                            );
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $comment);
                    }
                    if ($account->type == "pinterest") {
                        $pinterest = Pinterest::where("id", $account->pin_id)->firstOrFail();
                        // store in db
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "pinterest",
                            "type" => "link",
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "url" => $url,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = PinterestService::validateToken($account);
                        if (!$tokenResponse['success']) {
                            return array(
                                "success" => false,
                                "message" => $tokenResponse["message"] ?? "Failed to validate Pinterest access token."
                            );
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishPinterestPost::dispatch($post->id, $postData, $access_token, "link");
                    }
                    if ($account->type == "tiktok") {
                        $tiktok = Tiktok::where("id", $account->id)->firstOrFail();

                        $localImage = $image ? saveImageFromUrl($image, 'uploads') : null;
                        if (empty($content) || empty($localImage)) {
                            return array(
                                "success" => false,
                                "message" => "Content or image is required."
                            );
                        }

                        // store in db as photo post (not link)
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "tiktok",
                            "type" => "photo", // Changed from "link" to "photo"
                            "source" => $this->source,
                            "title" => $url, // Use content from modal textarea (title)
                            "comment" => $comment,
                            "url" => $url,
                            "image" => $localImage, // Store thumbnail image URL
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = TikTokService::validateToken($account);
                        if (!$tokenResponse['success']) {
                            return array(
                                "success" => false,
                                "message" => $tokenResponse["message"] ?? "Failed to validate TikTok access token."
                            );
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishTikTokPost::dispatch($post->id, $postData, $access_token, "photo"); // Changed from "link" to "photo"
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Your posts are being Published!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Invalid link provided!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }
    // publish link post
    private function queueLink($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::guard('user')->id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url) && !empty($image)) {
                // Count how many posts will be created (accounts with timeslots)
                $postsToCreate = 0;
                foreach ($accounts as $account) {
                    if (count($account->timeslots) > 0) {
                        $postsToCreate++;
                    }
                }

                // Check scheduled posts limit before creating any posts
                if ($postsToCreate > 0) {
                    /** @var User $user */
                    $limitCheck = $this->checkScheduledPostsLimit($user, $postsToCreate);
                    if (!$limitCheck['allowed']) {
                        return [
                            "success" => false,
                            "message" => $limitCheck['message']
                        ];
                    }
                }

                foreach ($accounts as $account) {
                    if (count($account->timeslots) > 0) {
                        if ($account->type == "facebook") {
                            Facebook::where("id", $account->fb_id)->firstOrFail();
                            $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "facebook", "source" => "schedule"], $account->timeslots);
                            // store in db
                            $data = [
                                "user_id" => $user->id,
                                "account_id" => $account->id,
                                "social_type" => "facebook",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => $nextTime
                            ];
                            $post = PostService::create($data);

                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }

                            // Use validateToken for proper error handling
                            $tokenResponse = FacebookService::validateToken($account);
                            if (!$tokenResponse['success']) {
                                return array(
                                    "success" => false,
                                    "message" => $tokenResponse["message"] ?? "Failed to validate Facebook access token."
                                );
                            }
                            $access_token = $tokenResponse['access_token'];
                            $postData = PostService::postTypeBody($post);
                            PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $comment);
                        }
                        if ($account->type == "pinterest") {
                            $pinterest = Pinterest::where("id", $account->pin_id)->firstOrFail();
                            if ($pinterest) {
                                $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "pinterest", "source" => "schedule"], $account->timeslots);
                                // store in db
                                $data = [
                                    "user_id" => $user->id,
                                    "account_id" => $account->id,
                                    "social_type" => "pinterest",
                                    "type" => "link",
                                    "source" => $this->source,
                                    "title" => $content,
                                    "comment" => $comment,
                                    "url" => $url,
                                    "image" => $image,
                                    "status" => 0,
                                    "publish_date" => $nextTime,
                                ];
                                $post = PostService::create($data);

                                // Verify account belongs to user before incrementing
                                if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                    $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                                }

                                // Use validateToken for proper error handling
                                $tokenResponse = PinterestService::validateToken($account);
                                if (!$tokenResponse['success']) {
                                    return array(
                                        "success" => false,
                                        "message" => $tokenResponse["message"] ?? "Failed to validate Pinterest access token."
                                    );
                                }
                                $access_token = $tokenResponse['access_token'];
                                $postData = PostService::postTypeBody($post);
                                PublishPinterestPost::dispatch($post->id, $postData, $access_token, "link");
                            }
                        }
                        if ($account->type == "tiktok") {
                            $tiktok = Tiktok::where("id", $account->id)->firstOrFail();
                            if ($tiktok) {
                                // For TikTok, fetch title and thumbnail from link and convert to photo post

                                $localImage = $image ? saveImageFromUrl($image, 'uploads') : null;
                                if (empty($content) || empty($image)) {
                                    return array(
                                        "success" => false,
                                        "message" => "Content or image is required."
                                    );
                                }

                                $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "tiktok", "source" => "schedule"], $account->timeslots);

                                // store in db as photo post (not link)
                                $data = [
                                    "user_id" => $user->id,
                                    "account_id" => $account->id,
                                    "social_type" => "tiktok",
                                    "type" => "photo", // Changed from "link" to "photo"
                                    "source" => $this->source,
                                    "title" => $url, // Use content from modal textarea (title)
                                    "comment" => $comment,
                                    "url" => $url,
                                    "image" => $localImage,
                                    "status" => 0,
                                    "publish_date" => $nextTime,
                                ];
                                $post = PostService::create($data);

                                // Verify account belongs to user before incrementing
                                if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                    $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                                }

                                // Use validateToken for proper error handling
                                $tokenResponse = TikTokService::validateToken($account);
                                if (!$tokenResponse['success']) {
                                    return array(
                                        "success" => false,
                                        "message" => $tokenResponse["message"] ?? "Failed to validate TikTok access token."
                                    );
                                }
                                $access_token = $tokenResponse['access_token'];
                                $postData = PostService::postTypeBody($post);
                                PublishTikTokPost::dispatch($post->id, $postData, $access_token, "photo"); // Changed from "link" to "photo"
                            }
                        }
                    } else {
                        $response = array(
                            "success" => false,
                            "message" => "Please select atleast 1 posting hour for " . $account->name . " account!"
                        );
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Your posts are being Published!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Invalid link provided!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }
    // publish link post
    private function scheduleLink($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::guard('user')->id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $schedule_date = $request->schedule_date;
            $schedule_time = $request->schedule_time;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url) && !empty($image)) {
                // Count total posts to be created
                $totalPostsToCreate = count($accounts);
                
                // Check scheduled posts limit before creating any posts
                /** @var User $user */
                $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
                if (!$limitCheck['allowed']) {
                    return [
                        "success" => false,
                        "message" => $limitCheck['message']
                    ];
                }

                foreach ($accounts as $account) {
                    $scheduleDateTime = date("Y-m-d", strtotime($schedule_date)) . " " . date("H:i", strtotime($schedule_time));
                    if ($account->type == "facebook") {
                        Facebook::where("id", $account->fb_id)->firstOrFail();
                        // store in db
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "facebook",
                            "type" => "link",
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "url" => $url,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => $scheduleDateTime,
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = FacebookService::validateToken($account);
                        if (!$tokenResponse['success']) {
                            return array(
                                "success" => false,
                                "message" => $tokenResponse["message"] ?? "Failed to validate Facebook access token."
                            );
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $comment);
                    }
                    if ($account->type == "pinterest") {
                        $pinterest = Pinterest::where("id", $account->pin_id)->firstOrFail();
                        if ($pinterest) {
                            // store in db
                            $data = [
                                "user_id" => $user->id,
                                "account_id" => $account->id,
                                "social_type" => "pinterest",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => $scheduleDateTime,
                            ];
                            $post = PostService::create($data);

                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }

                            // Use validateToken for proper error handling
                            $tokenResponse = PinterestService::validateToken($account);
                            if (!$tokenResponse['success']) {
                                return array(
                                    "success" => false,
                                    "message" => $tokenResponse["message"] ?? "Failed to validate Pinterest access token."
                                );
                            }
                            $access_token = $tokenResponse['access_token'];
                            $postData = PostService::postTypeBody($post);
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token, "link");
                        }
                    }
                    if ($account->type == "tiktok") {
                        $tiktok = Tiktok::where("id", $account->id)->firstOrFail();
                        if ($tiktok) {

                            $localImage = $image ? saveImageFromUrl($image, 'uploads') : null;
                            if (empty($content) || empty($image)) {
                                return array(
                                    "success" => false,
                                    "message" => "Content or image is required."
                                );
                            }

                            // store in db as photo post (not link)
                            $data = [
                                "user_id" => $user->id,
                                "account_id" => $account->id,
                                "social_type" => "tiktok",
                                "type" => "photo", // Changed from "link" to "photo"
                                "source" => $this->source,
                                "title" => $url, // Use content from modal textarea (title)
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $localImage,
                                "status" => 0,
                                "publish_date" => $scheduleDateTime,
                            ];
                            $post = PostService::create($data);

                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }

                            // Use validateToken for proper error handling
                            $tokenResponse = TikTokService::validateToken($account);
                            if (!$tokenResponse['success']) {
                                return array(
                                    "success" => false,
                                    "message" => $tokenResponse["message"] ?? "Failed to validate TikTok access token."
                                );
                            }
                            $access_token = $tokenResponse['access_token'];
                            $postData = PostService::postTypeBody($post);
                            PublishTikTokPost::dispatch($post->id, $postData, $access_token, "photo"); // Changed from "link" to "photo"
                        }
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Your posts are being Published!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Invalid link provided!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }
    public function getSetting(Request $request)
    {
        $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        $view = view("user.schedule.ajax.settings", compact("accounts"));
        $response = [
            "success" => true,
            "data" => $view->render()
        ];
        return response()->json($response);
    }
    public function timeslotSetting(Request $request)
    {
        $user = Auth::guard('user')->user();
        try {
            $type = $request->type;
            $id = $request->id;
            $timeslots = $request->timeslots;
            $account = null;
            if ($type == "facebook") {
                $account = Page::with("timeslots")->where("id", $id)->firstOrFail();
                $account_id = $account->id;
            } else if ($type == "pinterest") {
                $account = Board::with("timeslots")->where("id", $id)->firstOrFail();
                $account_id = $account->id;
            } else if ($type == "tiktok") {
                $account = Tiktok::with("timeslots")->where("id", $id)->firstOrFail();
                $account_id = $account->id;
            }
            if ($account) {
                // remove previous
                Timeslot::where("account_id", $account_id)->where("account_type", $type)->where("type", "schedule")->delete();
                // create new timeslots
                if (is_array($timeslots)) {
                    foreach ($timeslots as $timeslot) {
                        Timeslot::create([
                            "user_id" => $user->id,
                            "account_id" => $account_id,
                            "account_type" => $type,
                            "timeslot" => date("H:i", strtotime($timeslot)),
                            "type" => "schedule",
                        ]);
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Timeslot updated Successfully!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return response()->json($response);
    }

    public function saveTimeslotSettings(Request $request)
    {
        $user = Auth::guard('user')->user();
        $timeslotData = $request->timeslot_data ?? [];

        if (empty($timeslotData) || !is_array($timeslotData)) {
            return response()->json([
                "success" => false,
                "message" => "No timeslot data provided!"
            ]);
        }

        try {
            foreach ($timeslotData as $item) {
                $type = $item['type'] ?? null;
                $id = $item['id'] ?? null;
                $timeslots = $item['timeslots'] ?? [];

                if (!$type || !$id || empty($timeslots)) {
                    continue;
                }

                $account = null;
                $accountId = null;

                if ($type == "facebook") {
                    $account = Page::with("timeslots")->where("id", $id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                } else if ($type == "pinterest") {
                    $account = Board::with("timeslots")->where("id", $id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                } else if ($type == "tiktok") {
                    $account = Tiktok::with("timeslots")->where("id", $id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                }

                if ($account && $accountId) {
                    // Remove previous timeslots
                    Timeslot::where("account_id", $accountId)
                        ->where("account_type", $type)
                        ->where("type", "schedule")
                        ->delete();

                    // Create new timeslots
                    foreach ($timeslots as $timeslot) {
                        Timeslot::create([
                            "user_id" => $user->id,
                            "account_id" => $accountId,
                            "account_type" => $type,
                            "timeslot" => date("H:i", strtotime($timeslot)),
                            "type" => "schedule",
                        ]);
                    }

                    // Rearrange posts according to updated timeslots
                    $allTimeslots = [];
                    foreach ($timeslots as $timeslot) {
                        $allTimeslots[] = $timeslot;
                    }

                    if (!empty($allTimeslots)) {
                        // Sort timeslots chronologically
                        usort($allTimeslots, function ($a, $b) {
                            $timeA = strtotime($a);
                            $timeB = strtotime($b);
                            return $timeA - $timeB;
                        });

                        // Get all unpublished scheduled posts for this account
                        $posts = Post::where('account_id', $accountId)
                            ->where('status', '!=', 1) // Not published
                            ->where('scheduled', 1) // Scheduled posts only
                            ->orderBy('publish_date', 'ASC')
                            ->get();

                        if ($posts->count() > 0) {
                            $currentDateTime = now();
                            $currentDate = $currentDateTime->format('Y-m-d');
                            $currentTime = $currentDateTime->format('H:i:s');

                            $currentScheduleDate = $currentDate;
                            $timeslotIndex = 0;
                            // Track used timeslots for each date
                            $usedTimeslotsByDate = [];

                            foreach ($posts as $post) {
                                $assigned = false;

                                // Initialize used timeslots array for current schedule date if not exists
                                if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                    $usedTimeslotsByDate[$currentScheduleDate] = [];
                                }

                                // First, check if there are any available timeslots for the current date
                                $availableTimeslotForCurrentDate = null;
                                $availableTimeslotIndex = null;

                                if ($currentScheduleDate == $currentDate) {
                                    // Check current date for available timeslots
                                    foreach ($allTimeslots as $idx => $timeslot) {
                                        $timeslot24Hour = date('H:i:s', strtotime($timeslot));
                                        $timeslotKey = $timeslot24Hour;

                                        // Check if timeslot is available (not passed and not used)
                                        if (
                                            $timeslot24Hour > $currentTime &&
                                            !in_array($timeslotKey, $usedTimeslotsByDate[$currentScheduleDate])
                                        ) {
                                            $availableTimeslotForCurrentDate = $timeslot;
                                            $availableTimeslotIndex = $idx;
                                            break;
                                        }
                                    }
                                }

                                // If available timeslot found for current date, use it
                                if ($availableTimeslotForCurrentDate !== null) {
                                    $timeslot24Hour = date('H:i:s', strtotime($availableTimeslotForCurrentDate));
                                    $timeslotKey = $timeslot24Hour;

                                    // Assign post to this timeslot on current date
                                    $publishDateTime = $currentScheduleDate . ' ' . $timeslot24Hour;
                                    $post->update([
                                        'publish_date' => $publishDateTime
                                    ]);

                                    // Mark timeslot as used for this date
                                    $usedTimeslotsByDate[$currentScheduleDate][] = $timeslotKey;

                                    // Move to next timeslot index
                                    $timeslotIndex = ($availableTimeslotIndex + 1) % count($allTimeslots);

                                    // If we've used all timeslots for this day, move to next day
                                    if (count($usedTimeslotsByDate[$currentScheduleDate]) >= count($allTimeslots)) {
                                        $currentScheduleDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                        $timeslotIndex = 0;
                                        if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                            $usedTimeslotsByDate[$currentScheduleDate] = [];
                                        }
                                    }

                                    $assigned = true;
                                } else {
                                    // No available timeslot for current date, find next available
                                    $attempts = 0;
                                    $maxAttempts = count($allTimeslots) * 100; // Safety limit

                                    while (!$assigned && $attempts < $maxAttempts) {
                                        // Get current timeslot
                                        $timeslot = $allTimeslots[$timeslotIndex];

                                        // Convert timeslot to 24-hour format
                                        $timeslot24Hour = date('H:i:s', strtotime($timeslot));

                                        // Initialize used timeslots array for current schedule date if not exists
                                        if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                            $usedTimeslotsByDate[$currentScheduleDate] = [];
                                        }

                                        // Check if timeslot is already used for this date
                                        $timeslotKey = $timeslot24Hour;
                                        if (in_array($timeslotKey, $usedTimeslotsByDate[$currentScheduleDate])) {
                                            // Timeslot already used for this date, try next timeslot
                                            $timeslotIndex++;
                                            if ($timeslotIndex >= count($allTimeslots)) {
                                                // All timeslots used for this day, move to next day and reset timeslot index
                                                $currentScheduleDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                                $timeslotIndex = 0;
                                                // Reset used timeslots tracking for new date (timeslots reset on new date)
                                                if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                                    $usedTimeslotsByDate[$currentScheduleDate] = [];
                                                }
                                            }
                                            $attempts++;
                                            continue;
                                        }

                                        // Check if timeslot has passed for current day
                                        if ($currentScheduleDate == $currentDate && $timeslot24Hour <= $currentTime) {
                                            // Timeslot has passed, move to next day (keep same timeslot index since it's a new date)
                                            $currentScheduleDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                            // Reset used timeslots tracking for new date (timeslots reset on new date)
                                            if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                                $usedTimeslotsByDate[$currentScheduleDate] = [];
                                            }
                                            $attempts++;
                                            continue;
                                        }

                                        // Timeslot is available for this date, assign post
                                        $publishDateTime = $currentScheduleDate . ' ' . $timeslot24Hour;
                                        $post->update([
                                            'publish_date' => $publishDateTime
                                        ]);

                                        // Mark timeslot as used for this date
                                        $usedTimeslotsByDate[$currentScheduleDate][] = $timeslotKey;

                                        // Move to next timeslot
                                        $timeslotIndex++;
                                        if ($timeslotIndex >= count($allTimeslots)) {
                                            // All timeslots used for this day, move to next day and reset timeslot index
                                            $currentScheduleDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                            $timeslotIndex = 0;
                                            // Reset used timeslots tracking for new date
                                            if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                                $usedTimeslotsByDate[$currentScheduleDate] = [];
                                            }
                                        }

                                        $assigned = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return response()->json([
                "success" => true,
                "message" => "Timeslot settings saved and posts rearranged successfully!"
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    public function postsListing(Request $request)
    {
        $data = $request->all();
        $posts = Post::with("page.facebook", "board.pinterest")->isScheduled();
        // filters
        if (!empty($request->account_id)) {
            $posts = $posts->whereIn("account_id", $request->account_id);
        }
        if (!empty($request->type)) {
            $posts = $posts->whereIn("social_type", $request->type);
        }
        if (!empty($request->post_type)) {
            $posts = $posts->whereIn("type", $request->post_type);
        }
        if ($request->has('status')) {
            $posts = $posts->where("status", $request->status);
        }
        $totalRecordswithFilter = clone $posts;
        $posts = $posts->offset(intval($data['start']))->limit(intval($data['length']));
        $posts = $posts->orderBy("publish_date", "asc")->get();
        $posts->append(["post_details", "account_detail", "publish_datetime", "status_view", "action", "account_name", "account_profile", "published_at_formatted"]);
        $response = [
            "draw" => intval($data['draw']),
            "iTotalRecords" => Post::count(),
            "iTotalDisplayRecords" => $totalRecordswithFilter->count(),
            "data" => $posts
        ];
        return response()->json($response);
    }

    public function postDelete(Request $request)
    {
        try {
            $post = Post::findOrFail($request->id);
            $user = User::findOrFail($post->user_id);
            // Decrement feature usage if this is a scheduled post and account belongs to user
            if ($post->source === 'schedule' && $this->verifyPostAccountBelongsToUser($post, $user)) {
                $user->decrementFeatureUsage('scheduled_posts_per_account', 1);
            }

            $post->photo()->delete();
            PostService::delete($post->id);
            $response = [
                "success" => true,
                "message" => "Post delete Successfully!"
            ];
        } catch (Exception $e) {
            $response = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return response()->json($response);
    }

    public function postEdit(Request $request)
    {
        try {
            $post = Post::with("page.facebook", "board.pinterest")->findOrFail($request->id);
            $view = view("user.schedule.ajax.edit-post", compact("post"));
            $response = array(
                "success" => true,
                "data" => $view->render(),
                "action" => route('panel.schedule.post.update', $post->id)
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return response()->json($response);
    }
    public function postUpdate($id, Request $request)
    {
        try {
            $post = Post::findOrFail($id);
            $data = [
                "title" => $request->edit_post_title,
                "url" => $request->edit_post_link,
                "comment" => $request->edit_post_comment,
                "publish_date" => date("Y-m-d", strtotime($request->edit_post_publish_date)) . " " . date("H:i", strtotime($request->edit_post_publish_time)),
            ];
            if ($request->has("edit_post_publish_image") && $request->File("edit_post_publish_image")) {
                $image = saveImage($request->file("edit_post_publish_image"));
                $data['image'] = $image;
            }
            $post->update($data);
            $response = array(
                "success" => true,
                "message" => "Post updated Successfully!"
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return response()->json($response);
    }

    public function postPublishNow(Request $request)
    {
        $response = PostService::publishNow($request->id);
        return response()->json($response);
    }
}
