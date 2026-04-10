<?php

use App\Models\InstagramAccount;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_accounts', function (Blueprint $table) {
            $table->enum('schedule_status', ['active', 'inactive'])->default('inactive')->after('expires_in');
            $table->boolean('url_shortener_enabled')->default(false)->after('schedule_status');
            $table->timestamp('last_fetch')->nullable()->after('url_shortener_enabled');
            $table->integer('shuffle')->default(0)->after('last_fetch');
            $table->boolean('rss_paused')->default(false)->after('shuffle');
        });
    }

    public function down(): void
    {
        Schema::table('instagram_accounts', function (Blueprint $table) {
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
