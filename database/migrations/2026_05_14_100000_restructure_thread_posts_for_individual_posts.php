<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Threads posts are stored one row per external post (like facebook_posts),
     * not one JSON blob per date-range preset.
     */
    public function up(): void
    {
        Schema::dropIfExists('thread_posts');

        Schema::create('thread_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->cascadeOnDelete();
            $table->string('threads_post_id', 128);
            $table->string('permalink_url')->nullable();
            $table->string('media_type', 50)->nullable();
            $table->unsignedInteger('impressions_count')->default(0);
            $table->unsignedInteger('reach_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->decimal('engagement_rate', 8, 2)->default(0);
            $table->json('post_data');
            $table->timestamp('post_created_date')->nullable();
            $table->json('post_insights')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['thread_id', 'threads_post_id']);
            $table->index(['thread_id', 'post_created_date']);
            $table->index(['thread_id', 'fetched_at']);
            $table->index('post_created_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thread_posts');

        Schema::create('thread_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->cascadeOnDelete();
            $table->string('duration', 50);
            $table->date('since');
            $table->date('until');
            $table->json('posts');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['thread_id', 'since', 'until']);
            $table->index('thread_id');
        });
    }
};
