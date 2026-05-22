<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->timestamp('expiration_warning_sent_at')->nullable()->after('expires_at');
            $table->timestamp('expiration_expired_sent_at')->nullable()->after('expiration_warning_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropColumn(['expiration_warning_sent_at', 'expiration_expired_sent_at']);
        });
    }
};
