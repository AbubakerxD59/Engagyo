<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Providers\RouteServiceProvider;

class AuthController extends Controller
{
    public function redirect()
    {
        return redirect()->route("admin.showLogin");
    }
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember_me)) {
            $response = [
                'success' => true,
                'message' => 'Login successful!'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Invalid Credentials!',
            ];
        }

        if ($response['success']) {
            return redirect()->intended(RouteServiceProvider::ADMIN_HOME)->with('success', $response['message']);
        } else {
            return redirect()->intended(RouteServiceProvider::INV_CRED)->with('error', $response['message']);
        }
    }

    public function logout()
    {
        Auth::guard('admin')->logout();
        session()->flash('success', 'auth.logout_success');
        return redirect()->route('admin.showLogin');
    }
}
