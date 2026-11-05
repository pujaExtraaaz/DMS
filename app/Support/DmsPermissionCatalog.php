<?php

namespace App\Support;

/**
 * Canonical DMS Spatie permission modules for Users & Roles and RolePermissionSeeder.
 * Keep existing permission name strings stable; only add new modules/actions.
 */
class DmsPermissionCatalog
{
    /**
     * Modules that receive view/create/edit/manage (and delete where listed in deletes()).
     *
     * @return list<string>
     */
    public static function modules(): array
    {
        return [
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
            'sales_targets',
            'schemes',
            'interest',
            'od',
            'hrms',
            'crm',
            'tally',
            'reports',
        ];
    }

    /**
     * Modules that also need a .delete permission (used in route middleware / policies).
     *
     * @return list<string>
     */
    public static function deleteModules(): array
    {
        return [
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
            'logistics',
            'delivery',
            'settlement',
            'deals',
            'targets',
            'sales_targets',
            'schemes',
            'od',
            'hrms',
            'crm',
        ];
    }

    /**
     * Extra named permissions that are not module.action.
     *
     * @return list<string>
     */
    public static function extras(): array
    {
        return [
            'orders.approve',
            'orders.book',
            'orders.convert',
            'orders.override-credit',
            'orders.back-order',
            'orders.delete',
            'payments.reconcile',
            'settlement.entry',
            'create orders',
            'manage settlements',
            'quotations.view-profit',
            'cheques.unfreeze',
            'accounting.view',
        ];
    }

    /**
     * Edit Perms modal rows: label => permission module key.
     * Same UI (view / create / edit checkboxes); expanded to current modules.
     *
     * @return array<string, string>
     */
    public static function uiModules(): array
    {
        return [
            'Dashboard' => 'dashboard',
            'Organization' => 'organization',
            'Masters' => 'masters',
            'Products' => 'products',
            'Customers' => 'customers',
            'Price Master' => 'price-master',
            'Orders' => 'orders',
            'Quotations' => 'quotations',
            'Inventory' => 'inventory',
            'Stock' => 'stock',
            'Purchases' => 'purchases',
            'Purchase Orders' => 'purchase-orders',
            'Sales' => 'sales',
            'Invoices' => 'invoices',
            'Payments' => 'payments',
            'Cheques' => 'cheques',
            'Credit Notes' => 'credit-notes',
            'Communications' => 'communications',
            'Logistics' => 'logistics',
            'Delivery' => 'delivery',
            'Settlement' => 'settlement',
            'Deals' => 'deals',
            'Targets' => 'targets',
            'Sales Targets' => 'sales_targets',
            'Schemes' => 'schemes',
            'Interest' => 'interest',
            'OD Banking' => 'od',
            'HRMS' => 'hrms',
            'CRM' => 'crm',
            'Accounting / Tally' => 'tally',
            'Reports' => 'reports',
        ];
    }

    /**
     * Actions assignable from the Users Edit Perms modal.
     *
     * @return list<string>
     */
    public static function uiActions(): array
    {
        return ['view', 'create', 'edit'];
    }

    /**
     * All Spatie permission names for DMS modules (before Books catalog merge).
     *
     * @return list<string>
     */
    public static function names(): array
    {
        $permissions = [];

        foreach (self::modules() as $module) {
            foreach (['view', 'create', 'edit', 'manage'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        foreach (self::deleteModules() as $module) {
            $permissions[] = "{$module}.delete";
        }

        return array_values(array_unique(array_merge($permissions, self::extras())));
    }

    /**
     * When a UI module action is granted, also grant related module actions used by middleware.
     *
     * @return list<string>
     */
    public static function linkedPermissions(string $module, string $action): array
    {
        $linked = [];

        if (in_array($module, ['products', 'customers', 'price-master'], true)) {
            $linked[] = "masters.{$action}";
        }

        if (in_array($module, ['stock', 'purchases'], true)) {
            $linked[] = "inventory.{$action}";
        }

        if ($module === 'purchase-orders') {
            $linked[] = "purchases.{$action}";
        }

        if (in_array($module, ['invoices', 'quotations'], true)) {
            $linked[] = "sales.{$action}";
        }

        if (in_array($module, ['cheques', 'credit-notes'], true)) {
            $linked[] = "payments.{$action}";
        }

        if ($module === 'sales_targets') {
            $linked[] = "targets.{$action}";
        }

        if ($module === 'tally' && $action === 'view') {
            $linked[] = 'accounting.view';
        }

        return $linked;
    }
}
