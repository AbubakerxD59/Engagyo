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
        Schema::table('short_links', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE short_links MODIFY user_id BIGINT UNSIGNED NULL');

        Schema::table('short_links', function (Blueprint $table) {
            $table->text('user_agent')->nullable()->after('original_url');
            $table->string('ip_address', 45)->nullable()->after('user_agent');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('short_links', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'ip_address']);
        });

        DB::statement('ALTER TABLE short_links MODIFY user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('short_links', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
