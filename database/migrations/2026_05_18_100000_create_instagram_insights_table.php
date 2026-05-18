<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained('instagram_accounts')->cascadeOnDelete();
            $table->string('duration', 50);
            $table->date('since');
            $table->date('until');
            $table->json('insights');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['instagram_account_id', 'since', 'until']);
            $table->index('instagram_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_insights');
    }
};
