<?php

namespace App\Http\Controllers\Frontend;

use App\Models\User;
use App\Models\Package;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\VarDumper\Caster\RdKafkaCaster;

class HomeController extends Controller
{
    private $package;
    private $user;
    private $role;
    private $promoCode;
    public function __construct(Package $package, User $user, Role $role, PromoCode $promoCode)
    {
        $this->package = $package;
        $this->user = $user;
        $this->role = $role;
        $this->promoCode = $promoCode;
    }
    public function index()
    {
        $packages = $this->package->get();
        return view('frontend.home', compact('packages'));
    }

    public function buyMembership($id = null)
    {
        $package = $this->package->find($id);
        if ($package) {
            return view('frontend.become-a-member', compact('package'));
        } else {
            return redirect()->back()->with('error', 'Package not Found!');
        }
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required',
            'email' => 'required|unique:users',
            'whatsapp_number' => 'required',
            'city' => 'required',
            'country' => 'required',
            'promo' => 'sometimes'
        ]);
        $package = $this->package->find($request->package_id);
        if ($package) {
            if (!empty($request->promo)) {
                $promo = $this->promoCode->search($request->promo)->active()->first();
                if ($promo) {
                    if (!$promo->valid()) {
                        return redirect()->back()->with('error', 'Invalid Promo Code!');
                    }
                } else {
                    return redirect()->back()->with('error', 'Invalid Promo Code!');
                }
            }
            $this->user->create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'whatsapp_number' => $request->whatsapp_number,
                'phone_number' => $request->phone_number,
                'city' => $request->city,
                'country' => $request->country,
                'address' => $request->address,
                'membership_id' => rand(100000, 999999),
                'status' => 1,
            ]);
            $user = $this->user->where('email', $request->email)->first();
            $role = $this->role->where('name', 'Customer')->first();
            if (!empty($role)) {
                $user->assignRole($role->name);
            }
            Auth::login($user);
            if (!empty($request->promo)) {
                return auth()->user()
                    ->newSubscription($package->stripe_product_id, $package->stripe_price_id)
                    ->withCoupon($promo->stripe_coupon_id)
                    ->checkout([
                        'success_url' => route('frontend.checkout_success'),
                        'cancel_url' => route('frontend.home'),
                    ]);
            } else {
                return auth()->user()
                    ->newSubscription($package->stripe_product_id, $package->stripe_price_id)
                    ->checkout([
                        'success_url' => route('frontend.checkout_success'),
                        'cancel_url' => route('frontend.home'),
                    ]);
            }
        } else {
            return redirect()->back()->with('error', 'Package not Found!');
        }
    }

    public function success()
    {
        $user = Auth::user();
        return view('frontend.success', compact('user'));
    }
}
