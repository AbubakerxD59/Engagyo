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
        Schema::table('threads', function (Blueprint $table) {
            $table->string('schedule_status')->default('inactive')->after('refresh_token');
            $table->boolean('url_shortener_enabled')->default(false)->after('schedule_status');
            $table->timestamp('last_fetch')->nullable()->after('url_shortener_enabled');
            $table->boolean('shuffle')->default(false)->after('last_fetch');
            $table->boolean('rss_paused')->default(false)->after('shuffle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropColumn([
                'schedule_status',
                'url_shortener_enabled',
                'last_fetch',
                'shuffle',
                'rss_paused',
            ]);
        });
    }
};
