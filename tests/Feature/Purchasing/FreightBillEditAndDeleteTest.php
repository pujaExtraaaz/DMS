<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\FreightBillAllocation;
use App\Domains\Purchasing\Models\LandedCost;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FreightBillEditAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Customer $supplier;
    protected PurchaseInvoice $purchaseInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);

        $this->company = Company::create([
            'name' => 'Logistics Express Ltd',
            'code' => 'LOG-01',
            'is_active' => true,
        ]);

        $this->adminUser->update(['company_id' => $this->company->id]);

        $financialYear = FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => 'FY 2026-27',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Central Hub',
            'code' => 'WH-HUB',
            'is_active' => true,
        ]);

        $customerType = CustomerType::create([
            'name' => 'Supplier Type',
            'code' => 'SUPP-TYPE-01',
            'is_active' => true,
        ]);

        $this->supplier = Customer::create([
            'company_id' => $this->company->id,
            'customer_type_id' => $customerType->id,
            'name' => 'Atlas Heavy Cargo',
            'code' => 'SUPP-ATLAS-01',
            'party_type' => 'supplier',
            'is_active' => true,
        ]);

        $this->purchaseInvoice = PurchaseInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'PINV-2026-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => 50000.00,
            'grand_total' => 50000.00,
            'status' => 'posted',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_freight_bills_listing_displays_view_edit_and_delete_buttons_and_modal(): void
    {
        $bill = FreightBill::create([
            'freight_no' => 'FRT-2026-001',
            'bill_date' => now()->toDateString(),
            'transporter_name' => 'FastTrack Transporters',
            'vehicle_no' => 'MH-12-AB-1234',
            'lr_no' => 'LR-998877',
            'amount' => 4500.00,
            'tax_amount' => 810.00,
            'total_amount' => 5310.00,
            'allocation_basis' => 'value',
            'status' => 'posted',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.freight-bills.index'));

        $response->assertStatus(200);
        $response->assertSee('FRT-2026-001');
        $response->assertSee('FASTTRACK TRANSPORTERS');
        $response->assertSee('5,310.00');

        // Verify action buttons
        $response->assertSee('View');
        $response->assertSee('Edit');
        $response->assertSee('Delete');
        $response->assertSee('data-delete-freight="' . $bill->id . '"', false);

        // Verify modal elements
        $response->assertSee('Delete Freight Bill');
        $response->assertSee('Freight Bill No:');
        $response->assertSee('Transporter:');
        $response->assertSee('Date:');
        $response->assertSee('Total Amount:');
        $response->assertSee('Confirm Delete');
        $response->assertSee('Cancel');
    }

    public function test_edit_page_loads_and_prefills_existing_freight_bill_data(): void
    {
        $bill = FreightBill::create([
            'freight_no' => 'FRT-2026-002',
            'bill_date' => '2026-10-10',
            'transporter_name' => 'Speedy Logistics',
            'vehicle_no' => 'DL-01-XY-9999',
            'lr_no' => 'LR-445566',
            'amount' => 12000.00,
            'tax_amount' => 2160.00,
            'total_amount' => 14160.00,
            'allocation_basis' => 'weight',
            'status' => 'draft',
            'notes' => 'Priority shipping note',
            'created_by' => $this->adminUser->id,
        ]);

        FreightBillAllocation::create([
            'freight_bill_id' => $bill->id,
            'allocatable_type' => $this->purchaseInvoice->getMorphClass(),
            'allocatable_id' => $this->purchaseInvoice->id,
            'amount' => 14160.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('purchasing.freight-bills.edit', $bill));

        $response->assertStatus(200);
        $response->assertSee('Edit Freight Bill');
        $response->assertSee('FRT-2026-002');
        $response->assertSee('SPEEDY LOGISTICS');
        $response->assertSee('DL-01-XY-9999');
        $response->assertSee('LR-445566');
        $response->assertSee('Priority shipping note');
        $response->assertSee('Update Freight Bill');
    }

    public function test_updating_freight_bill_modifies_existing_record_without_creating_duplicate(): void
    {
        $bill = FreightBill::create([
            'freight_no' => 'FRT-2026-003',
            'bill_date' => '2026-10-01',
            'transporter_name' => 'Initial Carrier',
            'vehicle_no' => 'KA-01-AA-1111',
            'lr_no' => 'LR-0001',
            'amount' => 1000.00,
            'tax_amount' => 180.00,
            'total_amount' => 1180.00,
            'allocation_basis' => 'value',
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        $payload = [
            'freight_no' => 'FRT-2026-003-UPDATED',
            'bill_date' => '2026-10-05',
            'transporter_name' => 'Revised Carrier Express',
            'vehicle_no' => 'KA-01-BB-2222',
            'lr_no' => 'LR-0002',
            'amount' => 2000.00,
            'tax_amount' => 360.00,
            'allocation_basis' => 'qty',
            'status' => 'posted',
            'notes' => 'Updated notes for shipment',
            'purchase_invoice_id' => $this->purchaseInvoice->id,
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('purchasing.freight-bills.update', $bill), $payload);

        $response->assertRedirect(route('purchasing.freight-bills.show', $bill));
        $response->assertSessionHas('success');

        // Assert record count remains 1 (no duplicate created)
        $this->assertEquals(1, FreightBill::count());

        $bill->refresh();
        $this->assertEquals('FRT-2026-003-UPDATED', $bill->freight_no);
        $this->assertEquals('REVISED CARRIER EXPRESS', $bill->transporter_name);
        $this->assertEquals('KA-01-BB-2222', $bill->vehicle_no);
        $this->assertEquals('2000.00', $bill->amount);
        $this->assertEquals('360.00', $bill->tax_amount);
        $this->assertEquals('2360.00', $bill->total_amount);
        $this->assertEquals('qty', $bill->allocation_basis);
        $this->assertEquals('posted', $bill->status);
        $this->assertEquals('Updated notes for shipment', $bill->notes);

        // Verify allocation was updated with new total amount
        $this->assertDatabaseHas('freight_bill_allocations', [
            'freight_bill_id' => $bill->id,
            'allocatable_id' => $this->purchaseInvoice->id,
            'amount' => 2360.00,
        ]);
    }

    public function test_update_validates_required_fields(): void
    {
        $bill = FreightBill::create([
            'freight_no' => 'FRT-2026-VAL',
            'bill_date' => now()->toDateString(),
            'amount' => 1000.00,
            'total_amount' => 1000.00,
            'allocation_basis' => 'value',
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('purchasing.freight-bills.update', $bill), [
                'freight_no' => '',
                'bill_date' => '',
                'amount' => 'invalid-amount',
                'allocation_basis' => 'unknown-basis',
                'status' => 'invalid-status',
            ]);

        $response->assertSessionHasErrors(['freight_no', 'bill_date', 'amount', 'allocation_basis', 'status']);
    }

    public function test_can_delete_draft_or_unallocated_posted_freight_bill_via_json(): void
    {
        $bill = FreightBill::create([
            'freight_no' => 'FRT-2026-DEL-JSON',
            'bill_date' => now()->toDateString(),
            'transporter_name' => 'Transporter To Delete',
            'amount' => 5000.00,
            'tax_amount' => 900.00,
            'total_amount' => 5900.00,
            'allocation_basis' => 'value',
            'status' => 'posted',
            'created_by' => $this->adminUser->id,
        ]);

        FreightBillAllocation::create([
            'freight_bill_id' => $bill->id,
            'allocatable_type' => $this->purchaseInvoice->getMorphClass(),
            'allocatable_id' => $this->purchaseInvoice->id,
            'amount' => 5900.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('purchasing.freight-bills.destroy', $bill));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('freight_bills', ['id' => $bill->id]);
        $this->assertDatabaseMissing('freight_bill_allocations', ['freight_bill_id' => $bill->id]);

        // Purchase invoice must remain completely intact!
        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $this->purchaseInvoice->id,
            'status' => 'posted',
        ]);
    }

    public function test_can_delete_freight_bill_via_form_redirect(): void
    {
        $bill = FreightBill::create([
            'freight_no' => 'FRT-2026-DEL-FORM',
            'bill_date' => now()->toDateString(),
            'amount' => 3000.00,
            'total_amount' => 3000.00,
            'allocation_basis' => 'value',
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('purchasing.freight-bills.destroy', $bill));

        $response->assertRedirect(route('purchasing.freight-bills.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('freight_bills', ['id' => $bill->id]);
    }

    public function test_cannot_edit_or_delete_allocated_freight_bill(): void
    {
        $bill = FreightBill::create([
            'freight_no' => 'FRT-2026-ALLOCATED',
            'bill_date' => now()->toDateString(),
            'transporter_name' => 'Locked Transporter',
            'amount' => 7000.00,
            'total_amount' => 7000.00,
            'allocation_basis' => 'value',
            'status' => 'allocated',
            'created_by' => $this->adminUser->id,
        ]);

        // 1. Attempt edit page
        $editResponse = $this->actingAs($this->adminUser)
            ->get(route('purchasing.freight-bills.edit', $bill));
        $editResponse->assertRedirect(route('purchasing.freight-bills.show', $bill));
        $editResponse->assertSessionHas('error');

        // 2. Attempt update request
        $updateResponse = $this->actingAs($this->adminUser)
            ->put(route('purchasing.freight-bills.update', $bill), [
                'freight_no' => 'FRT-2026-ALLOCATED',
                'bill_date' => now()->toDateString(),
                'amount' => 9999.00,
                'allocation_basis' => 'value',
                'status' => 'posted',
            ]);
        $updateResponse->assertRedirect(route('purchasing.freight-bills.show', $bill));
        $updateResponse->assertSessionHas('error');

        // 3. Attempt delete request via JSON
        $deleteResponse = $this->actingAs($this->adminUser)
            ->deleteJson(route('purchasing.freight-bills.destroy', $bill));
        $deleteResponse->assertStatus(422);
        $deleteResponse->assertJson([
            'success' => false,
        ]);

        // Freight bill must still exist in database
        $this->assertDatabaseHas('freight_bills', [
            'id' => $bill->id,
            'status' => 'allocated',
            'amount' => 7000.00,
        ]);
    }

    public function test_unauthorized_user_cannot_edit_or_delete_freight_bills(): void
    {
        $restrictedUser = User::factory()->create();
        $staffRole = Role::firstOrCreate(['name' => 'guest-staff', 'guard_name' => 'web']);
        $restrictedUser->assignRole($staffRole);

        $bill = FreightBill::create([
            'freight_no' => 'FRT-2026-UNAUTH',
            'bill_date' => now()->toDateString(),
            'amount' => 2000.00,
            'total_amount' => 2000.00,
            'allocation_basis' => 'value',
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        // Attempt update
        $updateResponse = $this->actingAs($restrictedUser)
            ->put(route('purchasing.freight-bills.update', $bill), [
                'freight_no' => 'FRT-HACKED',
                'bill_date' => now()->toDateString(),
                'amount' => 100.00,
                'allocation_basis' => 'value',
                'status' => 'draft',
            ]);
        $this->assertTrue(in_array($updateResponse->status(), [302, 403]));
        $this->assertDatabaseHas('freight_bills', ['freight_no' => 'FRT-2026-UNAUTH']);

        // Attempt delete
        $deleteResponse = $this->actingAs($restrictedUser)
            ->deleteJson(route('purchasing.freight-bills.destroy', $bill));
        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('freight_bills', ['id' => $bill->id]);
    }

    public function test_search_and_filters_work_on_freight_bills_listing(): void
    {
        $bill1 = FreightBill::create([
            'freight_no' => 'FRT-ALPHA-01',
            'bill_date' => '2026-09-01',
            'transporter_name' => 'BlueDart Express',
            'amount' => 1000.00,
            'total_amount' => 1000.00,
            'allocation_basis' => 'value',
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        $bill2 = FreightBill::create([
            'freight_no' => 'FRT-BETA-02',
            'bill_date' => '2026-10-01',
            'transporter_name' => 'Gati KWE',
            'amount' => 2000.00,
            'total_amount' => 2000.00,
            'allocation_basis' => 'value',
            'status' => 'posted',
            'created_by' => $this->adminUser->id,
        ]);

        // 1. Search by freight_no
        $searchResponse = $this->actingAs($this->adminUser)
            ->get(route('purchasing.freight-bills.index', ['search' => 'ALPHA']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('FRT-ALPHA-01');
        $searchResponse->assertDontSee('FRT-BETA-02');

        // 2. Filter by status
        $statusResponse = $this->actingAs($this->adminUser)
            ->get(route('purchasing.freight-bills.index', ['status' => 'posted']));
        $statusResponse->assertStatus(200);
        $statusResponse->assertSee('FRT-BETA-02');
        $statusResponse->assertDontSee('FRT-ALPHA-01');

        // 3. Filter by date range
        $dateResponse = $this->actingAs($this->adminUser)
            ->get(route('purchasing.freight-bills.index', [
                'date_from' => '2026-09-15',
                'date_to' => '2026-10-15',
            ]));
        $dateResponse->assertStatus(200);
        $dateResponse->assertSee('FRT-BETA-02');
        $dateResponse->assertDontSee('FRT-ALPHA-01');
    }
}
