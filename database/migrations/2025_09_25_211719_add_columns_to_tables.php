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
        Schema::table('pages', function (Blueprint $table) {
            $table->enum("schedule_status", ["active", "inactive"])->default("inactive")->after("shuffle");
        });
        Schema::table('boards', function (Blueprint $table) {
            $table->enum("schedule_status", ["active", "inactive"])->default("inactive")->after("shuffle");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            //
        });
    }
};
