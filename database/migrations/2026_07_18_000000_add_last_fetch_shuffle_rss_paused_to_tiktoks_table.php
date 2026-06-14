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
            $table->timestamp('last_fetch')->nullable()->after('schedule_status');
            $table->integer('shuffle')->default(0)->after('last_fetch');
            $table->boolean('rss_paused')->default(false)->after('shuffle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiktoks', function (Blueprint $table) {
            $table->dropColumn(['last_fetch', 'shuffle', 'rss_paused']);
        });
    }
};
