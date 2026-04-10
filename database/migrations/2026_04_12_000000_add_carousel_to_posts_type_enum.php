<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Instagram (and future) carousel posts use type=carousel; ENUM must allow it or MySQL stores ''.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `posts` MODIFY COLUMN `type` ENUM('content_only', 'photo', 'link', 'video', 'short', 'story', 'reel', 'carousel') NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `posts` MODIFY COLUMN `type` ENUM('content_only', 'photo', 'link', 'video', 'short', 'story', 'reel') NULL");
    }
};
