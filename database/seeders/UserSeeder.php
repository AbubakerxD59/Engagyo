<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $user = User::firstOrCreate(['email' => 'admin123@gmail.com'], [
                'first_name' => 'Super ',
                'last_name' => 'Admin',
                'email' => 'admin123@gmail.com',
                'password' => 'admin123',
                'status' => '1'
            ]);
            if (! empty($user)) {
                $role = Role::where("name", "Super Admin")->first();
                if ($role) {
                    $user->assignRole($role->name);
                }
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }
}
