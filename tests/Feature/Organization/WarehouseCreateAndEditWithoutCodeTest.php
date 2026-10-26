<?php

namespace Tests\Feature\Organization;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseCreateAndEditWithoutCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.edit', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $group = BusinessGroup::create([
            'name' => 'Test Group',
            'code' => 'TG01',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'business_group_id' => $group->id,
            'name' => 'Apex Logistics Corp',
            'code' => 'ALC01',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Mumbai Hub',
            'code' => 'MMH01',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_create_warehouse_form_does_not_contain_code_field(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('organization.warehouses.create'));

        $response->assertOk();
        $response->assertSee('Create Warehouse');
        $response->assertSee('Company');
        $response->assertSee('Branch');
        $response->assertSee('Name');
        $response->assertSee('Address');
        $response->assertSee('Default warehouse');
        $response->assertSee('Active');

        // Verify the code input field is NOT in the form
        $response->assertDontSee('name="code"', false);
    }

    public function test_can_create_warehouse_without_entering_code(): void
    {
        $payload = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Central Cold Storage',
            'address' => 'Plot 42, MIDC, Navi Mumbai',
            'is_default' => '1',
            'is_active' => '1',
            // Note: 'code' is deliberately omitted from payload
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organization.warehouses.store'), $payload);

        $response->assertRedirect(route('organization.warehouses.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('warehouses', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Central Cold Storage',
            'address' => 'Plot 42, MIDC, Navi Mumbai',
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $warehouse = Warehouse::where('name', 'Central Cold Storage')->first();
        $this->assertNotNull($warehouse);
        $this->assertNotEmpty($warehouse->code);
        $this->assertStringStartsWith('WH-', $warehouse->code);
    }

    public function test_edit_warehouse_form_does_not_contain_code_field(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Transit Hub',
            'code' => 'WH-CUSTOM-99',
            'address' => 'Terminal 2',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('organization.warehouses.edit', $warehouse));

        $response->assertOk();
        $response->assertSee('Edit Warehouse');
        $response->assertSee('Transit Hub');
        $response->assertDontSee('name="code"', false);
    }

    public function test_can_edit_existing_warehouse_without_code_field_and_preserve_code(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Primary Depot',
            'code' => 'WH-EXISTING-12345',
            'address' => 'Old Address 123',
            'is_default' => false,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Primary Depot Renamed',
            'address' => 'Updated Address 456',
            'is_default' => '1',
            'is_active' => '1',
            // Note: 'code' is not in request
        ];

        $response = $this->actingAs($this->user)
            ->put(route('organization.warehouses.update', $warehouse), $payload);

        $response->assertRedirect(route('organization.warehouses.index'));
        $response->assertSessionHas('status');

        $warehouse->refresh();
        $this->assertSame('Primary Depot Renamed', $warehouse->name);
        $this->assertSame('Updated Address 456', $warehouse->address);
        $this->assertTrue($warehouse->is_default);
        $this->assertTrue($warehouse->is_active);

        // Crucial: Existing code must NOT be changed or wiped
        $this->assertSame('WH-EXISTING-12345', $warehouse->code);
    }

    public function test_validation_fails_if_required_fields_are_missing(): void
    {
        $payload = [
            'company_id' => '',
            'branch_id' => '',
            'name' => '',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organization.warehouses.store'), $payload);

        $response->assertSessionHasErrors(['company_id', 'branch_id', 'name']);
        // 'code' should NOT be an error since it is no longer required in form
        $response->assertSessionDoesntHaveErrors(['code']);
    }

    public function test_model_auto_generates_code_if_created_programmatically_without_code(): void
    {
        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Auto Coded Warehouse',
            'is_active' => true,
        ]);

        $this->assertNotEmpty($warehouse->code);
        $this->assertStringStartsWith('WH-', $warehouse->code);
    }
}

