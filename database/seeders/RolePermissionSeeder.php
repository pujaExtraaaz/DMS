<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    protected array $modules = [
        'dashboard',
        'masters',
        'products',
        'customers',
        'price-master',
        'orders',
        'inventory',
        'stock',
        'purchases',
        'sales',
        'invoices',
        'payments',
        'communications',
        'logistics',
        'delivery',
        'settlement',
        'reports',
    ];

    protected array $extraPermissions = [
        'orders.approve',
        'orders.book',
        'orders.convert',
        'payments.reconcile',
        'settlement.entry',
        'create orders',
        'manage settlements',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]
            ->forgetCachedPermissions();

        $permissions = $this->seedPermissions();

        $roles = [
            'owner' => [
                'dashboard.view',
                'reports.view',
            ],

            'super-admin' => $permissions,

            'sales-manager' => [
                'dashboard.view',

                'masters.view',
                'products.view',
                'customers.view',
                'price-master.view',

                'orders.view',
                'orders.create',
                'orders.edit',
                'orders.book',
                'orders.approve',
                'orders.convert',
                'orders.manage',

                'sales.view',
                'invoices.view',

                'reports.view',
                'reports.manage',
            ],

            'salesperson' => [
                'dashboard.view',

                'masters.view',
                'products.view',
                'customers.view',
                'price-master.view',

                'orders.view',
                'orders.create',
                'orders.edit',
                'orders.book',

                'create orders',

                'sales.view',
                'invoices.view',
            ],

            'warehouse' => [
                'dashboard.view',
                'inventory.view',
                'inventory.manage',
                'stock.view',
                'purchases.view',
            ],

            'finance' => [
                'dashboard.view',

                'sales.view',
                'sales.create',
                'sales.edit',
                'sales.manage',

                'invoices.view',
                'invoices.create',
                'invoices.edit',
                'invoices.manage',

                'payments.view',
                'payments.create',
                'payments.edit',
                'payments.manage',
                'payments.reconcile',

                'settlement.view',
                'settlement.create',
                'settlement.edit',
                'settlement.manage',

                'reports.view',
                'manage settlements',
            ],

            'driver' => [
                'dashboard.view',
                'logistics.view',
                'logistics.manage',
                'delivery.view',
                'delivery.manage',
                'settlement.entry',
            ],

            'delivery-person' => [
                'dashboard.view',
                'logistics.view',
                'delivery.view',
                'delivery.manage',
                'settlement.entry',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }
    }

    protected function seedPermissions(): array
    {
        $permissions = [];

        foreach ($this->modules as $module) {
            foreach (
                ['view', 'create', 'edit', 'manage']
                as $action
            ) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        $permissions = array_merge(
            $permissions,
            $this->extraPermissions
        );

        $permissions = array_values(
            array_unique($permissions)
        );

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        return $permissions;
    }
}