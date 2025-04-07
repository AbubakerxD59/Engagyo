<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Parent Permissions
            ['name' => 'manage_roles', 'label' => 'Manage Roles', 'parent_id' => '', 'route_name' => 'roles.index', 'role_id' => '1'],

            ['name' => 'manage_permissions', 'label' => 'Manage Permissions', 'parent_id' => '', 'route_name' => 'permissions.index', 'role_id' => '1'],

            ['name' => 'manage_users', 'label' => 'Manage Users', 'parent_id' => '', 'route_name' => 'users'],

            ['name' => 'manage_packages', 'label' => 'Manage Packages', 'parent_id' => '', 'route_name' => 'packages'],

            ['name' => 'manage_features', 'label' => 'Manage Features', 'parent_id' => '', 'route_name' => 'features'],

            ['name' => 'manage_promocodes', 'label' => 'Manage PromoCodes', 'parent_id' => '', 'route_name' => 'promo-code'],

            //Manage Roles
            ['name' => 'view_role', 'label' => 'View Role', 'parent_id' => 'manage_roles', 'route_name' => 'roles.index', 'role_id' => '1'],

            ['name' => 'add_role', 'label' => 'Add Role', 'parent_id' => 'manage_roles', 'route_name' => 'roles.create', 'role_id' => '1'],

            ['name' => 'edit_role', 'label' => 'Edit Role', 'parent_id' => 'manage_roles', 'route_name' => 'roles.edit', 'role_id' => '1'],

            ['name' => 'delete_role', 'label' => 'Delete Role', 'parent_id' => 'manage_roles', 'route_name' => 'roles.destory', 'role_id' => '1'],

            ['name' => 'assign_role_permissions', 'label' => 'Assign Role Permissions', 'parent_id' => 'manage_roles', 'route_name' => 'roles.assign-permissions', 'role_id' => '1'],

            //Manage Permissions
            ['name' => 'view_permission', 'label' => 'View Permission', 'parent_id' => 'manage_permissions', 'route_name' => 'permissions.index', 'role_id' => '1'],

            ['name' => 'add_permission', 'label' => 'Add Permission', 'parent_id' => 'manage_permissions', 'route_name' => 'permissions.create', 'role_id' => '1'],

            ['name' => 'edit_permission', 'label' => 'Edit Permission', 'parent_id' => 'manage_permissions', 'route_name' => 'permissions.edit', 'role_id' => '1'],

            ['name' => 'delete_permission', 'label' => 'Delete Permission', 'parent_id' => 'manage_permissions', 'route_name' => 'permissions.destroy', 'role_id' => '1'],

            //Manage Users
            ['name' => 'view_user', 'label' => 'View User', 'parent_id' => 'manage_users', 'route_name' => 'users.index'],

            ['name' => 'add_user', 'label' => 'Add User', 'parent_id' => 'manage_users', 'route_name' => 'users.create'],

            ['name' => 'edit_user', 'label' => 'Edit User', 'parent_id' => 'manage_users', 'route_name' => 'users.edit'],

            ['name' => 'delete_user', 'label' => 'Delete User', 'parent_id' => 'manage_users', 'route_name' => 'users.destroy'],

            //Manage Package
            ['name' => 'view_package', 'label' => 'View Package', 'parent_id' => 'manage_packages', 'route_name' => 'packages.index'],

            ['name' => 'add_package', 'label' => 'Add Package', 'parent_id' => 'manage_packages', 'route_name' => 'packages.create'],

            ['name' => 'edit_package', 'label' => 'Edit Package', 'parent_id' => 'manage_packages', 'route_name' => 'packages.edit'],

            ['name' => 'delete_package', 'label' => 'Delete Package', 'parent_id' => 'manage_packages', 'route_name' => 'packages.destroy'],

            //Manage Features
            ['name' => 'view_feature', 'label' => 'View Feature', 'parent_id' => 'manage_features', 'route_name' => 'features.index'],

            ['name' => 'add_feature', 'label' => 'Add Feature', 'parent_id' => 'manage_features', 'route_name' => 'features.create'],

            ['name' => 'edit_feature', 'label' => 'Edit Feature', 'parent_id' => 'manage_features', 'route_name' => 'features.edit'],

            ['name' => 'delete_feature', 'label' => 'Delete Feature', 'parent_id' => 'manage_features', 'route_name' => 'features.destroy'],

            // Manage Promo Codes
            ['name' => 'view_promocode', 'label' => 'View Promo Code', 'parent_id' => 'manage_promocodes', 'route_name' => 'promo-code.index'],

            ['name' => 'add_promocode', 'label' => 'Add Promo Code', 'parent_id' => 'manage_promocodes', 'route_name' => 'promo-code.create'],

            ['name' => 'edit_promocode', 'label' => 'Edit Promo Code', 'parent_id' => 'manage_promocodes', 'route_name' => 'promo-code.edit'],

            ['name' => 'delete_promocode', 'label' => 'Delete Promo Code', 'parent_id' => 'manage_promocodes', 'route_name' => 'promo-code.destroy'],

        ];
        try {
            foreach ($permissions as $permission) {
                if (!empty($permission['parent_id'])) {
                    $parent_id = Permission::where('name', $permission['parent_id'])->first();
                    $permission['parent_id'] = $parent_id->id;
                }
                $data = [
                    'label' => $permission['label'],
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                    'parent_id' => $permission['parent_id'],
                    'route_name' => $permission['route_name'],
                    'role_id' => isset($permission['role_id']) ? $permission['role_id'] : '',
                    'created_by' => '1',
                ];
                Permission::updateOrCreate(['name' => $permission['name']], $data);
            }
            // set permissions to Super admin
            $super_admin_role = Role::where('name', 'Super Admin')->first();
            $get_all_permissions = Permission::get()->pluck('id')->toArray();
            $super_admin_role->syncPermissions($get_all_permissions);
            // set permissions to Super admin
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }
}
