<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Converts jobs and failed_jobs tables to utf8mb4 for compatibility
     * when job payloads contain emoji/4-byte characters from posts.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE jobs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        DB::statement('ALTER TABLE failed_jobs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE jobs CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        DB::statement('ALTER TABLE failed_jobs CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
    }
};
