<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Board;
use App\Models\ApiKey;
use App\Models\Tiktok;
use App\Models\Feature;
use App\Models\DomainUtmCode;
use Illuminate\Console\Command;
use App\Models\UserFeatureUsage;
use Illuminate\Support\Facades\Log;

class SyncUserUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usage:sync {--user_id= : Sync usage for a specific user ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync user feature usage records for the current period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user_id');

        if ($userId) {
            $this->info("Starting user usage sync for user ID: {$userId}...");
        } else {
            $this->info('Starting user usage sync for all users...');
        }

        $currentMonth = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();

        // Get users to process
        if ($userId) {
            // Process specific user
            $users = User::where('id', $userId)
                ->whereHas('userPackages', function ($query) {
                    $query->where('is_active', true);
                })
                ->get();

            if ($users->isEmpty()) {
                $this->warn("User ID {$userId} not found or has no active package.");
                return Command::FAILURE;
            }
        } else {
            // Get all active users with packages
            $users = User::whereHas('userPackages', function ($query) {
                $query->where('is_active', true);
            })->get();
        }

        $this->info("Processing {$users->count()} user(s)...");

        $syncedCount = 0;
        $createdCount = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                $activePackage = $user->activeUserPackage;
                if (!$activePackage || !$activePackage->package) {
                    continue;
                }

                $package = $activePackage->package;

                // Get all enabled features for this package
                $packageFeatures = $package->features()
                    ->wherePivot('is_enabled', true)
                    ->where('is_active', true)
                    ->get();

                foreach ($packageFeatures as $feature) {
                    // Calculate actual usage count for this feature
                    $calculatedUsage = $this->calculateFeatureUsage($user, $feature->key);

                    // Check if usage record exists for current period
                    $usageRecord = UserFeatureUsage::where('user_id', $user->id)
                        ->where('feature_id', $feature->id)
                        ->where('period_start', $currentMonth->format('Y-m-d'))
                        ->where('is_archived', false)
                        ->first();

                    if (!$usageRecord) {
                        // Create new usage record for current period
                        UserFeatureUsage::create([
                            'user_id' => $user->id,
                            'feature_id' => $feature->id,
                            'usage_count' => $calculatedUsage,
                            'is_unlimited' => $package->features()
                                ->where('features.id', $feature->id)
                                ->first()
                                ->pivot
                                ->is_unlimited ?? false,
                            'period_start' => $currentMonth->format('Y-m-d'),
                            'period_end' => $currentMonthEnd->format('Y-m-d'),
                            'is_archived' => false,
                        ]);
                        $createdCount++;
                    } else {
                        // Update usage count and period_end if needed
                        $periodEnd = $usageRecord->period_end;
                        $needsUpdate = false;
                        $updateData = [];

                        // Check if usage count needs updating
                        if ($usageRecord->usage_count != $calculatedUsage) {
                            $updateData['usage_count'] = $calculatedUsage;
                            $needsUpdate = true;
                        }

                        // Check if period_end needs updating
                        if (!$periodEnd) {
                            $updateData['period_end'] = $currentMonthEnd->format('Y-m-d');
                            $needsUpdate = true;
                        } elseif ($periodEnd instanceof \Carbon\Carbon) {
                            if ($periodEnd->format('Y-m-d') !== $currentMonthEnd->format('Y-m-d')) {
                                $updateData['period_end'] = $currentMonthEnd->format('Y-m-d');
                                $needsUpdate = true;
                            }
                        } else {
                            $updateData['period_end'] = $currentMonthEnd->format('Y-m-d');
                            $needsUpdate = true;
                        }

                        if ($needsUpdate) {
                            $usageRecord->update($updateData);
                        }
                        $syncedCount++;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "User ID {$user->id}: " . $e->getMessage();
                Log::error("Usage sync failed for user {$user->id}: " . $e->getMessage());
            }
        }

        // Summary
        $this->info("Usage sync completed!");
        $this->info("Synced records: {$syncedCount}");
        $this->info("Created records: {$createdCount}");
        $this->info("Total processed: " . ($syncedCount + $createdCount));

        if (!empty($errors)) {
            $this->warn("Encountered " . count($errors) . " error(s):");
            foreach ($errors as $error) {
                $this->error($error);
            }
        }

        Log::info("Usage sync completed. Synced: {$syncedCount}, Created: {$createdCount}");

        return Command::SUCCESS;
    }

    /**
     * Calculate actual usage count for a feature based on its key
     * 
     * @param User $user
     * @param string $featureKey
     * @return int
     */
    private function calculateFeatureUsage(User $user, string $featureKey): int
    {
        $accounts = $user->getAccounts();
        switch ($featureKey) {
            case Feature::$features_list[0]:
                // Count total social accounts: boards (Pinterest) + pages (Facebook) + TikTok accounts
                $boardsCount = Board::where('user_id', $user->id)->count();
                $pagesCount = Page::where('user_id', $user->id)->count();
                $tiktokCount = Tiktok::where('user_id', $user->id)->count();
                return $boardsCount + $pagesCount + $tiktokCount;

            case Feature::$features_list[1]:
                return Post::whereIn('account_id', $accounts->pluck('id'))
                    ->where('source', '!=', 'rss')
                    ->where('user_id', $user->id)
                    ->count();

            case Feature::$features_list[2]:
                return Post::whereIn('account_id', $accounts->pluck('id'))
                    ->where('source', 'rss')
                    ->where('user_id', $user->id)
                    ->count();

            case Feature::$features_list[6]:
                $utmCodes = DomainUtmCode::where('user_id', $user->id)->get();
                $utmCodes = $utmCodes->groupBy('domain_name');
                return $utmCodes->count();
            default:
                // For unknown features, return 0 or try to get from existing usage record
                $feature = Feature::where('key', $featureKey)->first();
                if ($feature) {
                    $existingUsage = UserFeatureUsage::where('user_id', $user->id)
                        ->where('feature_id', $feature->id)
                        ->where('is_archived', false)
                        ->where('period_start', now()->startOfMonth()->format('Y-m-d'))
                        ->first();

                    return $existingUsage ? $existingUsage->usage_count : 0;
                }
                return 0;
        }
    }
}
