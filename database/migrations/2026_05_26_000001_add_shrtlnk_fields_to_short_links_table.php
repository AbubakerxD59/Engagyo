<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            $table->unsignedBigInteger('shrtlnk_id')->nullable()->after('short_code');
            $table->string('short_url', 2048)->nullable()->after('shrtlnk_id');
        });
    }

    public function down(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            $table->dropColumn(['shrtlnk_id', 'short_url']);
        });
    }
};
