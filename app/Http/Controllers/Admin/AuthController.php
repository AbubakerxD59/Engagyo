<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Providers\RouteServiceProvider;

class AuthController extends Controller
{
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
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->remember_me)) {
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
            return redirect()->intended(RouteServiceProvider::HOME)->with('success', $response['message']);
        } else {
            return redirect()->intended(RouteServiceProvider::INV_CRED)->with('error', $response['message']);
        }
    }

    public function logout()
    {
        $role_name = isset(Auth::user()->roles->pluck('name')[0]) ? Auth::user()->roles->pluck('name')[0] : '';
        if ($role_name == 'Super Admin') {
            Cache::forget('super_admin_assigned_permissions');
        } else {
            $role_id = isset(Auth::user()->roles->pluck('id')[0]) ? Auth::user()->roles->pluck('id')[0] : '';
            Cache::forget('assigned_permissions_to_role_' . $role_id);
        }
        Auth::logout();
        session()->flash('success', 'auth.logout_success');
        return redirect()->route('login');
    }
}
