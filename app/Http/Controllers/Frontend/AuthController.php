<?php

namespace App\Http\Controllers\Frontend;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use App\Http\Requests\FrontEnd\LoginRequest;
use App\Http\Requests\FrontEnd\RegisterRequest;

class AuthController extends Controller
{
    private $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    public function showLogin()
    {
        return view("frontend.auth.login");
    }

    public function showRegister()
    {
        return view("frontend.auth.register");
    }

    public function login(LoginRequest $request)
    {
        $request->validated();
        $user = $this->user->email($request->email)->first();
        if ($user) {
            $role = $user->getRole();
            if ($role == 'User') {
                $remember = $request->has("remember_me") && $request->remember_me == 'on' ? true : false;
                if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $remember)) {
                    $response = [
                        'success' => true,
                        'message' => 'Login successful!'
                    ];
                } else {
                    $response = [
                        "success" => false,
                        "message" => "Invalid Crendentials!"
                    ];
                }
            } else {
                $response = [
                    "success" => false,
                    "message" => "Invalid Crendentials!"
                ];
            }
        } else {
            $response = [
                "success" => false,
                "message" => "Invalid Crendentials!"
            ];
        }

        if ($response['success']) {
            return redirect()->intended(RouteServiceProvider::FRONTEND_AUTH_HOME)->with('success', $response['message']);
        } else {
            return redirect()->intended(RouteServiceProvider::FRONTEND_INV_CRED)->with('error', $response['message']);
        }
    }

    public function register(RegisterRequest $request)
    {
        $request->validated();
        $user = $this->user->create([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "username" => Str::random(6),
            "email" => $request->email,
            "password" => $request->password,
            "agreement" => $request->agreement,
            "status" => 2,
        ]);
        if ($user) {
            $user->assignRole("User");
            $response = [
                "success" => true,
                "message" => "Welcome to" . env("APP_NAME", "Engagyo") . " ! Get started and explore"
            ];
        } else {
            $response = [
                "success" => false,
                "message" => "Something went Wrong!"
            ];
        }

        if ($response['success']) {
            return redirect()->route("frontend.showLogin")->with("success", $response["message"]);
        } else {
            return back()->with("error", $response["message"]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect()->route('frontend.home')->with("success", "Session ended Successfully!");
    }
}
