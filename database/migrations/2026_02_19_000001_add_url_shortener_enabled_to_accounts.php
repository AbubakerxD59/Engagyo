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
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('url_shortener_enabled')->default(false)->after('schedule_status');
        });
        Schema::table('boards', function (Blueprint $table) {
            $table->boolean('url_shortener_enabled')->default(false)->after('schedule_status');
        });
        Schema::table('tiktoks', function (Blueprint $table) {
            $table->boolean('url_shortener_enabled')->default(false)->after('schedule_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('url_shortener_enabled');
        });
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('url_shortener_enabled');
        });
        Schema::table('tiktoks', function (Blueprint $table) {
            $table->dropColumn('url_shortener_enabled');
        });
    }
};
