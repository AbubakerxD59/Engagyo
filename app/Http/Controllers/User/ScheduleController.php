<?php

namespace App\Http\Controllers\User;

use App\Enums\DraftEnum;
use App\Http\Controllers\Controller;
use App\Jobs\DeleteFacebookPostJob;
use App\Jobs\DeleteSentPostJob;
use App\Jobs\PublishFacebookPost;
use App\Jobs\PublishPinterestPost;
use App\Jobs\PublishTikTokPost;
use App\Models\Board;
use App\Models\Facebook;
use App\Models\Notification;
use App\Models\Page;
use App\Models\PagePost;
use App\Models\Pinterest;
use App\Models\Post;
use App\Models\Tiktok;
use App\Models\Timeslot;
use App\Models\User;
use App\Services\FacebookService;
use App\Services\FeatureUsageService;
use App\Services\PagePostsSyncService;
use App\Services\PinterestService;
use App\Services\PostService;
use App\Services\SocialMediaLogService;
use App\Services\TikTokService;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class  ScheduleController extends Controller
{
    private const POSTS_CACHE_TTL_HOURS = 3;

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
    // old design code
    // public function index()
    // {
    //     $user = User::with("boards.pinterest", "pages.facebook", "tiktok", "timezone")->find(Auth::guard('user')->id());
    //     $accounts = $user->getAccounts();
    //     $userTimezoneName = $user->timezone && !empty($user->timezone->name) ? $user->timezone->name : 'UTC';
    //     return view("user.schedule.index", compact("accounts", "userTimezoneName"));
    // }

    // new design code
    public function index()
    {
        $user = User::with("boards.pinterest", "pages.facebook", "tiktok", "timezone")->find(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        $accounts = $this->sortAccountsByRecentUsage($accounts, $user->id);
        $userTimezoneName = $user->timezone && !empty($user->timezone->name) ? $user->timezone->name : 'UTC';
        $canAccessAnalytics = $user->canAccessMenu(8) && $user->hasMenuAccess('analytics');
        $scheduleSelectedAccount = $user->schedule_selected_account;
        return view("user.schedule-new-design.index", compact("accounts", "userTimezoneName", "canAccessAnalytics", "scheduleSelectedAccount"));
    }

    /**
     * Sort accounts for sidebar: frequently used (posts in recent days) first, then remaining by name ascending.
     */
    protected function sortAccountsByRecentUsage($accounts, $userId, $recentDays = 7)
    {
        $countRows = Post::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subDays($recentDays))
            ->selectRaw('account_id, social_type, count(*) as post_count')
            ->groupBy('account_id', 'social_type')
            ->get();

        $postCounts = [];
        foreach ($countRows as $row) {
            $key = (string) $row->social_type . '_' . (int) $row->account_id;
            $postCounts[$key] = (int) $row->post_count;
        }

        $sortSegment = function ($segment) use ($postCounts) {
            $segment = $segment->values();
            $sorted = $segment->sortByDesc(function ($account) use ($postCounts) {
                $key = ($account->type ?? '') . '_' . (int) $account->id;

                return $postCounts[$key] ?? 0;
            })->values();

            $withPosts = $sorted->filter(function ($a) use ($postCounts) {
                $key = ($a->type ?? '') . '_' . (int) $a->id;

                return ($postCounts[$key] ?? 0) > 0;
            });
            $withoutPosts = $sorted->filter(function ($a) use ($postCounts) {
                $key = ($a->type ?? '') . '_' . (int) $a->id;

                return ($postCounts[$key] ?? 0) === 0;
            })->sortBy('name')->values();

            return $withPosts->concat($withoutPosts);
        };

        $facebook = $sortSegment($accounts->filter(fn ($a) => ($a->type ?? '') === 'facebook'));
        $pinterest = $sortSegment($accounts->filter(fn ($a) => ($a->type ?? '') === 'pinterest'));
        $tiktok = $sortSegment($accounts->filter(fn ($a) => ($a->type ?? '') === 'tiktok'));

        return $facebook->concat($pinterest)->concat($tiktok);
    }

    /**
     * Get the account used for the most recently created post (last used account)
     * and the schedule_status of all Facebook pages for dynamic updates
     */
    public function getLastUsedAccount()
    {
        $userId = Auth::guard('user')->id();
        $user = User::find($userId);
        $post = Post::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        $account = null;
        if ($post) {
            $st = $post->social_type ?? '';
            if (str_contains($st, 'facebook')) {
                $page = Page::with('facebook')->find($post->account_id);
                if ($page) {
                    $account = [
                        'id' => $page->id,
                        'type' => 'facebook',
                        'name' => $page->name,
                        'profile_image' => $page->profile_image,
                    ];
                }
            } elseif (str_contains($st, 'pinterest')) {
                $board = Board::with('pinterest')->find($post->account_id);
                if ($board) {
                    $account = [
                        'id' => $board->id,
                        'type' => 'pinterest',
                        'name' => $board->name,
                        'profile_image' => $board->pinterest?->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'tiktok')) {
                $tiktok = Tiktok::find($post->account_id);
                if ($tiktok) {
                    $account = [
                        'id' => $tiktok->id,
                        'type' => 'tiktok',
                        'name' => $tiktok->display_name ?? $tiktok->username,
                        'profile_image' => $tiktok->profile_image ?? '',
                    ];
                }
            }
        }

        $accountsStatus = collect();
        if ($user) {
            $user->pages()->get(['id', 'schedule_status'])->each(function ($page) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $page->id,
                    'type' => 'facebook',
                    'schedule_status' => $page->schedule_status ?? 'inactive',
                ]);
            });
            $user->boards()->get(['id', 'schedule_status'])->each(function ($board) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $board->id,
                    'type' => 'pinterest',
                    'schedule_status' => $board->schedule_status ?? 'inactive',
                ]);
            });
            $user->tiktok()->get(['id', 'schedule_status'])->each(function ($tiktokRow) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $tiktokRow->id,
                    'type' => 'tiktok',
                    'schedule_status' => $tiktokRow->schedule_status ?? 'inactive',
                ]);
            });
        }

        return response()->json([
            'success' => true,
            'account' => $account,
            'accounts_status' => $accountsStatus->toArray(),
        ]);
    }

    /**
     * Fetch all user accounts with their schedule_status for the create post modal.
     * Used when New Post button is clicked to sync dropdown selection with server state.
     */
    public function getAccountsWithStatus()
    {
        $user = User::find(Auth::guard('user')->id());
        $accountsStatus = collect();

        $user->pages()->get(['id', 'schedule_status'])->each(function ($page) use ($accountsStatus) {   
            $accountsStatus->push([
                'id' => $page->id,
                'type' => 'facebook',
                'schedule_status' => $page->schedule_status ?? 'inactive',
            ]);
        });
        $user->boards()->get(['id', 'schedule_status'])->each(function ($board) use ($accountsStatus) {
            $accountsStatus->push([
                'id' => $board->id,
                'type' => 'pinterest',
                'schedule_status' => $board->schedule_status ?? 'inactive',
            ]);
        });
        $user->tiktok()->get(['id', 'schedule_status'])->each(function ($tiktok) use ($accountsStatus) {
            $accountsStatus->push([
                'id' => $tiktok->id,
                'type' => 'tiktok',
                'schedule_status' => $tiktok->schedule_status ?? 'inactive',
            ]);
        });

        $account = null;
        $post = Post::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();
        if ($post) {
            $st = $post->social_type ?? '';
            if (str_contains($st, 'facebook')) {
                $page = Page::with('facebook')->find($post->account_id);
                if ($page) {
                    $account = [
                        'id' => $page->id,
                        'type' => 'facebook',
                        'name' => $page->name,
                        'profile_image' => $page->profile_image,
                    ];
                }
            } elseif (str_contains($st, 'pinterest')) {
                $board = Board::with('pinterest')->find($post->account_id);
                if ($board) {
                    $account = [
                        'id' => $board->id,
                        'type' => 'pinterest',
                        'name' => $board->name,
                        'profile_image' => $board->pinterest?->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'tiktok')) {
                $tiktokRow = Tiktok::find($post->account_id);
                if ($tiktokRow) {
                    $account = [
                        'id' => $tiktokRow->id,
                        'type' => 'tiktok',
                        'name' => $tiktokRow->display_name ?? $tiktokRow->username,
                        'profile_image' => $tiktokRow->profile_image ?? '',
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'account' => $account,
            'accounts_status' => $accountsStatus->toArray(),
        ]);
    }

    /**
     * Get the user's saved schedule selected account from the database.
     */
    public function getSelectedAccount()
    {
        $user = Auth::guard('user')->user();
        $data = $user->schedule_selected_account;
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Save the user's schedule selected account to the database.
     */
    public function saveSelectedAccount(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:all,facebook,pinterest,tiktok',
            'id' => 'nullable|string',
        ]);
        $user = User::find(Auth::guard('user')->id());
        $data = [
            'type' => $request->type,
            'id' => $request->id,
        ];
        $user->update(['schedule_selected_account' => $data]);
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
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
     * Resolve which accounts to use for post creation.
     * When account_ids are provided in the request (from create post modal selection),
     * use those. Otherwise use getScheduledActiveAccounts().
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    private function resolveAccountsForPost(Request $request, User $user)
    {
        $accountIds = $request->get('account_ids');
        if ($accountIds) {
            if (is_string($accountIds)) {
                $accountIds = json_decode($accountIds, true);
            }
            if (is_array($accountIds) && count($accountIds) > 0) {
                return $user->getAccountsForPostCreation($accountIds);
            }
        }
        return $user->getScheduledActiveAccounts();
    }

    /**
     * Facebook media formats from create-post modal.
     * Input (from JS): facebook_content_formats = JSON array of "post" | "reel" | "story"
     * - image: post -> photo, story -> story
     * - video: post -> video, reel -> reel, story -> story
     *
     * Returns an array of concrete post types: ["photo","story"] etc.
     */
    private function facebookMediaTypesFromRequest(Request $request, bool $hasImage): array
    {
        $raw = $request->input('facebook_content_formats');
        $selected = [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $selected = $decoded;
            }
        } elseif (is_array($raw)) {
            $selected = $raw;
        }

        // Fallback to a single 'post' if nothing is selected.
        if (empty($selected)) {
            $selected = ['post'];
        }

        $selected = array_values(array_unique(array_map('strval', $selected)));
        $types = [];
        foreach ($selected as $format) {
            $format = strtolower(trim($format));
            if ($format === 'story') {
                $types[] = 'story';
                continue;
            }
            if ($format === 'reel') {
                // Only valid for video posts
                if (!$hasImage) {
                    $types[] = 'reel';
                }
                continue;
            }
            // 'post' fallback
            if ($hasImage) {
                $types[] = 'photo';
            } else {
                $types[] = 'video';
            }
        }

        // Ensure at least one sane default
        if (empty($types)) {
            $types[] = $hasImage ? 'photo' : 'video';
        }

        return array_values(array_unique($types));
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
            $accounts = $this->resolveAccountsForPost($request, $user);
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
            // Count total posts to be created (one per selected format per Facebook account)
            $totalPostsToCreate = 0;
            foreach ($accounts as $account) {
                if ($account->type == "facebook") {
                    if ($file) {
                        $types = $this->facebookMediaTypesFromRequest($request, !empty($image));
                        $totalPostsToCreate += count($types);
                    } else {
                        $totalPostsToCreate++;
                    }
                } elseif ($account->type == "pinterest" && $file) {
                    $totalPostsToCreate++;
                } elseif ($account->type == "tiktok" && $file) {
                    $totalPostsToCreate++;
                }
            }
            
            // Check scheduled posts limit before creating any posts
            /** @var User $user */
            $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
            if (!$limitCheck['allowed']) {
                return [
                    "success" => false,
                    "message" => $limitCheck['message']
                ];
            }

            $publishDateNow = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

            foreach ($accounts as $account) {
                if ($account->type == "facebook") {

                    Facebook::where("id", $account->fb_id)->firstOrFail();

                    // Validate token once per account
                    $tokenResponse = FacebookService::validateToken($account);
                    if (!$tokenResponse['success']) {
                        return [
                            "success" => false,
                            "message" => $tokenResponse["message"] ?? "Failed to validate Facebook access token."
                        ];
                    }
                    $access_token = $tokenResponse['access_token'];

                    if ($file) {
                        $types = $this->facebookMediaTypesFromRequest($request, !empty($image));
                    } else {
                        $types = ["content_only"];
                    }

                    foreach ($types as $type) {
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
                            "publish_date" => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        $this->logService->logPost('facebook', $type, $post->id, ['action' => 'publish'], 'pending');

                        $postData = PostService::postTypeBody($post);
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, $type, $post->comment);
                    }
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
                            "publish_date" => $publishDateNow,
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
                            "publish_date" => $publishDateNow,
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
                            "publish_date" => $publishDateNow,
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
            $accounts = $this->resolveAccountsForPost($request, $user);

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

            $publishDateNow = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

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
                            "publish_date" => $publishDateNow,
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
            $accounts = $this->resolveAccountsForPost($request, $user);

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

            $publishDateNow = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

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
                            "link" => $link,
                            "status" => 0, // Draft status
                            "publish_date" => $publishDateNow,
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
            $accounts = $this->resolveAccountsForPost($request, $user);
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
                        if ($file) {
                            $types = $this->facebookMediaTypesFromRequest($request, !empty($image));
                            $postsToCreate += count($types);
                        } else {
                            $postsToCreate++;
                        }
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
                        $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "facebook", "source" => "schedule"], $account->timeslots, $user);
                        if ($file) {
                            $types = $this->facebookMediaTypesFromRequest($request, !empty($image));
                        } else {
                            $types = ["content_only"];
                        }
                        foreach ($types as $type) {
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
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                            $this->logService->logQueuedPost('facebook', $post->id, ['type' => $type, 'publish_date' => $nextTime]);
                        }
                    }
                    if ($account->type == "pinterest") {
                        Pinterest::where("id", $account->pin_id)->firstOrFail();
                        if ($file) {
                            $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "pinterest", "source" => "schedule"], $account->timeslots, $user);
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
                            $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "tiktok", "source" => "schedule"], $account->timeslots, $user);
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
            $accounts = $this->resolveAccountsForPost($request, $user);
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

            // Count how many posts will be created (one per selected format per Facebook account)
            $postsToCreate = 0;
            foreach ($accounts as $account) {
                if ($account->type == "facebook") {
                    if ($file) {
                        $types = $this->facebookMediaTypesFromRequest($request, !empty($image));
                        $postsToCreate += count($types);
                    } else {
                        $postsToCreate++;
                    }
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
                    if ($file) {
                        $types = $this->facebookMediaTypesFromRequest($request, !empty($image));
                    } else {
                        $types = ["content_only"];
                    }
                    foreach ($types as $type) {
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
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        $this->logService->logScheduledPost('facebook', $post->id, $scheduleDateTime, ['type' => $type]);
                    }
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
            $accounts = $this->resolveAccountsForPost($request, $user);
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url)) {
                // Count total posts to be created (link allowed without image for Facebook/Instagram per Data Policy)
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

                $publishDateNow = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

                foreach ($accounts as $account) {
                    if ($account->type == "facebook") {
                        // store in db (image may be empty for Facebook/Instagram links)
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
                            "publish_date" => $publishDateNow,
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
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $post->comment);
                    }
                    if ($account->type == "pinterest") {
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
                            "publish_date" => $publishDateNow,
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
                    if ($account->type == "tiktok" && !empty($image)) {
                        $localImage = saveImageFromUrl($image, 'uploads');
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
                            "publish_date" => $publishDateNow,
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
            $accounts = $this->resolveAccountsForPost($request, $user);
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url)) {
                // Count how many posts will be created (link allowed without image for Facebook/Instagram)
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
                            $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "facebook", "source" => "schedule"], $account->timeslots, $user);
                            // store in db (image may be empty for Facebook/Instagram links)
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
                        }
                        if ($account->type == "pinterest") {
                            $pinterest = Pinterest::where("id", $account->pin_id)->firstOrFail();
                            if ($pinterest) {
                                $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "pinterest", "source" => "schedule"], $account->timeslots, $user);
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
                            }
                        }
                        if ($account->type == "tiktok" && !empty($image)) {
                            $tiktok = Tiktok::where("id", $account->id)->firstOrFail();
                            if ($tiktok) {
                                $localImage = saveImageFromUrl($image, 'uploads');
                                if (empty($content) || empty($localImage)) {
                                    return array(
                                        "success" => false,
                                        "message" => "Content or image is required."
                                    );
                                }

                                $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "tiktok", "source" => "schedule"], $account->timeslots, $user);

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
                    "message" => "Your posts are queued for Later!"
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
            $accounts = $this->resolveAccountsForPost($request, $user);
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $schedule_date = $request->schedule_date;
            $schedule_time = $request->schedule_time;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url)) {
                // Count total posts to be created (link allowed without image for Facebook/Instagram)
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
                        // store in db (image may be empty for Facebook/Instagram links)
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
                        }
                    }
                    if ($account->type == "tiktok" && !empty($image)) {
                        $tiktok = Tiktok::where("id", $account->id)->firstOrFail();
                        if ($tiktok) {
                            $localImage = saveImageFromUrl($image, 'uploads');
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
                                "image" => $localImage,
                                "status" => 0,
                                "publish_date" => $scheduleDateTime,
                            ];
                            $post = PostService::create($data);

                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                        }
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Your posts are scheduled for Later!"
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

    public function getQueueSettings(Request $request)
    {
        $user = User::with("boards.pinterest", "pages.facebook", "tiktok")->find(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        $view = view("user.schedule.partials.queue-settings-list", compact("accounts"));
        return response()->json([
            "success" => true,
            "data" => $view->render()
        ]);
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

    /**
     * Update shuffle status via route. Accepts account_id and schedule_shuffle (0 or 1).
     * Only handles shuffle - nothing else.
     */
    public function updateShuffleStatus(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer',
            'schedule_shuffle' => 'required|integer|in:0,1',
        ]);

        $user = Auth::guard('user')->user();
        $page = Page::where('id', $request->account_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->updateScheduleShuffleStatus($page, (int) $request->schedule_shuffle, $user);

        return response()->json([
            'success' => true,
            'message' => 'Shuffle status updated successfully.',
        ]);
    }

    /**
     * Update schedule shuffle status and shuffle posts when enabled.
     * Only handles shuffle - nothing else.
     */
    private function updateScheduleShuffleStatus(Page $page, int $scheduleShuffle, User $user): void
    {
        $page->update(['schedule_shuffle' => $scheduleShuffle]);

        if ($scheduleShuffle !== 1) {
            return;
        }

        $postsToShuffle = Post::where('user_id', $user->id)
            ->where('account_id', $page->id)
            ->where('social_type', 'like', '%facebook%')
            ->where('status', 0)
            ->where('source', 'schedule')
            ->get();

        if ($postsToShuffle->count() >= 2) {
            $publishDates = $postsToShuffle->pluck('publish_date')->shuffle()->values();
            DB::transaction(function () use ($postsToShuffle, $publishDates) {
                foreach ($postsToShuffle as $index => $post) {
                    $post->update(['publish_date' => $publishDates[$index] ?? $post->publish_date]);
                }
            });
        }
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
                        $posts = Post::with('user.timezone')
                            ->where('account_id', $accountId)
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

                                    // Assign post to this timeslot on current date (convert to UTC for storage)
                                    $publishDateTimeLocal = $currentScheduleDate . ' ' . $timeslot24Hour;
                                    $post->update([
                                        'publish_date' => TimezoneService::toUtc($publishDateTimeLocal, $user)
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

                                        // Timeslot is available for this date, assign post (convert to UTC for storage)
                                        $publishDateTimeLocal = $currentScheduleDate . ' ' . $timeslot24Hour;
                                        $post->update([
                                            'publish_date' => TimezoneService::toUtc($publishDateTimeLocal, $user)
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

    /**
     * Listing filter is exactly one platform: Pinterest (used to allow full sent list without pagination).
     */
    private function requestIsPinterestOnlyListingTypes(Request $request): bool
    {
        $types = array_values(array_filter(
            (array) $request->input('type', []),
            fn ($t) => $t !== null && $t !== ''
        ));

        return count($types) === 1 && (string) $types[0] === 'pinterest';
    }

    /**
     * Sent-tab window for rows sourced from posts table.
     */
    private function sentPostsRecentCutoffUtc(): Carbon
    {
        return Carbon::now('UTC')->subHours(3);
    }

    public function postsListing(Request $request)
    {
        $data = $request->all();
        $posts = Post::with("page.facebook", "board.pinterest", "user.timezone")->isScheduled();

        if (!empty($request->account_id)) {
            $posts = $posts->whereIn("account_id", $request->account_id);
        }
        if (!empty($request->type)) {
            $posts = $posts->whereIn("social_type", $request->type);
        }
        if (!empty($request->post_type)) {
            $posts = $posts->whereIn("type", $request->post_type);
        }

        $tab = $request->input('post_status_tab');
        if ($tab === 'sent') {
            $posts = $posts->where("status", 1);
            $posts = $posts->where('published_at', '>=', $this->sentPostsRecentCutoffUtc());
        } elseif ($tab === 'failed') {
            $posts = $posts->where("status", -1);
        } elseif ($tab === 'queue') {
            $posts = $posts->where("status", 0);
        } elseif ($request->has('status')) {
            $posts = $posts->where("status", $request->status);
        }

        $totalRecordswithFilter = clone $posts;

        $sortDir = ($tab === 'sent' || $tab === 'failed') ? 'desc' : 'asc';
        $sortCol = ($tab === 'sent') ? 'published_at' : 'publish_date';

        $loadAllPinterestSent = $request->boolean('sent_load_all')
            && $tab === 'sent'
            && $this->requestIsPinterestOnlyListingTypes($request);

        if ($loadAllPinterestSent) {
            $posts = (clone $totalRecordswithFilter)->orderBy($sortCol, $sortDir)->get();
        } else {
            $posts = (clone $totalRecordswithFilter)
                ->offset(intval($data['start'] ?? 0))
                ->limit(intval($data['length'] ?? 9))
                ->orderBy($sortCol, $sortDir)
                ->get();
        }

        $posts->append(["post_details", "account_detail", "publish_datetime", "status_view", "action", "account_name", "account_profile", "published_at_formatted", "facebook_post_url"]);
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
            $post = Post::find($request->id);

            if ($post) {
                $user = User::findOrFail($post->user_id);
                // Decrement feature usage if this is a scheduled post and account belongs to user
                if ($post->source === 'schedule' && $this->verifyPostAccountBelongsToUser($post, $user)) {
                    $user->decrementFeatureUsage('scheduled_posts_per_account', 1);
                }

                $post->photo()->delete();
                PostService::delete($post->id);
            } else {
                // Post not in database - delete from Facebook page only
                $facebookPostId = $request->facebook_post_id;
                $pageId = $request->page_id;
                if ($facebookPostId && $pageId) {
                    $page = Page::where('id', $pageId)->where('user_id', Auth::guard('user')->id())->first();
                    if ($page) {
                        DeleteFacebookPostJob::dispatch($facebookPostId, (int) $pageId, null);
                    }
                }
            }

            $response = [
                "success" => true,
                "message" => "Post deleted successfully!"
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
            $post = Post::with('user')->findOrFail($id);
            $publishDateTimeLocal = date("Y-m-d", strtotime($request->edit_post_publish_date)) . " " . date("H:i", strtotime($request->edit_post_publish_time));
            $data = [
                "title" => $request->edit_post_title,
                "url" => $request->edit_post_link,
                "comment" => $request->edit_post_comment,
                "publish_date" => TimezoneService::toUtc($publishDateTimeLocal, $post->user),
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

    public function postUpdateComment($id, Request $request)
    {
        try {
            $post = Post::findOrFail($id);
            $post->update(['comment' => $request->input('comment', '')]);
            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function postPublishNow(Request $request)
    {
        $response = PostService::publishNow($request->id);
        return response()->json($response);
    }

    public function postsStatusCounts(Request $request)
    {
        $accountIds = $request->input('account_id', []);
        $accountTypes = $request->input('type', []);
        $soruce = $request->input('source', 'schedule');

        if (empty($accountIds)) {
            return response()->json(['queue' => 0, 'sent' => 0, 'failed' => 0]);
        }

        $base = Post::withoutGlobalScopes()
            ->whereIn('account_id', (array) $accountIds);
        if ($soruce) {
            $base->where('source', $soruce);
        }

        $user = Auth::guard('user')->user();
        $userTz = TimezoneService::getUserTimezone($user);
        $nowUtc = Carbon::now($userTz)->setTimezone('UTC')->format('Y-m-d H:i:s');

        $queue = (clone $base)->where('status', 0)->where('publish_date', '>=', $nowUtc)->count();
        $sent = (clone $base)->where('status', 1)->count();

        return response()->json(['queue' => $queue, 'sent' => $sent]);
    }

    public function getTimeslots(Request $request)
    {
        $accountId = $request->input('account_id');
        $accountType = $request->input('type');

        if (!$accountId || !$accountType) {
            return response()->json(['timeslots' => []]);
        }

        $timeslots = Timeslot::where('account_id', $accountId)
            ->where('account_type', $accountType)
            ->pluck('timeslot')
            ->sort()
            ->values()
            ->map(function ($slot) {
                return date('h:i A', strtotime($slot));
            });

        return response()->json(['timeslots' => $timeslots]);
    }

    public function getQueueTimeline(Request $request)
    {
        $accountId = $request->input('account_id');
        $accountType = $request->input('account_type') ?? $request->input('type');
        $days = (int) $request->input('days', 14);
        $offset = (int) $request->input('offset', 0);
        $source = $request->input('source', 'schedule');

        if (!$accountId || !$accountType) {
            return response()->json(['success' => false, 'message' => 'Account required']);
        }

        $user = Auth::guard('user')->user();
        $userTz = TimezoneService::getUserTimezone($user);

        $timeslots = Timeslot::where('account_id', $accountId)
            ->where('account_type', $accountType)
            ->where('type', 'schedule')
            ->pluck('timeslot')
            ->sort()
            ->values()
            ->toArray();

        $socialType = $accountType === 'pinterest' ? 'pinterest' : ($accountType === 'tiktok' ? 'tiktok' : 'facebook');

        $startDate = Carbon::now($userTz)->addDays($offset)->startOfDay();
        $endDate = $startDate->copy()->addDays($days - 1)->endOfDay();

        $postsQuery = Post::with('page.facebook', 'board.pinterest', 'tiktok')
            ->where('user_id', $user->id)
            ->where('account_id', $accountId)
            ->where('social_type', 'like', "%{$socialType}%")
            ->where('status', 0)
            ->whereBetween('publish_date', [
                $startDate->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'),
                $endDate->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'),
            ]);
        if ($source) {
            $postsQuery->where('source', $source);
        }
        $existingPostsByKey = $postsQuery->get()->groupBy(function ($post) use ($userTz) {
            return Carbon::parse($post->publish_date, 'UTC')->setTimezone($userTz)->format('Y-m-d H:i');
        });

        $timeline = [];
        $now = Carbon::now($userTz);

        $formatPostForSlot = function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'comment' => $post->comment,
                'description' => $post->description,
                'url' => $post->url,
                'type' => $post->type,
                'status' => (int) $post->status,
                'image' => $post->image ? (str_starts_with($post->image, 'http') ? $post->image : asset($post->image)) : null,
                'account_name' => $post->account_name ?? ucfirst($post->social_type),
                'account_profile' => $post->account_profile ? (str_starts_with($post->account_profile, 'http') ? $post->account_profile : asset($post->account_profile)) : null,
                'social_type' => $post->social_type,
                'created_at' => $post->created_at ? Carbon::parse($post->created_at)->diffForHumans() : null,
            ];
        };

        for ($d = 0; $d < $days; $d++) {
            $date = $startDate->copy()->addDays($d);
            $dateStr = $date->format('Y-m-d');
            $daySlots = [];
            $placedKeys = [];

            foreach ($timeslots ?: [] as $slot) {
                $slotNormalized = date('H:i', strtotime($slot));
                $slotTime = Carbon::parse($dateStr . ' ' . $slotNormalized, $userTz);

                if ($date->isToday() && $slotTime->lt($now)) {
                    continue;
                }

                $key = $slotTime->format('Y-m-d H:i');
                $posts = $existingPostsByKey->get($key, collect());
                if ($posts->isNotEmpty()) {
                    $placedKeys[$key] = true;
                }

                $daySlots[] = [
                    'time' => $slotTime->format('H:i'),
                    'time_display' => $slotTime->format('h:i A'),
                    'datetime_utc' => $slotTime->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'),
                    'has_post' => $posts->isNotEmpty(),
                    'posts' => $posts->map($formatPostForSlot)->values()->toArray(),
                ];
            }

            // Generate virtual slots for posts that don't match any configured timeslot
            foreach ($existingPostsByKey as $key => $posts) {
                if (str_starts_with($key, $dateStr . ' ') && !isset($placedKeys[$key])) {
                    $slotTime = Carbon::parse($key, $userTz);
                    if ($date->isToday() && $slotTime->lt($now)) {
                        continue;
                    }
                    $daySlots[] = [
                        'time' => $slotTime->format('H:i'),
                        'time_display' => $slotTime->format('h:i A'),
                        'datetime_utc' => $slotTime->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'),
                        'has_post' => true,
                        'posts' => $posts->map($formatPostForSlot)->values()->toArray(),
                    ];
                }
            }

            usort($daySlots, fn($a, $b) => strcmp($a['time'], $b['time']));

            if (!empty($daySlots)) {
                $isToday = $date->isToday();
                $isTomorrow = $date->isTomorrow();
                $label = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $date->format('l'));

                $timeline[] = [
                    'date' => $date->format('Y-m-d'),
                    'label' => $label,
                    'date_display' => $date->format('F j'),
                    'slots' => $daySlots,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
            'has_more' => true,
            'next_offset' => $offset + $days,
        ]);
    }

    public function getPageSentPosts(Request $request)
    {
        $userId = (int) Auth::id();
        $accountIds = (array) $request->input('account_id', []);
        if (empty($accountIds)) {
            return response()->json(['success' => false, 'message' => 'No account selected', 'posts' => []]);
        }

        $pages = Page::whereIn('id', $accountIds)->get();
        if ($pages->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No pages found', 'posts' => []]);
        }

        // Sent tab (beta): full_year only — since = 1 year ago, until = today
        $until = now()->format('Y-m-d');
        $since = now()->subYear()->format('Y-m-d');
        $duration = 'full_year';

        $allPosts = [];
        $graphPostIds = [];

        foreach ($pages as $page) {
            $cacheKey = $this->sentPostsCacheKey($userId, (int) $page->id, $duration, $since, $until);
            $posts = Cache::get($cacheKey);
            if ($posts === null) {
                $posts = $this->fetchPagePostsFromStore($page, $since, $until, $duration);
                if ($posts !== null) {
                    Cache::put($cacheKey, $posts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));
                }
            }
            if (!is_array($posts)) {
                $posts = [];
            }

            // Avoid N+1 queries: preload our matching posts for this page response.
            $postIds = collect($posts)
                ->pluck('id')
                ->filter()
                ->map(fn($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            $ourPostsByExternalId = empty($postIds)
                ? collect()
                : Post::where('user_id', $userId)
                    ->whereIn('post_id', $postIds)
                    ->with('user')
                    ->get()
                    ->keyBy(fn($p) => (string) $p->post_id);

            foreach ($posts as &$post) {
                $post['account_name'] = $page->name;
                $post['account_profile'] = $page->profile_image;
                $post['social_type'] = 'facebook';
                $post['page_db_id'] = $page->id;
                $ourPost = $ourPostsByExternalId->get((string) ($post['id'] ?? ''));
                if ($ourPost) {
                    $post['db_post_id'] = $ourPost->id;
                    if ($ourPost->user) {
                        $post['publisher_username'] = $ourPost->user->username ?? $ourPost->user->full_name ?? $ourPost->user->email ?? '';
                        $post['publisher_email'] = $ourPost->user->email ?? '';
                    }
                }
            }
            unset($post);

            foreach ($posts as $p) {
                if (!empty($p['id'])) {
                    $graphPostIds[(string) $p['id']] = true;
                }
            }

            $allPosts = array_merge($allPosts, $posts);
        }

        // Posts published via the app but not yet present in PagePost / Graph cache: show from DB with zeroed metrics.
        $sinceStart = Carbon::parse($since)->startOfDay();
        $untilEnd = Carbon::parse($until)->endOfDay();
        $pageIds = $pages->pluck('id')->all();
        $pagesById = $pages->keyBy('id');

        $dbSentPosts = Post::query()
            ->where('user_id', $userId)
            ->where('social_type', 'facebook')
            ->whereIn('account_id', $pageIds)
            ->where('status', 1)
            ->whereNotNull('post_id')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $this->sentPostsRecentCutoffUtc())
            ->whereBetween('published_at', [$sinceStart, $untilEnd])
            ->with('user', 'page')
            ->get();

        foreach ($dbSentPosts as $dbPost) {
            $extId = (string) $dbPost->post_id;
            if ($extId === '' || isset($graphPostIds[$extId])) {
                continue;
            }
            $page = $pagesById->get($dbPost->account_id);
            if (!$page) {
                continue;
            }
            $graphPostIds[$extId] = true;
            $allPosts[] = $this->sentFacebookPostFromLocalRecord($dbPost, $page);
        }

        usort($allPosts, function ($a, $b) {
            $ta = $this->parseCreatedTime($a['created_time'] ?? null);
            $tb = $this->parseCreatedTime($b['created_time'] ?? null);
            return $tb - $ta;
        });

        return response()->json(['success' => true, 'posts' => $allPosts]);
    }

    /**
     * Pinterest Sent tab: all published schedule pins for selected boards, same card shape as Facebook sent API.
     */
    public function getPinterestSentPosts(Request $request)
    {
        $userId = (int) Auth::id();
        $accountIds = (array) $request->input('account_id', []);
        if (empty($accountIds)) {
            return response()->json(['success' => false, 'message' => 'No account selected', 'posts' => []]);
        }

        $boards = Board::whereIn('id', $accountIds)->where('user_id', $userId)->get();
        if ($boards->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No boards found', 'posts' => []]);
        }

        $boardIds = $boards->pluck('id')->all();
        $boardsById = $boards->keyBy('id');

        $dbPosts = Post::query()
            ->where('user_id', $userId)
            ->where('social_type', 'like', '%pinterest%')
            ->whereIn('account_id', $boardIds)
            ->where('status', 1)
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $this->sentPostsRecentCutoffUtc())
            ->where('source', 'schedule')
            ->with('user', 'board.pinterest')
            ->orderByDesc('published_at')
            ->get();

        $allPosts = [];
        foreach ($dbPosts as $dbPost) {
            $board = $boardsById->get($dbPost->account_id) ?? $dbPost->board;
            if (!$board) {
                continue;
            }
            $allPosts[] = $this->sentTabPostFromPinterestRecord($dbPost, $board);
        }

        return response()->json(['success' => true, 'posts' => $allPosts]);
    }

    /**
     * Minimal Sent-tab payload from a published Pinterest Post row (metrics zeroed; same keys as Facebook sent cards).
     */
    private function sentTabPostFromPinterestRecord(Post $dbPost, Board $board): array
    {
        $published = Carbon::parse($dbPost->published_at);
        $createdTime = $published->toIso8601String();

        $fullPicture = '';
        if (!empty($dbPost->image)) {
            $img = $dbPost->image;
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                $fullPicture = $img;
            } else {
                $fullPicture = fetchFromS3($img);
            }
        }

        $pinId = $dbPost->post_id ? (string) $dbPost->post_id : '';
        $permalink = $pinId !== ''
            ? 'https://www.pinterest.com/pin/' . rawurlencode($pinId) . '/'
            : null;

        $profileImage = $board->pinterest?->profile_image ?? '';

        $payload = [
            'id' => $pinId !== '' ? $pinId : ('db-' . $dbPost->id),
            'created_time' => $createdTime,
            'message' => (string) ($dbPost->title ?? ''),
            'story' => '',
            'full_picture' => $fullPicture,
            'permalink_url' => $permalink,
            'account_name' => $board->name,
            'account_profile' => $profileImage,
            'social_type' => 'pinterest',
            'page_db_id' => $board->id,
            'db_post_id' => $dbPost->id,
            'insights' => [
                'post_reactions' => 0,
                'post_impressions' => 0,
                'post_clicks' => 0,
            ],
            'comments' => 0,
            'shares' => 0,
            'from_local_db' => true,
        ];

        if ($dbPost->user) {
            $payload['publisher_username'] = $dbPost->user->username ?? $dbPost->user->full_name ?? $dbPost->user->email ?? '';
            $payload['publisher_email'] = $dbPost->user->email ?? '';
        }

        return $payload;
    }

    /**
     * Minimal Sent-tab payload from a published Post row (metrics zeroed until Graph sync / insights fetch).
     */
    private function sentFacebookPostFromLocalRecord(Post $dbPost, Page $page): array
    {
        $published = Carbon::parse($dbPost->published_at);
        $createdTime = $published->toIso8601String();

        $fullPicture = '';
        if (!empty($dbPost->image)) {
            $img = $dbPost->image;
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                $fullPicture = $img;
            } else {
                $fullPicture = fetchFromS3($img);
            }
        }

        $payload = [
            'id' => $dbPost->post_id,
            'created_time' => $createdTime,
            'message' => (string) ($dbPost->title ?? ''),
            'story' => '',
            // Used by the UI to show Reel/Story badges in the Sent tab.
            // Expected values from scheduled Facebook posts: post/photo, video, reel, story, etc.
            'type' => (string) ($dbPost->type ?? ''),
            'full_picture' => $fullPicture,
            'permalink_url' => $dbPost->facebook_post_url,
            'account_name' => $page->name,
            'account_profile' => $page->profile_image,
            'social_type' => 'facebook',
            'page_db_id' => $page->id,
            'db_post_id' => $dbPost->id,
            'insights' => [
                'post_reactions' => 0,
                'post_impressions' => 0,
                'post_clicks' => 0,
            ],
            'comments' => 0,
            'shares' => 0,
            'from_local_db' => true,
        ];

        if ($dbPost->user) {
            $payload['publisher_username'] = $dbPost->user->username ?? $dbPost->user->full_name ?? $dbPost->user->email ?? '';
            $payload['publisher_email'] = $dbPost->user->email ?? '';
        }

        return $payload;
    }

    private function sentPostsCacheKey(int $userId, int $pageId, string $duration, ?string $since, ?string $until): string
    {
        return implode(':', [
            'schedule_sent_posts',
            'v1',
            'user',
            $userId,
            'page',
            $pageId,
            'duration',
            $duration,
            'since',
            (string) ($since ?? ''),
            'until',
            (string) ($until ?? ''),
        ]);
    }

    private function fetchPagePostsFromStore(Page $page, string $since, string $until, string $duration = 'full_year'): ?array
    {
        if (empty($page->page_id) || empty($page->access_token)) {
            return null;
        }

        $stored = PagePost::where('page_id', $page->id)
            ->where('since', $since)
            ->where('until', $until)
            ->first();

        if ($stored && $stored->posts !== null) {
            return $stored->posts;
        }

        $tokenCheck = FacebookService::validateToken($page);
        if (!$tokenCheck['success']) {
            return null;
        }

        $accessToken = $tokenCheck['access_token'] ?? $page->access_token;
        $facebookService = new FacebookService();
        $insightsPreset = $duration === 'full_year' ? 'sent_tab' : 'default';
        $posts = $facebookService->getPagePostsWithInsights($page->page_id, $accessToken, $since, $until, $insightsPreset);

        PagePost::updateOrCreate(
            [
                'page_id' => $page->id,
                'since' => $since,
                'until' => $until,
            ],
            [
                'duration' => $duration,
                'posts' => $posts,
                'synced_at' => now(),
            ]
        );

        return $posts;
    }

    private function parseCreatedTime($value): int
    {
        if (is_string($value)) {
            return strtotime($value) ?: 0;
        }
        if (is_array($value) && isset($value['date'])) {
            return strtotime($value['date']) ?: 0;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }
        return 0;
    }

    /**
     * Refresh posts and post insights for the selected Facebook account (page).
     * Used by the schedule page "Refresh" button. Creates a success notification when done.
     */
    public function refreshPagePosts(Request $request, PagePostsSyncService $pagePostsSyncService)
    {
        $accountId = $request->input('account_id');
        $type = $request->input('type', 'facebook');

        if (empty($accountId) || $type !== 'facebook') {
            return response()->json(['success' => false, 'message' => 'Please select a Facebook account.'], 400);
        }

        $user = Auth::user();
        $page = Page::where('id', $accountId)->where('user_id', $user->id)->first();

        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Account not found or access denied.'], 404);
        }

        $result = $pagePostsSyncService->syncPageForFullYear($page);


        if ($result['success'] || $result['synced'] > 0) {
            $accountName = $page->name ?? 'Facebook account';
            $profileImage = $page->profile_image ?? null;
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Posts and insights synced',
                'body' => [
                    'type' => 'success',
                    'message' => 'Posts and insights have been synced for ' . $accountName . '.',
                    'social_type' => 'facebook',
                    'account_image' => $profileImage,
                    'account_name' => $accountName,
                    'account_username' => $page->facebook?->username ?? '',
                ],
                'is_read' => false,
                'is_system' => false,
            ]);
        }

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Posts and insights synced successfully.',
                'account_name' => $page->name,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Sync completed with some failures.',
            'synced' => $result['synced'],
            'failed' => $result['failed'],
        ], 422);
    }

    /**
     * Delete a sent post from Facebook and from DB (in background).
     * Returns success immediately; actual delete runs in a job.
     * Expects: id = Facebook post id, page_id = our Page (account) id.
     */
    public function deleteSentPost(Request $request)
    {
        $postId = $request->input('id'); // Facebook post id
        $pageId = $request->input('page_id');

        if (empty($postId) || empty($pageId)) {
            return response()->json(['success' => false, 'message' => 'Post id and page id are required.'], 400);
        }

        $user = Auth::user();
        $page = Page::where('id', $pageId)->where('user_id', $user->id)->first();

        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Account not found or access denied.'], 404);
        }

        DeleteSentPostJob::dispatch($postId, (int) $pageId);

        return response()->json(['success' => true, 'message' => 'Post deleted successfully.']);
    }
}
