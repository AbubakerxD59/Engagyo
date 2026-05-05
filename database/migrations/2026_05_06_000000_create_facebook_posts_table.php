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
        Schema::create('facebook_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fb_page_id');
            $table->string('fb_post_id', 100);
            $table->string('permalink_url')->nullable();
            $table->string('status_type', 100)->nullable();
            $table->string('post_type', 50)->nullable();
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->unsignedInteger('impressions_count')->default(0);
            $table->unsignedInteger('reach_count')->default(0);
            $table->decimal('engagement_rate', 8, 2)->default(0);
            $table->json('post_data');
            $table->timestamp('post_created_date')->nullable();
            $table->json('post_insights')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['fb_page_id', 'fb_post_id']);
            $table->index(['fb_page_id', 'post_created_date']);
            $table->index(['fb_page_id', 'fetched_at']);
            $table->index('post_created_date');
            $table->index('fetched_at');
            $table->index('status_type');
            $table->index('post_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_posts');
    }
};
