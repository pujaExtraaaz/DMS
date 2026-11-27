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
                'delivery-challans.view',
                'delivery-challans.create',
                'delivery-challans.edit',
                'delivery-challans.manage',
                'delivery-challans.dispatch',
                'delivery-challans.deliver',
                'delivery-challans.cancel',

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

                'service-support.view',
                'service-support.create',
                'service-support.edit',
                'service-support.manage',
                'service-support.assign',
                'service-support.close',
                'service-support.approve-charges',
                'service-support.oem',

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
                'delivery-challans.view',
                'delivery-challans.create',
                'delivery-challans.dispatch',
                'delivery-challans.deliver',

                'targets.view',
                'sales_targets.view',

                'crm.view',
                'crm.create',
                'crm.edit',

                'service-support.view',
                'service-support.create',
                'service-support.edit',
                'service-support.assign',
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
                'receipt-notes.view',
                'receipt-notes.create',
                'receipt-notes.edit',
                'receipt-notes.manage',
                'receipt-notes.delete',
                'receipt-notes.confirm',
                'receipt-notes.cancel',
                'receipt-notes.return',
                'receipt-notes.export',
                'debit-notes.view',
                'debit-notes.create',
                'debit-notes.edit',
                'debit-notes.manage',
                'debit-notes.approve',
                'debit-notes.post',
                'debit-notes.cancel',
                'supplier-payments.view',
                'supplier-payments.create',
                'supplier-payments.edit',
                'supplier-payments.manage',
                'supplier-payments.cancel',
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
                'debit-notes.view',
                'debit-notes.create',
                'debit-notes.edit',
                'debit-notes.manage',
                'debit-notes.approve',
                'debit-notes.post',
                'debit-notes.cancel',
                'supplier-payments.view',
                'supplier-payments.create',
                'supplier-payments.edit',
                'supplier-payments.manage',
                'supplier-payments.cancel',
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
                'receipt-notes.view',
                'delivery-challans.view',
                'delivery-challans.dispatch',
                'delivery-challans.deliver',
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

                'delivery-challans.view',
                'delivery-challans.create',
                'delivery-challans.manage',

                'payments.view',
                'payments.create',
                'payments.edit',
                'payments.manage',
                'payments.reconcile',

                'cheques.view',
                'cheques.manage',
                'credit-notes.view',
                'credit-notes.manage',
                'debit-notes.view',
                'debit-notes.create',
                'debit-notes.manage',
                'debit-notes.approve',
                'debit-notes.post',
                'debit-notes.cancel',
                'supplier-payments.view',
                'supplier-payments.create',
                'supplier-payments.manage',
                'supplier-payments.cancel',

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
                'delivery-challans.view',
                'delivery-challans.dispatch',
                'delivery-challans.deliver',
                'settlement.entry',
            ],

            'delivery-person' => [
                'dashboard.view',
                'logistics.view',
                'delivery.view',
                'delivery.manage',
                'delivery-challans.view',
                'delivery-challans.dispatch',
                'delivery-challans.deliver',
                'settlement.entry',
            ],
        ];

        $knownRoleNames = array_keys($roles);

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

        // Custom / hand-tuned roles: inherit new voucher module perms from related parents.
        Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $knownRoleNames)
            ->each(function (Role $role) {
                $existing = $role->permissions()->pluck('name')->all();
                $extra = $this->inheritedNewModulePermissions($existing);
                if ($extra === []) {
                    return;
                }
                $role->syncPermissions(array_values(array_unique(array_merge($existing, $extra))));
            });
    }

    /**
     * Map related parent permissions → new voucher module permissions for custom roles.
     *
     * @param  list<string>  $existing
     * @return list<string>
     */
    protected function inheritedNewModulePermissions(array $existing): array
    {
        $has = static fn (string $name): bool => in_array($name, $existing, true);
        $extra = [];

        if ($has('purchases.view') || $has('purchase-orders.view') || $has('receipt-notes.view')) {
            $extra = array_merge($extra, [
                'debit-notes.view',
                'supplier-payments.view',
            ]);
        }
        if ($has('purchases.create') || $has('purchase-orders.create') || $has('receipt-notes.create')) {
            $extra = array_merge($extra, [
                'debit-notes.create',
                'supplier-payments.create',
            ]);
        }
        if ($has('purchases.edit') || $has('purchase-orders.edit') || $has('receipt-notes.edit')) {
            $extra = array_merge($extra, [
                'debit-notes.edit',
                'supplier-payments.edit',
            ]);
        }
        if ($has('purchases.manage') || $has('purchase-orders.manage') || $has('receipt-notes.manage')) {
            $extra = array_merge($extra, [
                'debit-notes.manage',
                'debit-notes.approve',
                'debit-notes.post',
                'debit-notes.cancel',
                'supplier-payments.manage',
                'supplier-payments.cancel',
            ]);
        }

        if ($has('payments.view') || $has('credit-notes.view') || $has('accounting.view')) {
            $extra = array_merge($extra, [
                'debit-notes.view',
                'supplier-payments.view',
            ]);
        }
        if ($has('payments.create') || $has('credit-notes.create')) {
            $extra = array_merge($extra, [
                'debit-notes.create',
                'supplier-payments.create',
            ]);
        }
        if ($has('payments.manage') || $has('credit-notes.manage')) {
            $extra = array_merge($extra, [
                'debit-notes.manage',
                'debit-notes.approve',
                'debit-notes.post',
                'debit-notes.cancel',
                'supplier-payments.manage',
                'supplier-payments.cancel',
            ]);
        }

        if ($has('sales.view') || $has('invoices.view') || $has('orders.view') || $has('delivery.view')) {
            $extra[] = 'delivery-challans.view';
        }
        if ($has('sales.create') || $has('invoices.create') || $has('orders.create') || $has('delivery.create')) {
            $extra[] = 'delivery-challans.create';
        }
        if ($has('sales.edit') || $has('invoices.edit') || $has('delivery.edit')) {
            $extra[] = 'delivery-challans.edit';
        }
        if ($has('sales.manage') || $has('invoices.manage') || $has('delivery.manage')) {
            $extra = array_merge($extra, [
                'delivery-challans.manage',
                'delivery-challans.dispatch',
                'delivery-challans.deliver',
                'delivery-challans.cancel',
            ]);
        }

        $catalog = DmsPermissionCatalog::names();

        return array_values(array_filter(
            array_unique($extra),
            fn (string $name) => in_array($name, $catalog, true) && ! in_array($name, $existing, true)
        ));
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
