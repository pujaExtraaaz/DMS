<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_and_soft_delete_a_user(): void
    {
        $super = User::factory()->create();
        $super->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));
        Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);

        $response = $this->actingAs($super)->post('/users', [
            'form' => 'create',
            'name' => 'New Cashier',
            'phone' => '9876543210',
            'email' => 'cashier@example.com',
            'role' => 'salesperson',
            'password' => 'secret123',
            'permissions' => ['orders.view', 'orders.create'],
        ]);

        $response->assertRedirect();

        $created = User::where('email', 'cashier@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame('9876543210', $created->phone);
        $this->assertNull($created->avatar);
        $this->assertTrue($created->hasRole('salesperson'));
        $this->assertTrue($created->hasDirectPermission('orders.view'));

        $this->actingAs($super)->delete('/users/'.$created->id)->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $created->id]);
        $this->assertNull(User::where('email', 'cashier@example.com')->first());
    }

    public function test_client_admin_can_edit_name_email_and_role_but_cannot_create_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'client-admin', 'guard_name' => 'web']));
        Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);
        $warehouse = Role::firstOrCreate(['name' => 'warehouse', 'guard_name' => 'web']);

        $target = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);
        $target->assignRole('salesperson');

        $this->actingAs($admin)->post('/users', [
            'name' => 'Blocked',
            'phone' => '9999999999',
            'email' => 'blocked@example.com',
            'role' => 'salesperson',
            'password' => 'secret123',
            'permissions' => ['orders.view'],
        ])->assertForbidden();

        $this->actingAs($admin)->put('/users/'.$target->id, [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'role' => $warehouse->name,
        ])->assertRedirect();

        $target->refresh();
        $this->assertSame('New Name', $target->name);
        $this->assertSame('new@example.com', $target->email);
        $this->assertTrue($target->hasRole('warehouse'));
    }

    public function test_users_page_includes_select_all_for_permissions(): void
    {
        $super = User::factory()->create();
        $super->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $this->actingAs($super)
            ->get('/users')
            ->assertOk()
            ->assertSee('Select all')
            ->assertSee('Add User');
    }
}
