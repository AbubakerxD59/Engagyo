<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use App\Models\Pinterest;
use Illuminate\Support\Facades\Auth;

class AccountsController extends Controller
{
    private $pinterestService;
    private $pinterest;
    public function __construct(Pinterest $pinterest)
    {
        $this->pinterestService = new PinterestService();
        $this->pinterest = $pinterest;
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
                $pinterest->boards()->delete();
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
            $pinterest = $this->pinterest->search($id)->first();
            if ($pinterest) {
                return view('user.accounts.pinterest', compact('pinterest'));
            } else {
                // pinterest
            }
        } else {
            return back()->with('error', 'Something went Wrong!');
        }
    }
}
