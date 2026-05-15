<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiktok_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiktok_id')->constrained('tiktoks')->cascadeOnDelete();
            $table->string('tiktok_video_id', 100);
            $table->string('share_url')->nullable();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            $table->json('post_data');
            $table->timestamp('post_created_date')->nullable();
            $table->json('post_insights')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['tiktok_id', 'tiktok_video_id']);
            $table->index(['tiktok_id', 'post_created_date']);
            $table->index('post_created_date');
            $table->index('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_posts');
    }
};
