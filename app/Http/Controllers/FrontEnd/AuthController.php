<?php

namespace App\Http\Controllers\FrontEnd;

use Exception;
use App\Models\Role;
use App\Models\User;
use App\Models\Package;
use App\Models\TeamMember;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\TeamMemberService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Artisan;
use App\Http\Requests\FrontEnd\LoginRequest;
use App\Http\Requests\FrontEnd\RegisterRequest;

class AuthController extends Controller
{
    private $user;
    private $teamMemberService;

    public function __construct(User $user, TeamMemberService $teamMemberService)
    {
        $this->user = $user;
        $this->teamMemberService = $teamMemberService;
    }
    public function showLogin()
    {
        return view("frontend.auth.login");
    }

    public function showRegister(Request $request)
    {
        session()->forget('selected_package');
        $invitationToken = $request->get('token');
        $invitationEmail = $request->get('email');
        $teamMember = null;
        $package = null;
        if ($request->has("plan")) {
            $package = Package::where('name', 'like', $request->plan)->active()->first();
            if (!$package) {
                return back()->with("Something went wrong!");
            }
            session()->put('selected_package', $package->id);
        }

        if ($invitationToken && $invitationEmail) {
            $teamMember = TeamMember::where('invitation_token', $invitationToken)
                ->where('email', $invitationEmail)
                ->where('status', 'pending')
                ->first();
        }

        return view("frontend.auth.register", compact('invitationToken', 'invitationEmail', 'teamMember'));
    }

    public function login(LoginRequest $request)
    {
        $request->validated();
        $user = $this->user->email($request->email)->first();
        if ($user) {
            $role = $user->getRole();
            if ($role == 'User') {
                $remember = $request->has("remember_me") && $request->remember_me == 'on' ? true : false;
                if (Auth::guard('user')->attempt(['email' => $request->email, 'password' => $request->password], $remember)) {
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
        try {
            $request->validated();
            $user = $this->user->create([
                "username" => Str::random(6),
                "email" => $request->email,
                "password" => $request->password,
                "agreement" => $request->agreement,
                "status" => 1,
            ]);

            if ($user) {
                Auth::guard('user')->loginUsingId($user->id, true);
                // Selected Package
                $package_id = session()->get('selected_package');
                $package = Package::where('id', $package_id)->active()->first();
                $freePackage = Package::free()->active()->first();
                // Find and assign the User role
                // Try to find User role with 'web' guard first (matching User model's default guard)
                $userRole = Role::where('name', 'User')
                    ->where('guard_name', 'web')
                    ->first();

                // If not found, try 'user' guard
                if (!$userRole) {
                    $userRole = Role::where('name', 'User')
                        ->where('guard_name', 'user')
                        ->first();
                }

                // If still not found, try any User role
                if (!$userRole) {
                    $userRole = Role::where('name', 'User')->first();
                }

                // If role doesn't exist, create it with 'web' guard
                if (!$userRole) {
                    $userRole = Role::create([
                        'name' => 'User',
                        'guard_name' => 'web',
                    ]);
                }

                // Assign the role using the role object to ensure proper guard matching
                $user->assignRole($userRole);

                // Handle team invitation if token is provided
                if ($request->has('invitation_token') && $request->invitation_token) {
                    $invitationResult = $this->teamMemberService->acceptInvitation(
                        $request->invitation_token,
                        $user
                    );

                    if ($invitationResult['success']) {
                        $response = [
                            "success" => true,
                            "message" => "Welcome to " . env("APP_NAME", "Engagyo") . "! Your team invitation has been accepted."
                        ];
                    } else {
                        $response = [
                            "success" => true,
                            "message" => "Welcome to " . env("APP_NAME", "Engagyo") . "! " . $invitationResult['message']
                        ];
                    }
                } else {
                    $response = [
                        "success" => true,
                        "message" => "Welcome to " . env("APP_NAME", "Engagyo") . " ! Get started and explore"
                    ];
                }
            } else {
                $response = [
                    "success" => false,
                    "message" => "Something went Wrong!"
                ];
            }

            if ($response['success']) {
                // if a package is selected and not free
                if ($package && $package->price > 0) {
                    return redirect()->route("payment.checkout", $package->id);
                } elseif ($freePackage) { //assign free package
                    $user->assignFreePackage();
                    $user->update(["package_id" => $freePackage->id]);
                    Artisan::call('usage:sync', ['--user_id' => $user->id]);
                }

                if (session()->has('url.intended')) {
                    return redirect()->intended(session()->get('url.intended'))->with("success", $response["message"]);
                }

                return redirect()->route("frontend.home")->with("success", $response["message"]);
            } else {
                return back()->with("error", $response["message"]);
            }
        } catch (Exception $e) {
            return back()->with("error", $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        Auth::guard('user')->logout();
        return redirect()->route('frontend.home')->with("success", "Session ended Successfully!");
    }
}
