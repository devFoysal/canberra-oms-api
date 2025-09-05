<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache before seeding
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // === Define Resources ===
        $resources = [
            'category',
            'product',
            'user',
            'customer',
            'sales representative',
        ];

        // === Define Actions ===
        $actions = ['view', 'create', 'edit', 'delete'];

        // === Create Permissions ===
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "$action $resource"]);
            }
        }

        // === Create Roles ===
        $adminRole  = Role::firstOrCreate(['name' => 'admin']);
        $salesRole  = Role::firstOrCreate(['name' => 'sales_representative']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);

        // === Assign Permissions to Roles ===
        $adminRole->givePermissionTo(Permission::all()); // full access

        $salesRole->givePermissionTo([
            'view customer',
            'create customer',
            'edit customer',
            'view product',
            'view category',
        ]);

        $managerRole->givePermissionTo([
            'view product',
            'create product',
            'edit product',
            'delete product',
            'view category',
            'create category',
            'edit category',
        ]);

        // === Create Users & Assign Roles ===
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@canberra.com'],
            [
                'full_name' => 'Admin',
                'password' => bcrypt('password'), // change to secure password
            ]
        );
        $adminUser->assignRole($adminRole);

        $salesUser = User::firstOrCreate(
            ['email' => 'sales@canberra.com'],
            [
                'full_name' => 'Sales',
                'password' => bcrypt('password'),
            ]
        );
        $salesUser->assignRole($salesRole);

        $managerUser = User::firstOrCreate(
            ['email' => 'manager@canberra.com'],
            [
                'full_name' => 'Manager',
                'password' => bcrypt('password'),
            ]
        );
        $managerUser->assignRole($managerRole);
    }
}
