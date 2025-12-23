<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Board;
use App\Models\Domain;
use App\Models\Tiktok;
use App\Models\Feature;
use App\Models\Facebook;
use App\Models\Pinterest;
use Illuminate\Http\Request;
use App\Services\TikTokService;
use App\Services\FacebookService;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\FeatureUsageService;

class AccountsController extends Controller
{
    private $pinterestService;
    private $facebookService;
    private $tiktokService;
    private $featureUsageService;
    private $pinterest;
    private $facebook;
    private $tiktok;
    private $board;
    private $page;
    private $post;
    private $domain;
    public function __construct(Pinterest $pinterest, Facebook $facebook, Tiktok $tiktok, Board $board, Page $page, Post $post, Domain $domain, FeatureUsageService $featureUsageService)
    {
        $this->pinterestService = new PinterestService();
        $this->facebookService = new FacebookService();
        $this->tiktokService = new TikTokService();
        $this->featureUsageService = $featureUsageService;
        $this->pinterest = $pinterest;
        $this->facebook = $facebook;
        $this->tiktok = $tiktok;
        $this->board = $board;
        $this->page = $page;
        $this->post = $post;
        $this->domain = $domain;
    }
    public function index()
    {
        $user = User::with("facebook", "pinterest", "tiktok")->findOrFail(Auth::guard('user')->id());
        $facebookUrl = $this->facebookService->getLoginUrl();
        $pinterestUrl = $this->pinterestService->getLoginUrl();
        $tiktokUrl = $this->tiktokService->getLoginUrl();
        return view("user.accounts.index", compact("user", "facebookUrl", "pinterestUrl", "tiktokUrl"));
    }

    public function pinterestDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::guard('user')->user();
            $pinterest = $this->pinterest->search($id)->first();
            if ($pinterest && $pinterest->user_id === $user->id) {
                $board_ids = $pinterest->boards()->where('user_id', $user->id)->get()->pluck("board_id")->toArray();
                $totalScheduledPosts = 0;
                $totalBoards = 0;

                // posts,domains,timeslots,board
                foreach ($board_ids  as $board_id) {
                    $board = $this->board->find($board_id);
                    if ($board && $board->user_id === $user->id) {
                        // Count scheduled posts before deletion
                        $scheduledPostsCount = $board->posts()->where('source', 'schedule')->count();
                        $totalScheduledPosts += $scheduledPostsCount;
                        $totalBoards++;

                        $board->posts()->delete();
                        $board->domains()->delete();
                        $board->timeslots()->delete();
                        $board->delete();
                    }
                }

                // Decrement feature usage for scheduled posts
                if ($totalScheduledPosts > 0) {
                    /** @var User $user */
                    $user->decrementFeatureUsage('scheduled_posts_per_account', $totalScheduledPosts);
                }
                
                // Decrement feature usage for social_accounts (Pinterest account + all boards)
                /** @var User $user */
                $user->decrementFeatureUsage(Feature::$features_list[0], 1 + $totalBoards);

                // pinterest account
                $pinterest->delete();
                return back()->with("success", "Pinterest Account deleted Successfully!");
            } else {
                return back()->with("error", "Something went Wrong!");
            }
        } else {
            return back()->with("error", "Something went Wrong!");
        }
    }

    public function pinterest($id = null)
    {
        if (!empty($id)) {
            $pinterestUrl = $this->pinterestService->getLoginUrl();
            $pinterest = $this->pinterest->search($id)->firstOrFail();
            if ($pinterest) {
                return view('user.accounts.pinterest', compact('pinterestUrl', 'pinterest'));
            } else {
                return back()->with('error', 'Something went Wrong!');
            }
        } else {
            return back()->with('error', 'Something went Wrong!');
        }
    }

    public function addBoard(Request $request)
    {
        try {
            $user = User::find(Auth::guard('user')->id());

            // Check and track feature usage for social_accounts
            $result = $this->featureUsageService->checkAndIncrement($user, Feature::$features_list[0], 1);

            if (!$result['allowed']) {
                return response()->json([
                    "success" => false,
                    "message" => $result['message'],
                    "usage" => $result['usage'],
                    "limit" => $result['limit'],
                    "remaining" => $result['remaining']
                ]);
            }

            $board = $request->board_data;
            if ($board) {
                $pinterest = $this->pinterest->search($request->pin_id)->firstOrfail();
                $pinterest->boards()->updateOrCreate(["user_id" => $user->id, "board_id" => $board["id"]], [
                    "name" => $board["name"],
                    "status" => 1
                ]);
                return response()->json([
                    "success" => true,
                    "message" => "Board connected Successfully!",
                    "usage" => $result['usage'],
                    "remaining" => $result['remaining']
                ]);
            } else {
                // Rollback usage if board creation fails
                $user->decrementFeatureUsage(Feature::$features_list[0], 1);
                return response()->json(["success" => false, "message" => "Something went Wrong!"]);
            }
        } catch (Exception $e) {
            // Rollback usage on error
            if (isset($user)) {
                $user->decrementFeatureUsage(Feature::$features_list[0], 1);
            }
            return response()->json(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function boardDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::guard('user')->user();
            $board = $this->board->search($id)->first();
            if ($board && $board->user_id === $user->id) {
                // Count scheduled posts before deletion
                $scheduledPostsCount = $board->posts()->where('source', 'schedule')->count();

                // posts
                $board->posts()->delete();
                // domains
                $board->domains()->delete();
                // timeslots
                $board->timeslots()->delete();
                // board
                $board->delete();

                // Decrement feature usage for scheduled posts
                if ($scheduledPostsCount > 0) {
                    /** @var User $user */
                    $user->decrementFeatureUsage('scheduled_posts_per_account', $scheduledPostsCount);
                }
                
                // Decrement feature usage for social_accounts
                /** @var User $user */
                $user->decrementFeatureUsage(Feature::$features_list[0], 1);

                return back()->with("success", "Board deleted Successfully!");
            } else {
                return back()->with('error', 'Something went Wrong!');
            }
        } else {
            return back()->with('error', 'Something went Wrong!');
        }
    }

    public function facebookDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::guard('user')->user();
            $facebook = $this->facebook->search($id)->first();
            if ($facebook && $facebook->user_id === $user->id) {
                $page_ids = Page::where('fb_id', $facebook->id)->pluck('id')->toArray();
                $totalScheduledPosts = 0;
                $totalPages = 0;

                // posts,domains,timeslots,page
                foreach ($page_ids as $page_id) {
                    $page = Page::find($page_id);
                    if ($page && $page->user_id === $user->id) {
                        // Count scheduled posts before deletion
                        $scheduledPostsCount = $page->posts()->where('source', 'schedule')->count();
                        $totalScheduledPosts += $scheduledPostsCount;
                        $totalPages++;

                        $page->posts()->delete();
                        $page->domains()->delete();
                        $page->timeslots()->delete();
                        $page->delete();
                    }
                }

                // Decrement feature usage for scheduled posts
                if ($totalScheduledPosts > 0) {
                    /** @var User $user */
                    $user->decrementFeatureUsage('scheduled_posts_per_account', $totalScheduledPosts);
                }
                
                // Decrement feature usage for social_accounts (Facebook account + all pages)
                /** @var User $user */
                $user->decrementFeatureUsage(Feature::$features_list[0], 1 + $totalPages);

                // Delete Facebook account
                $facebook->delete();

                return back()->with("success", "Facebook Account deleted Successfully!");
            } else {
                return back()->with("error", "Something went Wrong!");
            }
        } else {
            return back()->with("error", "Something went Wrong!");
        }
    }

    public function facebook($id = null)
    {
        if (!empty($id)) {
            $facebookUrl = $this->facebookService->getLoginUrl();
            $facebook = $this->facebook->search($id)->first();
            if ($facebook) {
                return view('user.accounts.facebook', compact('facebookUrl', 'facebook'));
            } else {
                return back()->with('error', 'Something went Wrong!');
            }
        } else {
            return back()->with('error', 'Something went Wrong!');
        }
    }

    public function addPage(Request $request)
    {
        $page = $request->page_data;
        if ($page) {
            try {
                $user = User::find(Auth::guard('user')->id());

                // Check and track feature usage for social_accounts
                $result = $this->featureUsageService->checkAndIncrement($user, Feature::$features_list[0], 1);

                if (!$result['allowed']) {
                    return response()->json([
                        "success" => false,
                        "message" => $result['message'],
                        "usage" => $result['usage'],
                        "limit" => $result['limit'],
                        "remaining" => $result['remaining']
                    ]);
                }

                $facebook = $this->facebook->search($request->fb_id)->first();

                // Handle profile image - download if needed
                $profileImage = null;

                // If profile_image already exists in page_data as a filename, use it
                // (profile_image is already downloaded and saved when pages are fetched)
                if (!empty($page["profile_image"])) {
                    $profileImage = $page["profile_image"];
                } else {
                    // Fetch profile image from Facebook API and download it as fallback
                    $profileImageResponse = $this->facebookService->pageProfileImage($page["access_token"], $page["id"]);
                    if ($profileImageResponse["success"]) {
                        $profileImageData = $profileImageResponse["data"];
                        $profileImageUrl = $profileImageData->getField("url");

                        // Download the image
                        if ($profileImageUrl) {
                            $profileImage = saveImageFromUrl($profileImageUrl);
                        }
                    }
                }

                $pageData = [
                    "name" => $page["name"],
                    "status" => 1,
                    "access_token" => $page["access_token"],
                    "expires_in" => time()
                ];

                // Add profile_image if we have it
                if ($profileImage) {
                    $pageData["profile_image"] = $profileImage;
                }

                $facebook->pages()->updateOrCreate(["user_id" => $user->id, "page_id" => $page["id"]], $pageData);

                return response()->json([
                    "success" => true,
                    "message" => "Page connected Successfully!",
                    "usage" => $result['usage'],
                    "remaining" => $result['remaining']
                ]);
            } catch (Exception $e) {
                // Rollback usage on error
                if (isset($user)) {
                    $user->decrementFeatureUsage(Feature::$features_list[0], 1);
                }
                return response()->json(["success" => false, "message" => "Failed to connect page: " . $e->getMessage()]);
            }
        } else {
            return response()->json(["success" => false, "message" => "Something went Wrong!"]);
        }
    }

    public function pageDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::guard('user')->user();
            $page = $this->page->search($id)->first();
            if ($page && $page->user_id === $user->id) {
                // Count scheduled posts before deletion
                $scheduledPostsCount = $page->posts()->where('source', 'schedule')->count();

                // posts
                $page->posts()->delete();
                // domains
                $page->domains()->delete();
                // timeslots
                $page->timeslots()->delete();
                // page
                $page->delete();

                // Decrement feature usage for scheduled posts
                if ($scheduledPostsCount > 0) {
                    /** @var User $user */
                    $user->decrementFeatureUsage('scheduled_posts_per_account', $scheduledPostsCount);
                }
                
                // Decrement feature usage for social_accounts
                /** @var User $user */
                $user->decrementFeatureUsage(Feature::$features_list[0], 1);

                return back()->with("success", "Page deleted Successfully!");
            } else {
                return back()->with('error', 'Something went Wrong!');
            }
        } else {
            return back()->with('error', 'Something went Wrong!');
        }
    }

    /**
     * Toggle RSS pause status for a page or board
     */
    public function toggleRssPause(Request $request)
    {
        try {
            $type = $request->type;
            $id = $request->id;

            if ($type === 'facebook') {
                $page = $this->page->findOrFail($id);
                $page->rss_paused = !$page->rss_paused;
                $page->save();

                return response()->json([
                    "success" => true,
                    "message" => $page->rss_paused
                        ? "RSS automation paused for this page."
                        : "RSS automation resumed for this page.",
                    "paused" => $page->rss_paused
                ]);
            } elseif ($type === 'pinterest') {
                $board = $this->board->findOrFail($id);
                $board->rss_paused = !$board->rss_paused;
                $board->save();

                return response()->json([
                    "success" => true,
                    "message" => $board->rss_paused
                        ? "RSS automation paused for this board."
                        : "RSS automation resumed for this board.",
                    "paused" => $board->rss_paused
                ]);
            }

            return response()->json([
                "success" => false,
                "message" => "Invalid account type."
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    public function tiktok($id = null)
    {
        if (!empty($id)) {
            $tiktokUrl = $this->tiktokService->getLoginUrl();
            $tiktok = $this->tiktok->search($id)->first();
            if ($tiktok) {
                return view('user.accounts.tiktok', compact('tiktokUrl', 'tiktok'));
            } else {
                return back()->with('error', 'Something went Wrong!');
            }
        } else {
            return back()->with('error', 'Something went Wrong!');
        }
    }

    public function tiktokDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::guard('user')->user();
            $tiktok = $this->tiktok->search($id)->first();
            if ($tiktok && $tiktok->user_id === $user->id) {
                // Count scheduled posts before deletion
                $scheduledPostsCount = Post::where('account_id', $tiktok->id)
                    ->where('social_type', 'tiktok')
                    ->where('source', 'schedule')
                    ->where('user_id', $user->id)
                    ->count();

                // Delete all posts for this TikTok account
                Post::where('account_id', $tiktok->id)
                    ->where('social_type', 'tiktok')
                    ->where('user_id', $user->id)
                    ->delete();

                // TikTok account
                $tiktok->delete();

                // Decrement feature usage for scheduled posts
                if ($scheduledPostsCount > 0) {
                    /** @var User $user */
                    $user->decrementFeatureUsage('scheduled_posts_per_account', $scheduledPostsCount);
                }
                
                // Decrement feature usage for social_accounts
                /** @var User $user */
                $user->decrementFeatureUsage(Feature::$features_list[0], 1);

                return back()->with("success", "TikTok Account deleted Successfully!");
            } else {
                return back()->with("error", "Something went Wrong!");
            }
        } else {
            return back()->with("error", "Something went Wrong!");
        }
    }
}
