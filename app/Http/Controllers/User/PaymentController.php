<?php

namespace App\Http\Controllers\User;

use App\Models\Package;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Get upgrade packages (packages above current user's package)
     */
    public function getUpgradePackages(Request $request)
    {
        $user = Auth::guard('user')->user();

        $currentPackage = null;
        if ($user->package_id) {
            $currentPackage = Package::find($user->package_id);
        }

        // Get all active packages
        $query = Package::where('is_active', true)
            ->with(['features' => function ($query) {
                $query->wherePivot('is_enabled', true);
            }]);

        // If user has a package, filter to show only higher tier packages
        if ($currentPackage) {
            // Get packages with higher sort_order or higher price
            $query->where(function ($q) use ($currentPackage) {
                $q->where('sort_order', '>', $currentPackage->sort_order ?? 0)
                    ->orWhere(function ($q2) use ($currentPackage) {
                        $q2->where('sort_order', '=', $currentPackage->sort_order ?? 0)
                            ->where('price', '>', $currentPackage->price);
                    });
            });
        }

        $upgradePackages = $query->orderBy('sort_order', 'asc')
            ->orderBy('price', 'asc')
            ->get();

        // Format packages for frontend
        $formattedPackages = $upgradePackages->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'icon' => $package->icon,
                'price' => $package->price,
                'price_formatted' => '$' . number_format($package->price / 100, 2),
                'duration' => $package->duration,
                'date_type' => $package->date_type,
                'features' => $package->features->map(function ($feature) {
                    return [
                        'name' => $feature->name,
                        'limit_value' => $feature->pivot->limit_value,
                        'is_unlimited' => $feature->pivot->is_unlimited,
                    ];
                }),
            ];
        });

        return response()->json([
            'packages' => $formattedPackages,
            'current_package' => $currentPackage ? [
                'id' => $currentPackage->id,
                'name' => $currentPackage->name,
            ] : null,
        ]);
    }
}
