<?php

namespace Database\Seeders;

use App\Models\Password;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin123!'),
                'status' => User::STATUS_ACTIVE,
            ]
        );

        if (!$user->passwords()->where('status', Password::STATUS_ACTIVE)->exists()) {
            $user->passwords()->create([
                'hashed_password' => Hash::make('Admin123!'),
                'status' => Password::STATUS_ACTIVE,
            ]);
        }

        $superAdminRole = Role::where('role_name', 'super_admin')->first();
        if ($superAdminRole && !$user->roles()->where('role_id', $superAdminRole->id)->exists()) {
            $user->roles()->attach($superAdminRole->id);
        }
    }
}
