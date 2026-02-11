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
use App\Models\Timeslot;
use App\Models\Pinterest;
use Illuminate\Http\Request;
use App\Services\TikTokService;
use App\Services\FacebookService;
use App\Services\PinterestService;
use App\Services\PostService;
use App\Services\SocialMediaLogService;
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
    private $logService;
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
        $this->logService = new SocialMediaLogService();
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
            $user = User::find(Auth::guard('user')->id());
            $pinterest = $this->pinterest->search($id)->first();
            if ($pinterest) {
                $boards = Board::where("pin_id", $pinterest->id)->get();
                $totalScheduledPosts = 0;
                $totalBoards = 0;

                // posts,domains,timeslots,board
                foreach ($boards as $board) {
                    // Count scheduled posts before deletion
                    $scheduledPostsCount = Post::where("account_id", $board->id)->count();
                    $totalScheduledPosts += $scheduledPostsCount;
                    $totalBoards++;

                    // Delete Posts
                    $postsToDelete = Post::where("account_id", $board->id)->get();
                    foreach ($postsToDelete as $post) {
                        PostService::delete($post->id);
                    }
                    // Delete Domains
                    Domain::where("account_id", $board->id)->delete();
                    // Delete Timeslots
                    Timeslot::where("account_id", $board->id)->delete();
                    // Delete Board
                    Board::where("id", $board->id)->delete();
                }

                // Decrement feature usage for scheduled posts
                if ($totalScheduledPosts > 0) {
                    $user->decrementFeatureUsage(Feature::$features_list[1], $totalScheduledPosts);
                }

                // Decrement feature usage for social_accounts (Pinterest account + all boards)
                $user->decrementFeatureUsage(Feature::$features_list[0], 1 + $totalBoards);

                // pinterest account
                $pinterestId = $pinterest->id;
                $pinterestUsername = $pinterest->username;
                $pinterest->delete();
                
                // Log account deletion
                $this->logService->logAccountConnection('pinterest', $pinterestId, $pinterestUsername, 'disconnected');
                
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
                $boardModel = $pinterest->boards()->updateOrCreate(["user_id" => $user->id, "board_id" => $board["id"]], [
                    "name" => $board["name"],
                    "status" => 1
                ]);
                
                // Log board connection
                $this->logService->log('pinterest', 'board_connected', "Board '{$board["name"]}' connected", [
                    'board_id' => $boardModel->id,
                    'pinterest_id' => $pinterest->id,
                    'user_id' => $user->id
                ], 'info');
                
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
            $user = User::find(Auth::guard('user')->id());
            $board = $this->board->search($id)->first();
            if ($board) {
                // Count scheduled posts before deletion
                $scheduledPostsCount = $board->posts()->where('source', 'schedule')->count();

                // posts
                $postsToDelete = $board->posts()->get();
                foreach ($postsToDelete as $post) {
                    PostService::delete($post->id);
                }
                // domains
                $board->domains()->delete();
                // timeslots
                $board->timeslots()->delete();
                // board
                $boardId = $board->id;
                $boardName = $board->name;
                $board->delete();

                // Decrement feature usage for scheduled posts
                if ($scheduledPostsCount > 0) {
                    $user->decrementFeatureUsage(Feature::$features_list[1], $scheduledPostsCount);
                }

                // Decrement feature usage for social_accounts
                $user->decrementFeatureUsage(Feature::$features_list[0], 1);

                // Log board deletion
                $this->logService->log('pinterest', 'board_deleted', "Board '{$boardName}' deleted", [
                    'board_id' => $boardId,
                    'user_id' => $user->id
                ], 'info');

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
            $user = User::find(Auth::guard('user')->id());
            $facebook = $this->facebook->search($id)->first();
            if ($facebook) {
                $pages = Page::with("posts", "domains", "timeslots")->where('fb_id', $facebook->id)->get();
                $totalScheduledPosts = 0;
                $totalPages = 0;

                // posts,domains,timeslots,page
                foreach ($pages as $page) {
                    // Count scheduled posts before deletion
                    $scheduledPostsCount = Post::where("account_id", $page->id)->count();
                    $totalScheduledPosts += $scheduledPostsCount;
                    $totalPages++;

                    // Delete Posts
                    $postsToDelete = Post::where("account_id", $page->id)->get();
                    foreach ($postsToDelete as $post) {
                        PostService::delete($post->id);
                    }
                    // Delete Domains
                    Domain::where("account_id", $page->id)->delete();
                    // Delete Timeslots
                    Timeslot::where("account_id", $page->id)->delete();
                    // Delete Page
                    Page::where("id", $page->id)->delete();
                }

                // Decrement feature usage for scheduled posts
                if ($totalScheduledPosts > 0) {
                    $user->decrementFeatureUsage(Feature::$features_list[1], $totalScheduledPosts);
                }

                // Decrement feature usage for social_accounts (Facebook account + all pages)
                $user->decrementFeatureUsage(Feature::$features_list[0], 1 + $totalPages);

                // Delete Facebook account
                $facebookId = $facebook->id;
                $facebookUsername = $facebook->username;
                $facebook->delete();

                // Log account deletion
                $this->logService->logAccountConnection('facebook', $facebookId, $facebookUsername, 'disconnected');

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

                $pageModel = $facebook->pages()->updateOrCreate(["user_id" => $user->id, "page_id" => $page["id"]], $pageData);

                // Log page connection
                $this->logService->log('facebook', 'page_connected', "Page '{$page["name"]}' connected", [
                    'page_id' => $pageModel->id,
                    'facebook_id' => $facebook->id,
                    'user_id' => $user->id
                ], 'info');

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
            $user = User::find(Auth::guard('user')->id());
            $page = $this->page->search($id)->first();
            if ($page) {
                // Count scheduled posts before deletion
                $scheduledPostsCount = $page->posts()->where('source', 'schedule')->count();

                // posts
                $postsToDelete = $page->posts()->get();
                foreach ($postsToDelete as $post) {
                    PostService::delete($post->id);
                }
                // domains
                $page->domains()->delete();
                // timeslots
                $page->timeslots()->delete();
                // page
                $pageId = $page->id;
                $pageName = $page->name;
                $page->delete();

                // Decrement feature usage for scheduled posts
                if ($scheduledPostsCount > 0) {
                    $user->decrementFeatureUsage(Feature::$features_list[1], $scheduledPostsCount);
                }

                // Decrement feature usage for social_accounts
                $user->decrementFeatureUsage(Feature::$features_list[0], 1);

                // Log page deletion
                $this->logService->log('facebook', 'page_deleted', "Page '{$pageName}' deleted", [
                    'page_id' => $pageId,
                    'user_id' => $user->id
                ], 'info');

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
            $user = User::find(Auth::guard('user')->id());
            $tiktok = $this->tiktok->search($id)->first();
            if ($tiktok) {
                // Count scheduled posts before deletion
                $scheduledPostsCount = Post::where('account_id', $tiktok->id)->count();

                // Delete all posts for this TikTok account
                Post::where('account_id', $tiktok->id)
                    ->where('social_type', 'tiktok')
                    ->delete();

                // TikTok account
                $tiktok->delete();

                // Decrement feature usage for scheduled posts
                if ($scheduledPostsCount > 0) {
                    $user->decrementFeatureUsage(Feature::$features_list[1], $scheduledPostsCount);
                }

                // Decrement feature usage for social_accounts
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
