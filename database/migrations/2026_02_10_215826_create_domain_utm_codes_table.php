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
        Schema::create('domain_utm_codes', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->string("domain_name"); // For matching URLs by host (e.g., "google.com")
            $table->string("utm_key"); // UTM parameter name (e.g., "custom_utm1", "campaign")
            $table->string("utm_value"); // UTM parameter value
            $table->timestamps();

            // Indexes for performance
            $table->index("user_id");
            $table->index("domain_name");
            $table->index(["user_id", "domain_name"]); // Composite index for common queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_utm_codes');
    }
};
