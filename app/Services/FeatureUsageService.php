<?php

namespace App\Services;

use App\Models\User;
use App\Models\Feature;
use App\Models\UserFeatureUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FeatureUsageService
{
    /**
     * Check if user can use a feature and increment usage if allowed
     * 
     * @param User $user
     * @param string $featureKey
     * @param int $amount Amount to increment (default: 1)
     * @return array ['allowed' => bool, 'usage' => int, 'limit' => int|null, 'remaining' => int|null, 'message' => string]
     */
    public function checkAndIncrement(User $user, string $featureKey, int $amount = 1): array
    {
        // Check if user can use the feature
        if (!$user->canUseFeature($featureKey)) {
            return [
                'allowed' => false,
                'usage' => 0,
                'limit' => null,
                'remaining' => null,
                'message' => 'This feature is not available in your package.',
            ];
        }

        // Get feature details
        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return [
                'allowed' => false,
                'usage' => 0,
                'limit' => null,
                'remaining' => null,
                'message' => 'Feature not found.',
            ];
        }

        // Get user's package feature details
        $activePackage = $user->activeUserPackage;
        if (!$activePackage || !$activePackage->package) {
            return [
                'allowed' => false,
                'usage' => 0,
                'limit' => null,
                'remaining' => null,
                'message' => 'No active package found.',
            ];
        }

        $packageFeature = $activePackage->package->features()
            ->where('features.id', $feature->id)
            ->wherePivot('is_enabled', true)
            ->first();

        if (!$packageFeature) {
            return [
                'allowed' => false,
                'usage' => 0,
                'limit' => null,
                'remaining' => null,
                'message' => 'Feature not enabled in your package.',
            ];
        }

        $isUnlimited = $packageFeature->pivot->is_unlimited ?? false;
        $limitValue = $packageFeature->pivot->limit_value ?? null;
        $featureType = $feature->type;

        // Handle boolean features
        if ($featureType === 'boolean') {
            $currentUsage = $user->getFeatureUsage($featureKey);
            if ($currentUsage > 0) {
                return [
                    'allowed' => false,
                    'usage' => $currentUsage,
                    'limit' => 1,
                    'remaining' => 0,
                    'message' => 'This feature is already enabled.',
                ];
            }
            $user->incrementFeatureUsage($featureKey, 1);
            return [
                'allowed' => true,
                'usage' => 1,
                'limit' => 1,
                'remaining' => 0,
                'message' => 'Feature enabled successfully.',
            ];
        }

        // Handle unlimited features
        if ($featureType === 'unlimited' || $isUnlimited) {
            $user->incrementFeatureUsage($featureKey, $amount);
            return [
                'allowed' => true,
                'usage' => $user->getFeatureUsage($featureKey),
                'limit' => null,
                'remaining' => null,
                'message' => 'Usage incremented successfully.',
            ];
        }

        // Handle numeric features with limits
        if ($featureType === 'numeric') {
            $currentUsage = $user->getFeatureUsage($featureKey);
            
            // If no limit is set, allow usage
            if ($limitValue === null) {
                $user->incrementFeatureUsage($featureKey, $amount);
                return [
                    'allowed' => true,
                    'usage' => $user->getFeatureUsage($featureKey),
                    'limit' => null,
                    'remaining' => null,
                    'message' => 'Usage incremented successfully.',
                ];
            }

            // Check if incrementing would exceed limit
            if (($currentUsage + $amount) > $limitValue) {
                return [
                    'allowed' => false,
                    'usage' => $currentUsage,
                    'limit' => $limitValue,
                    'remaining' => max(0, $limitValue - $currentUsage),
                    'message' => "You have reached your limit of {$limitValue}. You have {$this->getRemaining($currentUsage, $limitValue)} remaining.",
                ];
            }

            // Increment usage
            $user->incrementFeatureUsage($featureKey, $amount);
            $newUsage = $user->getFeatureUsage($featureKey);
            
            return [
                'allowed' => true,
                'usage' => $newUsage,
                'limit' => $limitValue,
                'remaining' => $limitValue - $newUsage,
                'message' => 'Usage incremented successfully.',
            ];
        }

        return [
            'allowed' => false,
            'usage' => 0,
            'limit' => null,
            'remaining' => null,
            'message' => 'Unknown feature type.',
        ];
    }

    /**
     * Get remaining usage for a feature
     * 
     * @param int $currentUsage
     * @param int|null $limit
     * @return int|null
     */
    private function getRemaining(int $currentUsage, ?int $limit): ?int
    {
        if ($limit === null) {
            return null;
        }
        return max(0, $limit - $currentUsage);
    }

    /**
     * Get usage statistics for a user and feature
     * 
     * @param User $user
     * @param string $featureKey
     * @return array
     */
    public function getUsageStats(User $user, string $featureKey): array
    {
        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return [];
        }

        $activePackage = $user->activeUserPackage;
        if (!$activePackage || !$activePackage->package) {
            return [];
        }

        $packageFeature = $activePackage->package->features()
            ->where('features.id', $feature->id)
            ->wherePivot('is_enabled', true)
            ->first();

        if (!$packageFeature) {
            return [];
        }

        $currentUsage = $user->getFeatureUsage($featureKey);
        $limitValue = $packageFeature->pivot->limit_value ?? null;
        $isUnlimited = $packageFeature->pivot->is_unlimited ?? false;
        $featureType = $feature->type;

        $isUnlimitedFeature = $isUnlimited || $featureType === 'unlimited';
        $remaining = $isUnlimitedFeature ? null : ($limitValue !== null ? max(0, $limitValue - $currentUsage) : null);
        $usagePercentage = $isUnlimitedFeature || $limitValue === null ? 0 : ($limitValue > 0 ? round(($currentUsage / $limitValue) * 100, 2) : 0);
        $isOverLimit = !$isUnlimitedFeature && $limitValue !== null && $currentUsage > $limitValue;
        $isNearLimit = !$isUnlimitedFeature && $limitValue !== null && $limitValue > 0 && $usagePercentage >= 80;

        return [
            'feature_key' => $featureKey,
            'feature_name' => $feature->name,
            'feature_type' => $featureType,
            'current_usage' => $currentUsage,
            'limit' => $limitValue,
            'is_unlimited' => $isUnlimitedFeature,
            'remaining' => $remaining,
            'usage_percentage' => $usagePercentage,
            'is_over_limit' => $isOverLimit,
            'is_near_limit' => $isNearLimit,
            'can_use' => $user->canUseFeature($featureKey),
        ];
    }

    /**
     * Reset usage for a specific user and feature (admin function)
     * 
     * @param User $user
     * @param string $featureKey
     * @return bool
     */
    public function resetUsage(User $user, string $featureKey): bool
    {
        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return false;
        }

        $periodStart = now()->startOfMonth();
        $usage = UserFeatureUsage::where('user_id', $user->id)
            ->where('feature_id', $feature->id)
            ->where('period_start', $periodStart)
            ->where('is_archived', false)
            ->first();

        if ($usage) {
            $usage->update(['usage_count' => 0]);
            return true;
        }

        return false;
    }

    /**
     * Get usage history for a user and feature
     * 
     * @param User $user
     * @param string $featureKey
     * @param int $months Number of months to retrieve (default: 12)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsageHistory(User $user, string $featureKey, int $months = 12)
    {
        return $user->getFeatureUsageHistory($featureKey)
            ->take($months)
            ->map(function ($usage) {
                return [
                    'period_start' => $usage->period_start,
                    'period_end' => $usage->period_end,
                    'usage_count' => $usage->usage_count,
                    'archived_at' => $usage->archived_at,
                ];
            });
    }

    /**
     * Check and send notifications for users approaching or exceeding limits
     * This should be called periodically (e.g., daily cron job)
     * 
     * @return array Summary of notifications sent
     */
    public function checkAndNotifyLimits(): array
    {
        $summary = [
            'near_limit' => 0,
            'over_limit' => 0,
            'notifications_sent' => 0,
        ];

        // Get all active users with packages
        $users = User::whereHas('userPackages', function ($query) {
            $query->where('is_active', true);
        })->get();

        foreach ($users as $user) {
            $activePackage = $user->activeUserPackage;
            if (!$activePackage || !$activePackage->package) {
                continue;
            }

            $package = $activePackage->package;
            $packageFeatures = $package->features()
                ->wherePivot('is_enabled', true)
                ->get();

            foreach ($packageFeatures as $feature) {
                $stats = $this->getUsageStats($user, $feature->key);

                if (empty($stats) || $stats['is_unlimited']) {
                    continue;
                }

                // Check if over limit
                if ($stats['is_over_limit']) {
                    $this->sendLimitNotification($user, $feature->key, 'over_limit', $stats);
                    $summary['over_limit']++;
                    $summary['notifications_sent']++;
                }
                // Check if near limit (80% or more)
                elseif ($stats['is_near_limit'] && !$stats['is_over_limit']) {
                    $this->sendLimitNotification($user, $feature->key, 'near_limit', $stats);
                    $summary['near_limit']++;
                    $summary['notifications_sent']++;
                }
            }
        }

        return $summary;
    }

    /**
     * Send limit notification to user
     * 
     * @param User $user
     * @param string $featureKey
     * @param string $type 'near_limit' or 'over_limit'
     * @param array $stats Usage statistics
     * @return void
     */
    private function sendLimitNotification(User $user, string $featureKey, string $type, array $stats): void
    {
        $feature = Feature::where('key', $featureKey)->first();
        if (!$feature) {
            return;
        }

        $featureName = $feature->name;
        $usage = $stats['current_usage'];
        $limit = $stats['limit'];
        $remaining = $stats['remaining'] ?? 0;

        if ($type === 'over_limit') {
            $title = "Feature Limit Exceeded: {$featureName}";
            $message = "You have exceeded your limit of {$limit} for {$featureName}. Current usage: {$usage}. Please upgrade your package to continue using this feature.";
            $notificationType = 'error';
        } else {
            $title = "Approaching Feature Limit: {$featureName}";
            $message = "You are approaching your limit for {$featureName}. You have used {$usage} of {$limit} ({$remaining} remaining). Consider upgrading your package soon.";
            $notificationType = 'warning';
        }

        // Check if notification was already sent today to avoid spam
        $today = now()->startOfDay();
        $existingNotification = \App\Models\Notification::where('user_id', $user->id)
            ->where('title', $title)
            ->where('created_at', '>=', $today)
            ->first();

        if (!$existingNotification) {
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => [
                    'type' => $notificationType,
                    'message' => $message,
                    'feature_key' => $featureKey,
                    'feature_name' => $featureName,
                    'usage' => $usage,
                    'limit' => $limit,
                    'remaining' => $remaining,
                ],
                'is_read' => false,
                'is_system' => true,
            ]);
        }
    }
}

