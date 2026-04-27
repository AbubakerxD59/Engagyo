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
        Schema::create('linkedins', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->longText('linkedin_id');
            $table->string('username');
            $table->string('email')->nullable();
            $table->string('profile_image')->nullable();
            $table->longText('access_token')->nullable();
            $table->longText('expires_in')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('linkedins');
    }
};
