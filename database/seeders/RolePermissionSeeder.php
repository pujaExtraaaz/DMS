<?php

namespace Database\Seeders;

use App\Support\DmsPermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tally\Authorization\PermissionCatalog;

class RolePermissionSeeder extends Seeder
{
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
                'customers.create',
                'customers.edit',
                'price-master.view',

                'orders.view',
                'orders.create',
                'orders.edit',
                'orders.book',
                'orders.approve',
                'orders.convert',
                'orders.manage',

                'quotations.view',
                'quotations.create',
                'quotations.edit',

                'sales.view',
                'invoices.view',

                'targets.view',
                'targets.manage',
                'sales_targets.view',
                'sales_targets.create',
                'sales_targets.edit',
                'sales_targets.manage',

                'crm.view',
                'crm.create',
                'crm.edit',
                'crm.manage',

                'deals.view',
                'schemes.view',

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

                'quotations.view',
                'quotations.create',

                'sales.view',
                'invoices.view',

                'targets.view',
                'sales_targets.view',

                'crm.view',
                'crm.create',
                'crm.edit',
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
                'od.view',
                'od.create',
                'od.edit',
                'od.manage',
                'invoices.view',
                'tally.view',
                'accounting.view',
                'reports.view',
            ],

            'management' => [
                'dashboard.view',
                'reports.view',
                'reports.manage',
                'targets.view',
                'sales_targets.view',
                'schemes.view',
                'deals.view',
                'organization.view',
                'od.view',
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

                'cheques.view',
                'cheques.manage',
                'credit-notes.view',
                'credit-notes.manage',

                'settlement.view',
                'settlement.create',
                'settlement.edit',
                'settlement.manage',

                'od.view',
                'od.manage',
                'tally.view',
                'accounting.view',

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

            // Super-admin always mirrors the full catalog.
            // Other roles: grant the template set without stripping any
            // permissions already assigned to that role outside this seeder.
            if ($roleName === 'super-admin') {
                $role->syncPermissions($rolePermissions);
                continue;
            }

            $existing = $role->permissions()->pluck('name')->all();
            $merged = array_values(array_unique(array_merge($existing, $rolePermissions)));
            $role->syncPermissions($merged);
        }
    }

    protected function seedPermissions(): array
    {
        $permissions = array_values(array_unique(array_merge(
            DmsPermissionCatalog::names(),
            PermissionCatalog::all(),
        )));

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        return $permissions;
    }
}
