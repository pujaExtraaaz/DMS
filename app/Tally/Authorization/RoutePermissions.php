<?php

namespace Tally\Authorization;

/**
 * Maps a route name to the permission a non-owner must hold.
 * Routes that return null stay available to every signed-in user.
 */
final class RoutePermissions
{
    public static function for(?string $name, string $method = 'GET'): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }

        if ($name === 'products.barcode' || $name === 'barcodes.lookup') {
            return 'inventory.view';
        }

        $name = str_starts_with($name, 'api.v1.') ? substr($name, 7) : $name;

        foreach (self::modules() as $prefix => $module) {
            if ($name === $prefix || str_starts_with($name, $prefix.'.') || ($prefix === 'stock.' && str_starts_with($name, 'stock.'))) {
                return self::permission($module, $name, $method);
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private static function modules(): array
    {
        return [
            'invoices.credit-note' => 'sales',
            'invoices.debit-note' => 'purchase',
            'invoices.sales' => 'sales',
            'invoices.purchase' => 'purchase',
            'purchase-orders' => 'purchase',
            'sales-orders' => 'sales',
            'payroll' => 'accounting',
            'payment-requests' => 'accounting',
            'bank-statements' => 'banking',
            'stock-movements' => 'inventory',
            'stock.' => 'inventory',
            'manufacturing' => 'manufacturing',
            'reports.production' => 'manufacturing',
            'reports.consumption' => 'manufacturing',
            'reports.gst' => 'gst',
            'reports.gstr-1' => 'gst',
            'reports.gstr-3b' => 'gst',
            'reports' => 'reports',
            'voucher-entries' => 'accounting',
            'vouchers' => 'accounting',
            'banking' => 'banking',
            'utilities.import' => 'import',
            'utilities.export' => 'export',
            'utilities.backup' => 'backup',
            'account-groups' => 'masters',
            'ledgers' => 'masters',
            'units' => 'masters',
            'product-groups' => 'masters',
            'products' => 'masters',
            'godowns' => 'masters',
            'tax-categories' => 'masters',
            'tax-rates' => 'masters',
            'hsn-sacs' => 'masters',
            'hsn-catalogue' => 'masters',
            'price-lists' => 'masters',
            'tax-accounts' => 'masters',
            'currencies' => 'masters',
            'voucher-types' => 'masters',
            'gst-registrations' => 'masters',
            'merchant-profiles' => 'masters',
            'employees' => 'masters',
            'pay-heads' => 'masters',
            'deduction-sections' => 'masters',
            'boms' => 'masters',
            'cost-categories' => 'masters',
            'cost-centres' => 'masters',
            'budgets' => 'masters',
            'companies' => 'settings',
            'branches' => 'settings',
            'financial-years' => 'settings',
            'settings.api-tokens' => 'settings',
            'audit' => 'settings',
            'settings.shortcuts' => 'settings',
            'parties' => 'parties',
            'users' => 'users',
            'roles' => 'roles',
            'preferences' => 'settings',
            'integrations' => 'integrations',
            'webhooks' => 'integrations',
            'webhook-deliveries' => 'integrations',
            'outstanding' => 'reports',
        ];
    }

    private static function permission(string $module, string $name, string $method): string
    {
        $action = self::action($name, $method);

        if ($module === 'settings') {
            return 'settings.manage';
        }

        if ($module === 'import') {
            return 'import.run';
        }

        if ($module === 'export') {
            return 'export.run';
        }

        if ($module === 'gst') {
            return 'gst.view';
        }

        if ($module === 'reports') {
            return 'reports.view';
        }

        if ($module === 'backup') {
            return str_contains($name, 'restore') ? 'backup.restore' : 'backup.view';
        }

        if ($module === 'integrations') {
            return $action === 'view' ? 'integrations.view' : 'integrations.manage';
        }

        if ($module === 'roles') {
            return $action === 'view' ? 'roles.view' : 'roles.edit';
        }

        if ($module === 'users') {
            return match ($action) {
                'view' => 'users.view',
                'create' => 'users.create',
                default => 'users.edit',
            };
        }

        if ($module === 'banking') {
            return $action === 'view' ? 'banking.view' : 'banking.create';
        }

        if ($module === 'inventory' && ! in_array($action, ['view', 'create', 'edit'], true)) {
            $action = 'edit';
        }

        if ($module === 'manufacturing' && $action === 'edit') {
            $action = 'create';
        }

        if ($module === 'accounting' && $action === 'delete') {
            $action = 'edit';
        }

        if (in_array($module, ['sales', 'purchase'], true) && $action === 'delete') {
            $action = 'edit';
        }

        return $module.'.'.$action;
    }

    private static function action(string $name, string $method): string
    {
        $method = strtoupper($method);

        if (str_ends_with($name, '.print') || str_ends_with($name, '.pdf')) {
            return 'print';
        }

        if (str_ends_with($name, '.post')) {
            return 'post';
        }

        if (str_ends_with($name, '.cancel')) {
            return 'cancel';
        }

        if (str_ends_with($name, '.destroy') || $method === 'DELETE') {
            return 'delete';
        }

        if (str_ends_with($name, '.create') || str_ends_with($name, '.store') || $method === 'POST') {
            return 'create';
        }

        if (str_ends_with($name, '.edit') || str_ends_with($name, '.update') || str_ends_with($name, '.activation') || in_array($method, ['PUT', 'PATCH'], true)) {
            return 'edit';
        }

        return 'view';
    }
}
