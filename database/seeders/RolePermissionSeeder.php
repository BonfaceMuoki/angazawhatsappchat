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
            // Chatbot management
            ['permission_name' => 'bot.manage', 'description' => 'Manage chatbot (flows, nodes, edges, settings)', 'is_admin_permission' => true],
            ['permission_name' => 'bot.flows', 'description' => 'Manage chatbot flows', 'is_admin_permission' => true],
            ['permission_name' => 'bot.nodes', 'description' => 'Manage chatbot nodes', 'is_admin_permission' => true],
            ['permission_name' => 'bot.edges', 'description' => 'Manage chatbot edges', 'is_admin_permission' => true],
            ['permission_name' => 'bot.settings', 'description' => 'Manage chatbot settings (e.g. AI)', 'is_admin_permission' => true],
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

        $admin = Role::firstOrCreate(
            ['role_name' => 'admin'],
            ['description' => 'System administrator', 'is_superadmin' => false]
        );

        $customerSupport = Role::firstOrCreate(
            ['role_name' => 'customer_support'],
            ['description' => 'Customer support agent', 'is_superadmin' => false]
        );

        $allPermissionIds = Permission::pluck('id')->toArray();
        $superAdmin->permissions()->syncWithoutDetaching($allPermissionIds);
        $admin->permissions()->syncWithoutDetaching($allPermissionIds);
    }
}
