<?php

namespace App\Http\Controllers\User;

use App\Models\Domain;
use App\Models\Page;
use App\Models\Post;
use App\Models\Board;
use App\Models\Pinterest;
use App\Services\FacebookService;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use App\Models\Facebook;
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
        $user = Auth::user();
        $facebookUrl = $this->facebookService->getLoginUrl();
        $pinterestUrl = $this->pinterestService->getLoginUrl();
        return view("user.accounts.index", compact("user", "facebookUrl", "pinterestUrl"));
    }

    public function pinterestDelete($id = null)
    {
        if (!empty($id)) {
            $user = Auth::user();
            $pinterest = $this->pinterest->search($id)->user($user->id)->first();
            if ($pinterest) {
                $board_ids = $pinterest->boards()->where("user_id", $user->id)->pluck('board_id')->toArray();
                // posts
                $this->post->whereIn("account_id", $board_ids)->where("user_id", $user->id)->delete();
                // domains
                $this->domain->whereIn("account_id", $board_ids)->where("user_id", $user->id)->delete();
                // boards
                $pinterest->boards()->where("user_id", $user->id)->delete();
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
            $facebook = $this->facebook->search($id)->user($user->id)->first();
            if ($facebook) {
                $fb_ids = $facebook->pages()->where("user_id", $user->id)->pluck('fb_id')->toArray();
                // posts
                $this->post->whereIn("account_id", $fb_ids)->where("user_id", $user->id)->delete();
                // domains
                $this->domain->whereIn("account_id", $fb_ids)->where("user_id", $user->id)->delete();
                // boards
                $facebook->posts()->where("user_id", $user->id)->delete();
                // pinterest account
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
