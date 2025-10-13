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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->bigInteger("post_id")->nullable();
            $table->bigInteger("account_id")->nullable();
            $table->string("social_type")->nullable();
            $table->enum("type", ["content_only", "photo", "link", "video", "short", "story"])->nullable();
            $table->string("source")->nullable();
            $table->text("title")->nullable();
            $table->longText("description")->nullable();
            $table->longText("comment")->nullable();
            $table->integer("domain_id")->nullable();
            $table->text("url")->nullable();
            $table->text("image")->nullable();
            $table->text("publish_date")->nullable();
            $table->integer("status")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
