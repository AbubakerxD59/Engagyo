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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thread_posts');
    }
};
