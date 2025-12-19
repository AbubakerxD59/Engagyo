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
        
        // Attempt authentication
        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember_me)) {
            $user = Auth::guard('admin')->user();
            
            // Check if user has an admin role (not a regular user role)
            // Admin roles are: Super Admin, Admin, Staff (any role that is not "User")
            $userRole = $user->getRole();
            
            // If user has "User" role or no role, reject login
            if (empty($userRole) || $userRole === 'User') {
                // Logout the user immediately
                Auth::guard('admin')->logout();
                
                $response = [
                    'success' => false,
                    'message' => 'Access denied. Admin credentials required.',
                ];
            } else {
                // User has an admin role, allow login
                $response = [
                    'success' => true,
                    'message' => 'Login successful!'
                ];
            }
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
