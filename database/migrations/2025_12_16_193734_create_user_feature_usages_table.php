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
        Schema::create('user_feature_usages', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id")->index();
            $table->integer("feature_id")->index();
            $table->integer("usage_count")->default(0);
            $table->integer("is_unlimited")->default(0);
            $table->integer("period_start")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feature_usages');
    }
};
