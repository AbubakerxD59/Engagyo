<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\FeatureUsageService;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureLimit
{
    protected $featureUsageService;

    public function __construct(FeatureUsageService $featureUsageService)
    {
        $this->featureUsageService = $featureUsageService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $featureKey  The feature key to check
     * @param  bool  $increment  Whether to increment usage if allowed (default: false)
     */
    public function handle(Request $request, Closure $next, string $featureKey, bool $increment = false): Response
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        // If user has full access, bypass all limit checks
        if ($user->hasFullAccess()) {
            // If increment is requested, just increment without checking limits
            if ($increment) {
                $this->featureUsageService->checkAndIncrement($user, $featureKey, 1);
            }

            return $next($request);
        }

        // Check if user can use the feature (check if feature is available in package)
        $canUseFeature = $user->canUseFeature($featureKey);

        // Get usage stats to check if limit is reached
        $usageStats = $this->featureUsageService->getUsageStats($user, $featureKey);
        $isLimitReached = false;
        $limitMessage = '';

        if ($canUseFeature && !empty($usageStats)) {
            // Check if limit is reached (but feature is still available in package)
            if ($usageStats['is_over_limit'] || ($usageStats['limit'] !== null && $usageStats['current_usage'] >= $usageStats['limit'])) {
                $isLimitReached = true;
                $limitMessage = "You have reached your limit of {$usageStats['limit']} for {$usageStats['feature_name']}. Upgrade your package to continue using this feature.";
            }
        }

        // If feature is not available in package at all, block access
        if (!$canUseFeature) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This feature is not available in your package.',
                ], 403);
            }

            return redirect()->back()->with('error', 'This feature is not available in your package.');
        }

        // If increment is requested, check and increment usage
        if ($increment) {
            $result = $this->featureUsageService->checkAndIncrement($user, $featureKey, 1);

            if (!$result['allowed']) {
                // Update limit status if increment failed
                $isLimitReached = true;
                $limitMessage = $result['message'];

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'usage' => $result['usage'],
                        'limit' => $result['limit'],
                        'remaining' => $result['remaining'],
                    ], 403);
                }
            }
        }
        // Share limit status with views
        if ($isLimitReached) {
            view()->share([
                'featureLimitReached' => true,
                'featureLimitMessage' => $limitMessage,
                'featureLimitStats' => $usageStats,
            ]);
        }

        return $next($request);
    }
}
