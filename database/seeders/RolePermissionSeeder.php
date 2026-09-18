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
        'organization',
        'masters',
        'products',
        'customers',
        'price-master',
        'orders',
        'quotations',
        'inventory',
        'stock',
        'purchases',
        'purchase-orders',
        'sales',
        'invoices',
        'payments',
        'cheques',
        'credit-notes',
        'communications',
        'logistics',
        'delivery',
        'settlement',
        'deals',
        'targets',
        'schemes',
        'interest',
        'hrms',
        'crm',
        'tally',
        'reports',
    ];

    protected array $extraPermissions = [
        'orders.approve',
        'orders.book',
        'orders.convert',
        'orders.override-credit',
        'orders.back-order',
        'payments.reconcile',
        'settlement.entry',
        'create orders',
        'manage settlements',
        'quotations.view-profit',
        'cheques.unfreeze',
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

            'purchase' => [
                'dashboard.view',
                'masters.view',
                'products.view',
                'customers.view',
                'inventory.view',
                'inventory.manage',
                'stock.view',
                'purchases.view',
                'purchases.create',
                'purchases.edit',
                'purchases.manage',
                'purchase-orders.view',
                'purchase-orders.create',
                'purchase-orders.edit',
                'purchase-orders.manage',
            ],

            'accounts' => [
                'dashboard.view',
                'payments.view',
                'payments.create',
                'payments.edit',
                'payments.manage',
                'payments.reconcile',
                'cheques.view',
                'cheques.create',
                'cheques.edit',
                'cheques.manage',
                'cheques.unfreeze',
                'credit-notes.view',
                'credit-notes.create',
                'credit-notes.edit',
                'credit-notes.manage',
                'interest.view',
                'interest.manage',
                'invoices.view',
                'reports.view',
            ],

            'management' => [
                'dashboard.view',
                'reports.view',
                'reports.manage',
                'targets.view',
                'schemes.view',
                'deals.view',
                'organization.view',
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