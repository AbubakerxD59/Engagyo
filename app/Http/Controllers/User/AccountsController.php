<?php

namespace App\Http\Controllers\User;

use App\Models\Domain;
use App\Models\Post;
use App\Models\Board;
use App\Models\Pinterest;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AccountsController extends Controller
{
    private $pinterestService;
    private $pinterest;
    private $board;
    private $post;
    private $domain;
    public function __construct(Pinterest $pinterest, Board $board, Post $post, Domain $domain)
    {
        $this->pinterestService = new PinterestService();
        $this->pinterest = $pinterest;
        $this->board = $board;
        $this->post = $post;
        $this->domain = $domain;
    }
    public function index()
    {
        $user = Auth::user();
        $pinterestUrl = $this->pinterestService->getLoginUrl();
        return view("user.accounts.index", compact("user", "pinterestUrl"));
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
                return back()->with("error", "Somewthing went Wrong!");
            }
        } else {
            return back()->with("error", "Somewthing went Wrong!");
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
}
