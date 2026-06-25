<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->enum('facebook_posting_status', ['active', 'paused', 'testing', 'stopped'])
                ->default('active')
                ->after('rss_paused');
            $table->timestamp('facebook_posting_paused_until')->nullable()->after('facebook_posting_status');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['facebook_posting_status', 'facebook_posting_paused_until']);
        });
    }
};
