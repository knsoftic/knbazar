<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Reference data: the four roles and the assignable admin permissions.
 * Safe to run repeatedly.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = [
            RoleName::SuperAdmin->value => 'Full access to everything',
            RoleName::Admin->value => 'Admin panel access limited by assigned permissions',
            RoleName::Publisher->value => 'Can submit products for review and sell them',
            RoleName::Customer->value => 'Can buy and download products',
        ];

        foreach (RoleName::cases() as $role) {
            Role::query()->updateOrCreate(['slug' => $role->value], [
                'name' => $role->label(),
                'description' => $descriptions[$role->value],
            ]);
        }

        foreach (config('permissions') as $slug => $permission) {
            Permission::query()->updateOrCreate(['slug' => $slug], [
                'name' => $permission['label'],
                'group' => $permission['group'],
            ]);
        }

        Role::flushIdCache();
    }
}
