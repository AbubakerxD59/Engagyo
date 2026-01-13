<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_member_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained('team_members')->onDelete('cascade');
            $table->enum('account_type', ['page', 'board', 'tiktok']);
            $table->unsignedBigInteger('account_id');
            $table->timestamps();

            $table->index(['team_member_id', 'account_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_accounts');
    }
};
