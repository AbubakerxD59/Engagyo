<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Add `reel` to posts.type (Facebook Page Reels API).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `posts` MODIFY COLUMN `type` ENUM('content_only', 'photo', 'link', 'video', 'short', 'story', 'reel') NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `posts` MODIFY COLUMN `type` ENUM('content_only', 'photo', 'link', 'video', 'short', 'story') NULL");
    }
};
