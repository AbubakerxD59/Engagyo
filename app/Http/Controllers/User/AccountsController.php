<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\PinterestService;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    private $pinterestService;
    public function __construct()
    {
        $this->pinterestService = new PinterestService();
    }
    public function index()
    {
        $pinterestUrl = $this->pinterestService->getLoginUrl();
        return view("user.accounts.index", compact("pinterestUrl"));
    }
}
