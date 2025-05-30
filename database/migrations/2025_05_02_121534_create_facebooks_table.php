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
        Schema::create('facebooks', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->longText("fb_id");
            $table->string("username");
            $table->string("profile_image")->nullable();
            $table->longText('access_token');
            $table->longText('expires_in');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebooks');
    }
};
