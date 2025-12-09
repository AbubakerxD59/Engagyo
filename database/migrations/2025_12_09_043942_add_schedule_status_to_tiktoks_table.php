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
        Schema::table('tiktoks', function (Blueprint $table) {
            $table->enum("schedule_status", ["active", "inactive"])->default("inactive")->after("refresh_token_expires_in");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiktoks', function (Blueprint $table) {
            $table->dropColumn("schedule_status");
        });
    }
};
