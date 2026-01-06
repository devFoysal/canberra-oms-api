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
            'order',
            'invoice',
            'payment',
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
        $superAdminRole  = Role::firstOrCreate(['name' => 'super_admin']);
        $adminRole  = Role::firstOrCreate(['name' => 'admin']);
        $salesRole  = Role::firstOrCreate(['name' => 'sales_representative']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);

        // === Assign Permissions to Roles ===
        $superAdminRole->givePermissionTo(Permission::all()); // full access

        $adminRole->givePermissionTo([
            'view product',
            'create product',
            'edit product',
            'delete product',
            'view category',
            'create category',
            'edit category',
            'view order',
            'edit order',
            'view invoice',
        ]);

        // === Create Users & Assign Roles ===
        $superAdminUser = User::firstOrCreate(
            ['email' => 'sales@canberralimited.com'],
            [
                'full_name' => 'Admin',
                'password' => bcrypt('@canberra'), // change to secure password
            ]
        );
        $superAdminUser->assignRole($superAdminRole);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@canberra.com'],
            [
                'full_name' => 'Admin',
                'password' => bcrypt('password'), // change to secure password
            ]
        );
        $adminUser->assignRole($adminRole);
    }
}
