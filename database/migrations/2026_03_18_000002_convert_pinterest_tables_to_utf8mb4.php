<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Converts pinterests and boards tables to utf8mb4 for proper emoji storage,
     * same as done for the posts table.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE pinterests CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        DB::statement('ALTER TABLE boards CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE pinterests CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        DB::statement('ALTER TABLE boards CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    }
};
