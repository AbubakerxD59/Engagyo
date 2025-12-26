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
        Schema::create('tiktok_test_cases', function (Blueprint $table) {
            $table->id();
            $table->enum('test_type', ['image', 'link', 'video']);
            $table->enum('status', ['pending', 'passed', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('test_post_id')->nullable();
            $table->unsignedBigInteger('tiktok_account_id')->nullable();
            $table->json('test_data')->nullable();
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();

            $table->foreign('test_post_id')->references('id')->on('posts')->onDelete('set null');
            $table->foreign('tiktok_account_id')->references('id')->on('tiktoks')->onDelete('set null');
            
            $table->index('test_type');
            $table->index('status');
            $table->index('ran_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_test_cases');
    }
};

