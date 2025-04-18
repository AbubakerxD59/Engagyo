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
        Schema::create('pinterests', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->longText("pin_id");
            $table->string("username");
            $table->longText("about")->nullable();
            $table->string("profile_image")->nullable();
            $table->integer('board_count')->default(0);
            $table->integer('pin_count')->default(0);
            $table->bigInteger('following_count')->default(0);
            $table->bigInteger('follower_count')->default(0);
            $table->bigInteger('monthly_views')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinterests');
    }
};
