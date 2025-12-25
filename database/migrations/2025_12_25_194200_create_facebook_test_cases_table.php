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
        Schema::create('facebook_test_cases', function (Blueprint $table) {
            $table->id();
            $table->enum('test_type', ['image', 'quote', 'link', 'video']);
            $table->enum('status', ['pending', 'passed', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('test_post_id')->nullable();
            $table->unsignedBigInteger('facebook_page_id')->nullable();
            $table->json('test_data')->nullable();
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();

            $table->foreign('test_post_id')->references('id')->on('posts')->onDelete('set null');
            $table->foreign('facebook_page_id')->references('id')->on('pages')->onDelete('set null');
            
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
        Schema::dropIfExists('facebook_test_cases');
    }
};

