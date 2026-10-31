<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DmsPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DmsPermissionCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_covers_current_modules_and_delete_actions(): void
    {
        $names = DmsPermissionCatalog::names();

        $this->assertContains('crm.view', $names);
        $this->assertContains('hrms.manage', $names);
        $this->assertContains('sales_targets.view', $names);
        $this->assertContains('od.manage', $names);
        $this->assertContains('customers.delete', $names);
        $this->assertContains('orders.override-credit', $names);
        $this->assertContains('accounting.view', $names);
        $this->assertSame('sales_targets', DmsPermissionCatalog::uiModules()['Sales Targets']);
    }

    public function test_role_permission_seeder_is_additive_for_non_super_admin_roles(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $role = Role::findByName('salesperson', 'web');
        $custom = Permission::findOrCreate('custom.keep', 'web');
        $role->givePermissionTo($custom);

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('custom.keep'));
        $this->assertTrue($role->hasPermissionTo('sales_targets.view'));
        $this->assertTrue($role->hasPermissionTo('crm.view'));
    }

    public function test_user_permission_modal_accepts_new_modules_without_stripping_manage(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $user = User::factory()->create();
        $user->givePermissionTo(['products.manage', 'crm.view']);

        $response = $this->actingAs($admin)->postJson(route('users.permissions.update', $user), [
            'permissions' => [
                'crm.view',
                'crm.create',
                'sales_targets.view',
                'od.view',
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $user->refresh();
        $this->assertTrue($user->hasDirectPermission('crm.view'));
        $this->assertTrue($user->hasDirectPermission('crm.create'));
        $this->assertTrue($user->hasDirectPermission('sales_targets.view'));
        $this->assertTrue($user->hasDirectPermission('targets.view'));
        $this->assertTrue($user->hasDirectPermission('od.view'));
        $this->assertTrue($user->hasDirectPermission('products.manage'));
    }
}
