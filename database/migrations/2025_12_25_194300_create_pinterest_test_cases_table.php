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
        Schema::create('pinterest_test_cases', function (Blueprint $table) {
            $table->id();
            $table->enum('test_type', ['image', 'link', 'video']);
            $table->enum('status', ['pending', 'passed', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('test_post_id')->nullable();
            $table->unsignedBigInteger('pinterest_board_id')->nullable();
            $table->json('test_data')->nullable();
            $table->timestamp('ran_at')->nullable();
            $table->timestamps();

            $table->foreign('test_post_id')->references('id')->on('posts')->onDelete('set null');
            $table->foreign('pinterest_board_id')->references('id')->on('boards')->onDelete('set null');
            
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
        Schema::dropIfExists('pinterest_test_cases');
    }
};

