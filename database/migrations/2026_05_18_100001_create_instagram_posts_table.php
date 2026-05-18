<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained('instagram_accounts')->cascadeOnDelete();
            $table->string('ig_user_id', 64);
            $table->string('ig_media_id', 100);
            $table->string('permalink_url')->nullable();
            $table->string('media_type', 50)->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('saves_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('impressions_count')->default(0);
            $table->unsignedInteger('reach_count')->default(0);
            $table->decimal('engagement_rate', 8, 2)->default(0);
            $table->json('post_data');
            $table->timestamp('post_created_date')->nullable();
            $table->json('post_insights')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['instagram_account_id', 'ig_media_id']);
            $table->index(['ig_user_id', 'post_created_date']);
            $table->index(['instagram_account_id', 'post_created_date']);
            $table->index('post_created_date');
            $table->index('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};
