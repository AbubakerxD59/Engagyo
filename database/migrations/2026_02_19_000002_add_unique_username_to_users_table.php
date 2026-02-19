<?php

use App\Models\User;
use App\Services\UsernameService;
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
        // Ensure all existing users have unique 6-char alphabetical usernames before adding constraint
        $users = User::orderBy('id')->get();
        $used = [];
        foreach ($users as $user) {
            $current = $user->username;
            if (empty($current) || !preg_match('/^[a-z]{6}$/', $current) || isset($used[$current])) {
                $user->username = UsernameService::generate($user->email ?? 'user@example.com');
                $user->save();
            }
            $used[$user->username] = true;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });
    }
};
