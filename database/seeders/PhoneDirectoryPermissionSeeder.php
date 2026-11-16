<?php

namespace Database\Seeders;

use App\Support\DmsPermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PhoneDirectoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (DmsPermissionCatalog::names() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(Permission::all());
        }

        $clientAdmin = Role::where('name', 'client-admin')->first();
        if ($clientAdmin) {
            $clientAdmin->givePermissionTo(Permission::where('name', 'like', 'phone_directory.%')->get());
        }

        $salesManager = Role::where('name', 'sales-manager')->first();
        if ($salesManager) {
            $salesManager->givePermissionTo([
                'phone_directory.view',
                'phone_directory.create',
                'phone_directory.edit',
                'phone_directory.export',
            ]);
        }
    }
}
