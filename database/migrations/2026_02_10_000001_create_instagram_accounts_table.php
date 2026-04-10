<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facebook_id')->nullable()->constrained('facebooks')->nullOnDelete();
            $table->string('page_id')->comment('Facebook Page ID linked to this Instagram Business account');
            $table->string('ig_user_id')->comment('Instagram Business account ID from Graph API');
            $table->string('username')->nullable();
            $table->string('name')->nullable();
            $table->string('profile_image')->nullable();
            $table->longText('access_token')->nullable()->comment('Page access token for Instagram API');
            $table->longText('expires_in')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'ig_user_id']);
            $table->index(['user_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_accounts');
    }
};
