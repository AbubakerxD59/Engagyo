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
        Schema::create('tiktoks', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->longText("tiktok_id");
            $table->string("username");
            $table->string("display_name")->nullable();
            $table->string("profile_image")->nullable();
            $table->longText('access_token')->nullable();
            $table->longText('expires_in')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->longText('refresh_token_expires_in')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktoks');
    }
};
