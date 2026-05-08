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
        Schema::table('linkedins', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('username');
            $table->longText('refresh_token_expires_in')->nullable()->after('refresh_token');
            $table->enum('schedule_status', ['active', 'inactive'])->default('inactive')->after('refresh_token_expires_in');
            $table->boolean('url_shortener_enabled')->default(false)->after('schedule_status');
            $table->timestamp('last_fetch')->nullable()->after('url_shortener_enabled');
            $table->integer('shuffle')->default(0)->after('last_fetch');
            $table->boolean('rss_paused')->default(false)->after('shuffle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('linkedins', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'refresh_token_expires_in',
                'schedule_status',
                'url_shortener_enabled',
                'last_fetch',
                'shuffle',
                'rss_paused',
            ]);
        });
    }
};

