<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Company permissions
            'view companies',
            'create companies',
            'update companies',
            'delete companies',
            
            // User permissions
            'view users',
            'create users',
            'update users',
            'delete users',
            
            // Project permissions
            'view projects',
            'create projects',
            'update projects',
            'delete projects',
            
            // Task permissions
            'view tasks',
            'create tasks',
            'update tasks',
            'delete tasks',
            'assign tasks',
            
            // General permissions
            'view dashboard',
            'view reports',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view companies',
            'update companies',
            'view users',
            'create users',
            'update users',
            'view projects',
            'create projects',
            'update projects',
            'view tasks',
            'create tasks',
            'update tasks',
            'assign tasks',
            'view dashboard',
            'view reports',
        ]);

        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view users',
            'view projects',
            'create projects',
            'update projects',
            'view tasks',
            'create tasks',
            'update tasks',
            'assign tasks',
            'view dashboard',
        ]);

        $employee = Role::create(['name' => 'employee']);
        $employee->givePermissionTo([
            'view projects',
            'view tasks',
            'update tasks',
            'view dashboard',
        ]);

        $client = Role::create(['name' => 'client']);
        $client->givePermissionTo([
            'view projects',
            'view tasks',
        ]);
    }
}
