<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['permission_name' => 'users.create', 'description' => 'Create users', 'is_admin_permission' => true],
            ['permission_name' => 'users.update', 'description' => 'Update users', 'is_admin_permission' => true],
            ['permission_name' => 'users.block', 'description' => 'Block users', 'is_admin_permission' => true],
            ['permission_name' => 'roles.assign', 'description' => 'Assign roles', 'is_admin_permission' => true],
            ['permission_name' => 'permissions.assign', 'description' => 'Assign permissions', 'is_admin_permission' => true],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['permission_name' => $p['permission_name']],
                $p
            );
        }

        $superAdmin = Role::firstOrCreate(
            ['role_name' => 'super_admin'],
            ['description' => 'Super administrator', 'is_superadmin' => true]
        );

        $customerSupport = Role::firstOrCreate(
            ['role_name' => 'customer_support'],
            ['description' => 'Customer support agent', 'is_superadmin' => false]
        );

        $allPermissionIds = Permission::pluck('id')->toArray();
        $superAdmin->permissions()->syncWithoutDetaching($allPermissionIds);
    }
}
