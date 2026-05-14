<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('duration', 50);
            $table->date('since');
            $table->date('until');
            $table->json('insights');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['board_id', 'since', 'until']);
            $table->index('board_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_insights');
    }
};
