<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_member_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_member_id')->constrained('team_members')->onDelete('cascade');
            $table->string('menu_id', 50); // e.g., 'schedule', 'automation', 'api-posts', 'accounts', 'team'
            $table->timestamps();
            
            $table->unique(['team_member_id', 'menu_id']);
            $table->index('team_member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_menus');
    }
};

