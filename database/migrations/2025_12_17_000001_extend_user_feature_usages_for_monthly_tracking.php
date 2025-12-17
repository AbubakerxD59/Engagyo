<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add new columns
        Schema::table('user_feature_usages', function (Blueprint $table) {
            $table->date('period_end')->nullable()->after('period_start');
            $table->boolean('is_archived')->default(false)->after('is_unlimited');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
        });

        // Update existing period_start values to current month start
        // This ensures all records have a valid date before we change the column type
        $currentMonthStart = now()->startOfMonth()->format('Y-m-d');
        
        // Update null, empty, or zero values
        DB::table('user_feature_usages')
            ->where(function ($query) {
                $query->whereNull('period_start')
                      ->orWhere('period_start', '=', 0)
                      ->orWhere('period_start', '');
            })
            ->update(['period_start' => $currentMonthStart]);

        // For existing integer/timestamp values, convert them to dates
        // We'll handle this by updating all records to current month start
        // (since the old integer values were likely timestamps or not meaningful)
        DB::table('user_feature_usages')
            ->whereNotNull('period_start')
            ->where('period_start', '!=', 0)
            ->where('period_start', '!=', '')
            ->update(['period_start' => $currentMonthStart]);

        // Now change the column type from integer to date
        // Using raw SQL for better compatibility
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE user_feature_usages MODIFY period_start DATE NULL');
        } else {
            // For other databases, use Laravel's change method
            Schema::table('user_feature_usages', function (Blueprint $table) {
                $table->date('period_start')->nullable()->change();
            });
        }

        // Add indexes for efficient queries
        Schema::table('user_feature_usages', function (Blueprint $table) {
            $table->index(['user_id', 'feature_id', 'period_start']);
            $table->index(['is_archived', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_feature_usages', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'feature_id', 'period_start']);
            $table->dropIndex(['is_archived', 'period_start']);
            $table->dropColumn(['period_end', 'is_archived', 'archived_at']);
            // Note: period_start type change might need manual handling in down()
        });
    }
};

