<?php

namespace Tests\Feature\Inventory;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockAdjustmentItem;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockAdjustmentEditAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $unauthorizedUser;
    protected Company $company;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected Uom $boxUom;
    protected Brand $brand;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->user = User::first() ?? User::factory()->create();
        if (! $this->user->hasRole('super-admin')) {
            $this->user->assignRole($superAdminRole);
        }

        $this->unauthorizedUser = User::factory()->create();

        $this->company = Company::create([
            'name' => 'Test Corp',
            'code' => 'TC-01',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $this->boxUom = Uom::create([
            'name' => 'Box',
            'code' => 'BOX',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'name' => 'Apex Brand',
            'code' => 'APX-01',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Industrial Parts',
            'code' => 'IND-01',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Bearing Widget',
            'sku' => 'BW-100',
            'serial_no' => 'SN-BW-100',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'base_uom_id' => $this->boxUom->id,
            'purchase_price' => 50.00,
            'selling_price' => 75.00,
            'is_active' => true,
        ]);
    }

    public function test_index_page_displays_view_edit_and_delete_actions(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-0001',
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'damage',
            'status' => 'draft',
            'notes' => 'Test draft adjustment',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.adjustments.index'));
        $response->assertStatus(200);
        $response->assertSee('View');
        $response->assertSee('Edit');
        $response->assertSee('Delete');
        $response->assertSee('ADJ-2026-0001');
    }

    public function test_can_view_edit_page_for_draft_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-0002',
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'shrinkage',
            'status' => 'draft',
            'notes' => 'Draft adjustment to edit',
            'created_by' => $this->user->id,
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => -5,
            'notes' => 'Damaged in transit',
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.adjustments.edit', $adjustment));
        $response->assertStatus(200);
        $response->assertSee('ADJ-2026-0002');
        $response->assertSee('Save as Draft');
        $response->assertSee('Post Adjustment');
    }

    public function test_cannot_view_edit_page_for_posted_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-0003',
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'found',
            'status' => 'posted',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('inventory.adjustments.edit', $adjustment));
        $response->assertRedirect(route('inventory.adjustments.show', $adjustment));
        $response->assertSessionHas('error');
    }

    public function test_can_update_draft_adjustment_preserving_id_and_reference_number(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-0004',
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'recount',
            'status' => 'draft',
            'notes' => 'Initial notes',
            'created_by' => $this->user->id,
        ]);

        $originalId = $adjustment->id;

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => 2,
        ]);

        $updateData = [
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'found',
            'notes' => 'Updated draft notes',
            'action' => 'draft',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->boxUom->id,
                    'quantity' => 5,
                    'notes' => 'Found 5 items during audit',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('inventory.adjustments.update', $adjustment), $updateData);
        $response->assertRedirect(route('inventory.adjustments.show', $adjustment));

        $adjustment->refresh();
        $this->assertEquals($originalId, $adjustment->id);
        $this->assertEquals('ADJ-2026-0004', $adjustment->adjustment_no);
        $this->assertEquals('found', $adjustment->reason);
        $this->assertEquals('draft', $adjustment->status);
        $this->assertEquals('Updated draft notes', $adjustment->notes);

        $this->assertDatabaseHas('stock_adjustment_items', [
            'stock_adjustment_id' => $adjustment->id,
            'quantity' => 5,
            'notes' => 'Found 5 items during audit',
        ]);

        // Verify no stock movements recorded while in draft
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => $adjustment->getMorphClass(),
            'reference_id' => $adjustment->id,
        ]);
    }

    public function test_can_post_draft_adjustment_recording_stock_movements(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-0005',
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'found',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'found',
            'notes' => 'Posting now',
            'action' => 'post',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->boxUom->id,
                    'quantity' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('inventory.adjustments.update', $adjustment), $updateData);
        $response->assertRedirect(route('inventory.adjustments.show', $adjustment));

        $adjustment->refresh();
        $this->assertEquals('posted', $adjustment->status);

        // Verify stock movement was recorded
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => $adjustment->getMorphClass(),
            'reference_id' => $adjustment->id,
            'quantity' => 10,
        ]);

        $level = StockLevel::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertNotNull($level);
        $this->assertEquals(10, $level->quantity);
    }

    public function test_cannot_update_posted_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-0006',
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'damage',
            'status' => 'posted',
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'recount',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->boxUom->id,
                    'quantity' => -2,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('inventory.adjustments.update', $adjustment), $updateData);
        $response->assertRedirect(route('inventory.adjustments.show', $adjustment));
        $response->assertSessionHas('error');
    }

    public function test_can_delete_draft_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-0007',
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'damage',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $this->product->id,
            'uom_id' => $this->boxUom->id,
            'quantity' => -4,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('inventory.adjustments.destroy', $adjustment));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('stock_adjustments', ['id' => $adjustment->id]);
        $this->assertDatabaseMissing('stock_adjustment_items', ['stock_adjustment_id' => $adjustment->id]);
    }

    public function test_cannot_delete_posted_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-0008',
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'opening',
            'status' => 'posted',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('inventory.adjustments.destroy', $adjustment));
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);

        $this->assertDatabaseHas('stock_adjustments', ['id' => $adjustment->id]);
    }

    public function test_unauthorized_user_cannot_update_or_delete_adjustments(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_no' => 'ADJ-2026-0009',
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'damage',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        // Unauthorized user attempts update -> blocked by role_or_permission middleware or controller
        $responseUpdate = $this->actingAs($this->unauthorizedUser)->put(route('inventory.adjustments.update', $adjustment), [
            'adjustment_date' => now()->toDateString(),
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'damage',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->boxUom->id,
                    'quantity' => -1,
                ],
            ],
        ]);
        $this->assertTrue(in_array($responseUpdate->status(), [302, 403]));

        // Unauthorized user attempts delete
        $responseDelete = $this->actingAs($this->unauthorizedUser)->deleteJson(route('inventory.adjustments.destroy', $adjustment));
        $this->assertEquals(403, $responseDelete->status());
    }
}

