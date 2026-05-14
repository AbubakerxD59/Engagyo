<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinterest_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('pinterest_pin_id', 64);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('link')->nullable();
            $table->string('media_url')->nullable();
            $table->string('pin_type', 50)->nullable();
            $table->unsignedInteger('impressions_count')->default(0);
            $table->unsignedInteger('saves_count')->default(0);
            $table->unsignedInteger('outbound_clicks_count')->default(0);
            $table->unsignedInteger('pin_clicks_count')->default(0);
            $table->unsignedInteger('video_views_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->decimal('engagement_rate', 8, 2)->default(0);
            $table->json('post_data');
            $table->json('post_insights')->nullable();
            $table->timestamp('pin_created_at')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['board_id', 'pinterest_pin_id']);
            $table->index(['board_id', 'pin_created_at']);
            $table->index(['board_id', 'fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinterest_pins');
    }
};
