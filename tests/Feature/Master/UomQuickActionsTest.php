<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UomQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->company = Company::create([
            'name' => 'Uom Quick Co',
            'code' => 'UQC-01',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole($role);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);
    }

    public function test_quick_store_creates_uom(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('masters.uoms.quick-store'), [
            'name' => 'Carton',
            'code' => 'CTN',
            'is_active' => true,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('uoms', ['code' => 'CTN']);
        $this->assertNotEmpty($response->json('item.id'));
        $this->assertStringContainsString('CTN', $response->json('item.name'));
    }

    public function test_quick_destroy_removes_unused_uom(): void
    {
        $uom = Uom::create(['name' => 'Temp Unit', 'code' => 'TMP', 'is_active' => true]);

        $response = $this->actingAs($this->user)->deleteJson(route('masters.uoms.quick-destroy', $uom));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('uoms', ['id' => $uom->id]);
    }

    public function test_quick_destroy_blocks_uom_used_on_product(): void
    {
        $uom = Uom::create(['name' => 'Used Unit', 'code' => 'USED', 'is_active' => true]);
        Product::create([
            'company_id' => $this->company->id,
            'name' => 'Widget',
            'sku' => 'PN-USED-1',
            'serial_no' => 'SN-USED-1',
            'base_uom_id' => $uom->id,
            'tax_rate' => 18,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('masters.uoms.quick-destroy', $uom));

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseHas('uoms', ['id' => $uom->id]);
    }

}
