<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'group_dashboard.view',
            'group_dashboard.combined.view',
            'group_dashboard.profit_loss.view',
            'group_dashboard.inter_company.view',
            'group_dashboard.export',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Grant to super-admin, client-admin, owner
        $superAdmin = Role::where(['name' => 'super-admin', 'guard_name' => 'web'])->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $clientAdmin = Role::where(['name' => 'client-admin', 'guard_name' => 'web'])->first();
        if ($clientAdmin) {
            $clientAdmin->givePermissionTo($permissions);
        }

        $owner = Role::where(['name' => 'owner', 'guard_name' => 'web'])->first();
        if ($owner) {
            $owner->givePermissionTo([
                'group_dashboard.view',
                'group_dashboard.combined.view',
                'group_dashboard.profit_loss.view',
                'group_dashboard.inter_company.view',
                'group_dashboard.export',
            ]);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'group_dashboard.view',
            'group_dashboard.combined.view',
            'group_dashboard.profit_loss.view',
            'group_dashboard.inter_company.view',
            'group_dashboard.export',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
