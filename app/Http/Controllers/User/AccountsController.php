<?php

namespace App\Http\Controllers\User;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Board;
use App\Models\Domain;
use App\Models\Facebook;
use App\Models\Pinterest;
use Illuminate\Http\Request;
use App\Services\FacebookService;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;

class AccountsController extends Controller
{
    private $pinterestService;
    private $facebookService;
    private $pinterest;
    private $facebook;
    private $board;
    private $page;
    private $post;
    private $domain;
    public function __construct(Pinterest $pinterest, Facebook $facebook, Board $board, Page $page, Post $post, Domain $domain)
    {
        $this->pinterestService = new PinterestService();
        $this->facebookService = new FacebookService();
        $this->pinterest = $pinterest;
        $this->facebook = $facebook;
        $this->board = $board;
        $this->page = $page;
        $this->post = $post;
        $this->domain = $domain;
    }
    public function index()
    {
        $user = User::with("facebook", "pinterest")->findOrFail(Auth::id());
        $facebookUrl = $this->facebookService->getLoginUrl();
        $pinterestUrl = $this->pinterestService->getLoginUrl();
        return view("user.accounts.index", compact("user", "facebookUrl", "pinterestUrl"));
    }

    public function pinterestDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::user();
            $pinterest = $this->pinterest->search($id)->userSearch($user->id)->first();
            if ($pinterest) {
                $board_ids = $pinterest->boards()->userSearch($user->id)->get()->pluck("board_id")->toArray();
                // posts
                $this->post->accounts($board_ids)->userSearch($user->id)->delete();
                // domains
                $this->domain->accounts($board_ids)->userSearch($user->id)->delete();
                // boards
                $pinterest->boards()->userSearch($user->id)->delete();
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
            $pinterest = $this->pinterest->search($id)->first();
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
                $pinterest = $this->pinterest->search($request->pin_id)->userSearch($user->id)->firstOrfail();
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
            $board = $this->board->search($id)->userSearch($user->id)->first();
            if ($board) {
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
            $facebook = $this->facebook->search($id)->userSearch($user->id)->first();
            if ($facebook) {
                $page_ids = $facebook->pages()->userSearch($user->id)->get()->pluck("page_id")->toArray();
                // posts
                $this->post->accounts($page_ids)->userSearch($user->id)->delete();
                // domains
                $this->domain->accounts($page_ids)->userSearch($user->id)->delete();
                // pages
                $facebook->pages()->userSearch($user->id)->delete();
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
            $user = Auth::user();
            $facebook = $this->facebook->search($request->fb_id)->userSearch($user->id)->first();
            $facebook->pages()->updateOrCreate(["user_id" => $user->id, "fb_id" => $facebook->fb_id, "page_id" => $page["id"]], [
                "user_id" => $user->id,
                "fb_id" => $facebook->fb_id,
                "page_id" => $page["id"],
                "name" => $page["name"],
                "status" => 1,
                "access_token" => $page["access_token"],
                "expires_in" => time()
            ]);
            return response()->json(["success" => true, "message" => "Page connected Successfully!"]);
        } else {
            return response()->json(["success" => false, "message" => "Something went Wrong!"]);
        }
    }

    public function pageDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::user();
            $page = $this->page->search($id)->userSearch($user->id)->first();
            if ($page) {
                $page->delete();
                return back()->with("success", "Page deleted Successfully!");
            } else {
                return back()->with('error', 'Something went Wrong!');
            }
        } else {
            return back()->with('error', 'Something went Wrong!');
        }
    }
}
