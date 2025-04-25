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
        Schema::table('pinterests', function (Blueprint $table) {
            $table->longText('access_token')->nullable()->after("monthly_views");
            $table->longText('expires_in')->nullable()->after("access_token");
            $table->longText('refresh_token')->nullable()->after("expires_in");
            $table->longText('refresh_token_expires_in')->nullable()->after("refresh_token");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pinterests', function (Blueprint $table) {
            //
        });
    }
};
