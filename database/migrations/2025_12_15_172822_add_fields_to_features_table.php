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
        Schema::table('features', function (Blueprint $table) {
            $table->string('key')->unique()->nullable()->after('id');
            $table->enum('type', ['boolean', 'numeric', 'unlimited'])->default('boolean')->after('key');
            $table->integer('default_value')->nullable()->after('type');
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn(['key', 'type', 'default_value', 'description', 'is_active']);
        });
    }
};
