<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController;

class PlanBillingController extends BaseController
{
    /**
     * Display the plan and billing page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = User::with("userPackages.package", "userPackages.package.features")->findOrFail(Auth::guard('user')->id());

        // Get active package
        $activePackage = $user->activeUserPackage;
        $package = $activePackage ? $activePackage->package : null;

        // Get package status
        $packageStatus = 'No Package';
        $expiryDate = null;
        if ($activePackage && $package) {
            if ($package->is_lifetime) {
                $packageStatus = 'Lifetime';
            } elseif ($activePackage->expires_at) {
                $expiryDate = $activePackage->expires_at;
                if ($expiryDate->isPast()) {
                    $packageStatus = 'Expired';
                } elseif ($expiryDate->isToday() || $expiryDate->isFuture()) {
                    $packageStatus = 'Active';
                }
            } else {
                $packageStatus = 'Active';
            }
        }

        // Get features with usage
        $featuresWithUsage = [];
        if ($activePackage && $package) {
            $packageFeatures = $package->features()
                ->wherePivot('is_enabled', true)
                ->where('is_active', true)
                ->get();

            foreach ($packageFeatures as $feature) {
                $usage = $user->getFeatureUsage($feature->key);
                $limit = $feature->pivot->limit_value;
                $isUnlimited = $feature->pivot->is_unlimited ?? false;

                $featuresWithUsage[] = [
                    'id' => $feature->id,
                    'key' => $feature->key,
                    'name' => $feature->name,
                    'description' => $feature->description ?? '',
                    'usage' => $usage,
                    'limit' => $isUnlimited ? null : $limit,
                    'is_unlimited' => $isUnlimited,
                    'remaining' => $isUnlimited ? null : ($limit !== null ? max(0, $limit - $usage) : null),
                ];
            }
        }

        // Get available packages for upgrade/grading
        $packages = \App\Models\Package::where('is_active', true)
            ->with(['features' => function ($query) {
                $query->wherePivot('is_enabled', true);
            }])
            ->orderBy('sort_order', 'asc')
            ->orderBy('price', 'asc')
            ->get();

        return view('user.plan-billing.index', compact('user', 'package', 'packageStatus', 'expiryDate', 'featuresWithUsage', 'packages'));
    }
}
