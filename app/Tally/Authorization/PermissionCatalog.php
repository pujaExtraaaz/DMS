<?php

namespace Tally\Authorization;

/**
 * Permission names enforced by the gate. The Super Admin bypasses every check.
 */
final class PermissionCatalog
{
    /**
     * @return array<string, list<string>>
     */
    public static function grouped(): array
    {
        return [
            'Accounting' => ['accounting.view', 'accounting.create', 'accounting.edit', 'accounting.post', 'accounting.cancel'],
            'Sales' => ['sales.view', 'sales.create', 'sales.edit', 'sales.post', 'sales.cancel', 'sales.print'],
            'Purchase' => ['purchase.view', 'purchase.create', 'purchase.edit', 'purchase.post', 'purchase.cancel', 'purchase.print'],
            'Inventory' => ['inventory.view', 'inventory.create', 'inventory.edit'],
            'Manufacturing' => ['manufacturing.view', 'manufacturing.create', 'manufacturing.post', 'manufacturing.cancel'],
            'GST' => ['gst.view'],
            'Banking' => ['banking.view', 'banking.create'],
            'Reports' => ['reports.view', 'reports.export'],
            'Masters' => ['masters.view', 'masters.create', 'masters.edit', 'masters.delete'],
            'Parties' => ['parties.view', 'parties.create', 'parties.edit', 'parties.delete'],
            'Users' => ['users.view', 'users.create', 'users.edit'],
            'Roles' => ['roles.view', 'roles.edit'],
            'Integrations' => ['integrations.view', 'integrations.manage'],
            'Backup' => ['backup.view', 'backup.restore'],
            'Settings' => ['settings.manage'],
            'Exchange' => ['import.run', 'export.run'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_merge(...array_values(self::grouped()));
    }
}
