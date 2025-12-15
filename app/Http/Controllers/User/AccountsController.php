<?php

namespace App\Http\Controllers\User;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Board;
use App\Models\Domain;
use App\Models\Facebook;
use App\Models\Pinterest;
use App\Models\Tiktok;
use Illuminate\Http\Request;
use App\Services\FacebookService;
use App\Services\PinterestService;
use App\Services\TikTokService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;

class AccountsController extends Controller
{
    private $pinterestService;
    private $facebookService;
    private $tiktokService;
    private $pinterest;
    private $facebook;
    private $tiktok;
    private $board;
    private $page;
    private $post;
    private $domain;
    public function __construct(Pinterest $pinterest, Facebook $facebook, Tiktok $tiktok, Board $board, Page $page, Post $post, Domain $domain)
    {
        $this->pinterestService = new PinterestService();
        $this->facebookService = new FacebookService();
        $this->tiktokService = new TikTokService();
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
        $user = User::with("facebook", "pinterest", "tiktok")->findOrFail(Auth::id());
        $facebookUrl = $this->facebookService->getLoginUrl();
        $pinterestUrl = $this->pinterestService->getLoginUrl();
        $tiktokUrl = $this->tiktokService->getLoginUrl();
        return view("user.accounts.index", compact("user", "facebookUrl", "pinterestUrl", "tiktokUrl"));
    }

    public function pinterestDelete($id = null)
    {
        if (!empty($id)) {
            $pinterest = $this->pinterest->search($id)->first();
            if ($pinterest) {
                $board_ids = $pinterest->boards()->get()->pluck("board_id")->toArray();
                // posts
                $this->post->accounts($board_ids)->delete();
                // domains
                $this->domain->accounts($board_ids)->delete();
                // boards
                $pinterest->boards()->delete();
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
            $board = $request->board_data;
            if ($board) {
                $user = Auth::user();
                $pinterest = $this->pinterest->search($request->pin_id)->firstOrfail();
                $pinterest->boards()->updateOrCreate(["user_id" => $user->id, "board_id" => $board["id"]], [
                    "name" => $board["name"],
                    "status" => 1
                ]);
                return response()->json(["success" => true, "message" => "Board connected Successfully!"]);
            } else {
                return response()->json(["success" => false, "message" => "Something went Wrong!"]);
            }
        } catch (Exception $e) {
            return response()->json(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function boardDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::user();
            $board = $this->board->search($id)->first();
            if ($board) {
                // posts
                $board->posts()->delete();
                // domains
                $board->domains()->delete();
                // timeslots
                $board->timeslots()->delete();
                // board
                $board->delete();
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
            $user = Auth::user();
            $facebook = $this->facebook->search($id)->first();
            if ($facebook) {
                $page_ids = $facebook->pages()->get()->pluck("page_id")->toArray();
                // posts
                $this->post->accounts($page_ids)->delete();
                // domains
                $this->domain->accounts($page_ids)->delete();
                // pages
                $facebook->pages()->delete();
                // facebook account
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
                $user = Auth::user();
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

                return response()->json(["success" => true, "message" => "Page connected Successfully!"]);
            } catch (Exception $e) {
                return response()->json(["success" => false, "message" => "Failed to connect page: " . $e->getMessage()]);
            }
        } else {
            return response()->json(["success" => false, "message" => "Something went Wrong!"]);
        }
    }

    public function pageDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::user();
            $page = $this->page->search($id)->first();
            if ($page) {
                // posts
                $page->posts()->delete();
                // domains
                $page->domains()->delete();
                // timeslots
                $page->timeslots()->delete();
                // page
                $page->delete();
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
            $user = Auth::user();
            $tiktok = $this->tiktok->search($id)->first();
            if ($tiktok) {
                // TikTok account
                $tiktok->delete();
                return back()->with("success", "TikTok Account deleted Successfully!");
            } else {
                return back()->with("error", "Something went Wrong!");
            }
        } else {
            return back()->with("error", "Something went Wrong!");
        }
    }
}
