<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $roles = Role::$roles_array;
            foreach ($roles as $role) {
                Role::updateOrCreate(
                    ['name' => $role],
                    array(
                        'name' => $role,
                        'guard_name' => 'web',
                    )
                );
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }
}
