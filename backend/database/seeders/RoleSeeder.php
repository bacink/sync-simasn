<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions
        $permissions = [
            'kgb.view',
            'kgb.create',
            'kgb.edit',
            'kgb.delete',
            'kgb.submit',
            'kgb.verify',
            'kgb.approve',
            'pmk.view',
            'pmk.create',
            'pmk.edit',
            'pmk.delete',
            'ref_gaji.view',
            'ref_gaji.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->syncPermissions([
            'kgb.view',
            'kgb.create',
            'kgb.edit',
            'kgb.submit',
            'pmk.view',
            'pmk.create',
            'pmk.edit',
            'ref_gaji.view',
        ]);

        $verifikator = Role::firstOrCreate(['name' => 'verifikator']);
        $verifikator->syncPermissions([
            'kgb.view',
            'kgb.verify',
            'pmk.view',
            'ref_gaji.view',
        ]);
    }
}