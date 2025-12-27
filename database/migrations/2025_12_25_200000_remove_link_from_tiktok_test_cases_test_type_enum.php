<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to modify the enum by altering the column
        // First, update any existing 'link' records to 'image' (or handle as needed)
        DB::table('tiktok_test_cases')
            ->where('test_type', 'link')
            ->update(['test_type' => 'image']);

        // Alter the enum column to remove 'link'
        DB::statement("ALTER TABLE tiktok_test_cases MODIFY COLUMN test_type ENUM('image', 'video') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the enum to include 'link'
        DB::statement("ALTER TABLE tiktok_test_cases MODIFY COLUMN test_type ENUM('image', 'link', 'video') NOT NULL");
    }
};

