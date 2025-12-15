<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('stripe_price_id');
            $table->integer('sort_order')->default(0)->after('is_active');
            $table->decimal('monthly_price', 10, 2)->nullable()->after('price');
            $table->integer('trial_days')->default(0)->after('monthly_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_active', 'sort_order', 'monthly_price', 'trial_days']);
        });
    }
};
