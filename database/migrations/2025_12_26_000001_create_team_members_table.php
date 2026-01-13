<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_lead_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('member_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('email');
            $table->string('invitation_token', 64)->unique()->nullable();
            $table->enum('status', ['pending', 'active', 'inactive'])->default('pending');
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            
            $table->unique(['team_lead_id', 'email']);
            $table->index(['team_lead_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};

