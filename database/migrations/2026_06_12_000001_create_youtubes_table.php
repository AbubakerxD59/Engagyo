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
        Schema::create('youtubes', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->longText('channel_id');
            $table->string('username');
            $table->string('custom_url')->nullable();
            $table->string('profile_image')->nullable();
            $table->longText('access_token')->nullable();
            $table->longText('expires_in')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->enum('schedule_status', ['active', 'inactive'])->default('inactive');
            $table->boolean('url_shortener_enabled')->default(false);
            $table->timestamp('last_fetch')->nullable();
            $table->integer('shuffle')->default(0);
            $table->boolean('rss_paused')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtubes');
    }
};
