<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AccountsController extends Controller
{
    private $pinterestService;
    public function __construct()
    {
        $this->pinterestService = new PinterestService();
    }
    public function index()
    {
        dd(session()->all(), request()->sess()->get('items'));
        $user = Auth::user();
        $pinterestUrl = $this->pinterestService->getLoginUrl();
        return view("user.accounts.index", compact("user", "pinterestUrl"));
    }
}
