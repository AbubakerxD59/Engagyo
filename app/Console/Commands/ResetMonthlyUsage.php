<?php

namespace App\Console\Commands;

use App\Models\UserFeatureUsage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ResetMonthlyUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usage:reset-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset monthly feature usage for all users and archive previous month data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting monthly usage reset...');
        
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        // Get all active users with packages
        $users = User::whereHas('userPackages', function ($query) {
            $query->where('is_active', true);
        })->get();

        $this->info("Processing {$users->count()} users...");

        $archivedCount = 0;
        $resetCount = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                // Get all non-archived usage records for this user
                $usageRecords = UserFeatureUsage::where('user_id', $user->id)
                    ->where('is_archived', false)
                    ->get();

                foreach ($usageRecords as $usage) {
                    // period_start is already cast to Carbon date in the model
                    $usagePeriodStart = $usage->period_start;

                    // Archive the previous period's usage
                    if ($usagePeriodStart && $usagePeriodStart instanceof \Carbon\Carbon && $usagePeriodStart->lt($currentMonth)) {
                        $usage->update([
                            'is_archived' => true,
                            'archived_at' => now(),
                            'period_end' => $usage->period_end ?? $previousMonthEnd,
                        ]);
                        $archivedCount++;
                    }

                    // Check if we need to create a new record for current month
                    $currentPeriodUsage = UserFeatureUsage::where('user_id', $user->id)
                        ->where('feature_id', $usage->feature_id)
                        ->where('period_start', $currentMonth->format('Y-m-d'))
                        ->where('is_archived', false)
                        ->first();

                    if (!$currentPeriodUsage) {
                        // Create new usage record for current month
                        UserFeatureUsage::create([
                            'user_id' => $user->id,
                            'feature_id' => $usage->feature_id,
                            'usage_count' => 0,
                            'is_unlimited' => $usage->is_unlimited ?? false,
                            'period_start' => $currentMonth->format('Y-m-d'),
                            'period_end' => $currentMonth->copy()->endOfMonth()->format('Y-m-d'),
                            'is_archived' => false,
                        ]);
                        $resetCount++;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "User ID {$user->id}: " . $e->getMessage();
                Log::error("Monthly usage reset failed for user {$user->id}: " . $e->getMessage());
            }
        }

        // Also handle users who might have usage records but no active package
        // Archive any remaining old records
        $oldRecords = UserFeatureUsage::where('is_archived', false)
            ->where(function ($query) use ($currentMonth) {
                $query->whereNull('period_start')
                    ->orWhere('period_start', '<', $currentMonth);
            })
            ->get();

        foreach ($oldRecords as $record) {
            try {
                $record->update([
                    'is_archived' => true,
                    'archived_at' => now(),
                    'period_end' => $record->period_end ?? $previousMonthEnd,
                ]);
                $archivedCount++;
            } catch (\Exception $e) {
                $errors[] = "Record ID {$record->id}: " . $e->getMessage();
                Log::error("Failed to archive record {$record->id}: " . $e->getMessage());
            }
        }

        // Summary
        $this->info("Monthly usage reset completed!");
        $this->info("Archived records: {$archivedCount}");
        $this->info("Reset records: {$resetCount}");

        if (!empty($errors)) {
            $this->warn("Encountered " . count($errors) . " error(s):");
            foreach ($errors as $error) {
                $this->error($error);
            }
        }

        Log::info("Monthly usage reset completed. Archived: {$archivedCount}, Reset: {$resetCount}");

        return Command::SUCCESS;
    }
}

