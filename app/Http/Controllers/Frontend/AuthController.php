<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view("frontend.auth.login");
    }

    public function showRegister()
    {
        return view("frontend.auth.register");
    }
}
