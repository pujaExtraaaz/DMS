<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    protected array $modules = [
        'dashboard',
        'masters',
        'orders',
        'inventory',
        'sales',
        'payments',
        'communications',
        'logistics',
        'delivery',
        'settlement',
        'reports',
    ];

    /**
     * @var list<string>
     */
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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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
                'orders.view',
                'orders.approve',
                'orders.manage',
                'reports.view',
                'reports.manage',
            ],
            'salesperson' => [
                'dashboard.view',
                'masters.view',
                'orders.view',
                'orders.book',
                'orders.manage',
                'create orders',
            ],
            'warehouse' => [
                'dashboard.view',
                'inventory.view',
                'inventory.manage',
            ],
            'finance' => [
                'dashboard.view',
                'sales.view',
                'sales.manage',
                'payments.view',
                'payments.manage',
                'payments.reconcile',
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
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );

            $role->syncPermissions($rolePermissions);
        }
    }

    /**
     * @return list<string>
     */
    protected function seedPermissions(): array
    {
        $permissions = [];

        foreach ($this->modules as $module) {
            foreach (['view', 'manage'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        $permissions = array_merge($permissions, $this->extraPermissions);
        $permissions = array_values(array_unique($permissions));

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
            );
        }

        return $permissions;
    }
}
