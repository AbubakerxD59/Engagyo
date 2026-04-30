<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `posts` MODIFY COLUMN `type` ENUM('content_only', 'photo', 'link', 'video', 'short', 'story', 'reel', 'carousel', 'document') NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `posts` MODIFY COLUMN `type` ENUM('content_only', 'photo', 'link', 'video', 'short', 'story', 'reel', 'carousel') NULL");
    }
};

