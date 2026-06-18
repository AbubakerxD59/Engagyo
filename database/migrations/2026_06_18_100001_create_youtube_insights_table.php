<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youtube_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youtube_id')->constrained('youtubes')->cascadeOnDelete();
            $table->string('duration', 50);
            $table->date('since');
            $table->date('until');
            $table->json('insights');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['youtube_id', 'since', 'until']);
            $table->index('youtube_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_insights');
    }
};
