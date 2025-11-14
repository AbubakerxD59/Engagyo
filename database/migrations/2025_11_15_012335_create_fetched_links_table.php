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
        Schema::create('fetched_links', function (Blueprint $table) {
            $table->id();
            $table->text("domain")->nullable();
            $table->longText("path")->nullable();
            $table->text("title")->nullable();
            $table->text("image_link")->nullable();
            $table->text("pin_image_link")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fetched_links');
    }
};
