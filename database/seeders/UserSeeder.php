<?php

namespace Database\Seeders;

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
                'full_name' => 'Super Admin',
                'email' => 'admin123@gmail.com',
                'password' => 'admin123',
                'status' => '1'
            ]);
            if (! empty($user)) {
                $role = DB::table('roles')->where('name', 'Super Admin')->first();
                if (empty($role)) {
                    $current_date = Carbon::now()->format('Y-m-d H:i:s');
                    $role_id = DB::table('roles')->insertGetId(['name' => 'Super Admin', 'guard_name' => 'web', 'created_by' => '1', 'created_at' => $current_date, 'updated_at' => $current_date]);
                } else {
                    $role_id = isset($role->id) ? $role->id : '';
                }
                if (! empty($role_id)) {
                    $user->assignRole('Super Admin');
                }
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }
}
