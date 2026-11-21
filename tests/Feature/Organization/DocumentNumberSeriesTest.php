<?php

namespace Tests\Feature\Organization;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\DocumentNumberSeries;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Services\QuotationService;
use App\Models\User;
use App\Support\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentNumberSeriesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Customer $customer;
    protected Product $product;
    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'organization.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'organization.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'quotations.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'quotations.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'quotations.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'quotations.manage', 'guard_name' => 'web']);

        $roleAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $roleAdmin->givePermissionTo([
            'organization.view',
            'organization.manage',
            'quotations.view',
            'quotations.create',
            'quotations.edit',
            'quotations.manage',
        ]);

        $this->company = Company::create([
            'name' => 'Apex Enterprises',
            'legal_name' => 'Apex Enterprises Ltd',
            'code' => 'APX',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole($roleAdmin);

        FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $area = Area::create(['name' => 'Central City', 'code' => 'CC-01']);
        $custType = CustomerType::create(['name' => 'Standard Retailer', 'code' => 'RET']);

        $this->customer = Customer::create([
            'name' => 'Vibrant Traders',
            'code' => 'VT-100',
            'area_id' => $area->id,
            'customer_type_id' => $custType->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $brand = Brand::create(['name' => 'ApexBrand', 'code' => 'AB']);
        $category = Category::create(['name' => 'General', 'code' => 'GEN']);
        $this->uom = Uom::create(['name' => 'Pieces', 'code' => 'PCS']);

        $this->product = Product::create([
            'name' => 'Industrial Valve',
            'sku' => 'VALVE-01',
            'serial_no' => 'SN-VALVE-01',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'base_uom_id' => $this->uom->id,
            'is_active' => true,
            'is_hidden' => false,
            'cost_price' => 100,
            'selling_price' => 150,
        ]);
    }

    public function test_can_list_document_number_series(): void
    {
        DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'PO',
            'name' => 'Purchase Order Series',
            'prefix' => 'APXPO',
            'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('organization.document-number-series.index'));

        $response->assertOk();
        $response->assertSee('Purchase Order');
        $response->assertSee('APXPO');
        $response->assertSee('Automatic');
    }

    public function test_can_create_series_with_all_four_numbering_methods(): void
    {
        $methods = [
            'automatic' => DocumentNumberSeries::METHOD_AUTOMATIC,
            'automatic_manual_override' => DocumentNumberSeries::METHOD_AUTOMATIC_MANUAL_OVERRIDE,
            'manual' => DocumentNumberSeries::METHOD_MANUAL,
            'multi_user_auto' => DocumentNumberSeries::METHOD_MULTI_USER_AUTO,
        ];

        $docTypes = ['PO', 'PI', 'SO', 'QTN'];
        $i = 0;

        foreach ($methods as $key => $methodValue) {
            $docType = $docTypes[$i++];
            $response = $this->actingAs($this->user)->post(route('organization.document-number-series.store'), [
                'company_id' => $this->company->id,
                'document_type' => $docType,
                'name' => "Series for {$docType}",
                'prefix' => "TEST{$docType}",
                'include_financial_year' => 1,
                'financial_year_style' => 'yy_yy',
                'separator' => '-',
                'padding' => 4,
                'numbering_method' => $methodValue,
                'restart_each_fy' => 1,
                'is_active' => 1,
            ]);

            $response->assertRedirect(route('organization.document-number-series.index'));
            $this->assertDatabaseHas('document_number_series', [
                'document_type' => $docType,
                'numbering_method' => $methodValue,
            ]);
        }
    }

    public function test_model_helper_methods_and_labels(): void
    {
        $auto = new DocumentNumberSeries(['numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC]);
        $this->assertTrue($auto->isAutomatic());
        $this->assertFalse($auto->isManual());
        $this->assertFalse($auto->isManualOverride());
        $this->assertFalse($auto->isMultiUserAuto());
        $this->assertEquals('Automatic', $auto->methodName());

        $override = new DocumentNumberSeries(['numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC_MANUAL_OVERRIDE]);
        $this->assertFalse($override->isAutomatic());
        $this->assertTrue($override->isManualOverride());
        $this->assertTrue($override->allowsManualEntry());
        $this->assertFalse($override->requiresManualEntry());
        $this->assertEquals('Automatic (Manual Override)', $override->methodName());

        $manual = new DocumentNumberSeries(['numbering_method' => DocumentNumberSeries::METHOD_MANUAL]);
        $this->assertTrue($manual->isManual());
        $this->assertTrue($manual->requiresManualEntry());
        $this->assertEquals('Manual', $manual->methodName());

        $multi = new DocumentNumberSeries(['numbering_method' => DocumentNumberSeries::METHOD_MULTI_USER_AUTO]);
        $this->assertTrue($multi->isMultiUserAuto());
        $this->assertTrue($multi->generatesAutomatically());
        $this->assertEquals('Multi-user Auto', $multi->methodName());

        $types = DocumentNumberSeries::documentTypes();
        $this->assertArrayHasKey('QTN', $types);
        $this->assertEquals('Quotation', $types['QTN']);
        $this->assertArrayHasKey('INV', $types);
        $this->assertEquals('Tax Invoice', $types['INV']);
        $this->assertArrayHasKey('CN', $types);
        $this->assertArrayHasKey('ADJ', $types);
        $this->assertArrayHasKey('PAY', $types);
        $this->assertArrayHasKey('JWO', $types);
    }

    public function test_quotation_automatic_numbering(): void
    {
        DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Quotation Series',
            'prefix' => 'QTN',
            'separator' => '-',
            'padding' => 3,
            'include_financial_year' => true,
            'financial_year_style' => 'yy_yy',
            'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
            'restart_each_fy' => true,
            'is_active' => true,
        ]);

        $service = app(QuotationService::class);

        $q1 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-10',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 2,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);

        $this->assertEquals('QTN-2627-001', $q1->quotation_no);

        $q2 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-11',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);

        $this->assertEquals('QTN-2627-002', $q2->quotation_no);
    }

    public function test_quotation_manual_numbering_requires_unique_input(): void
    {
        DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Quotation Manual Series',
            'prefix' => 'QTN',
            'numbering_method' => DocumentNumberSeries::METHOD_MANUAL,
            'is_active' => true,
        ]);

        $service = app(QuotationService::class);

        $this->expectException(ValidationException::class);
        $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-10',
            'quotation_no' => '',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);
    }

    public function test_quotation_manual_numbering_saves_and_blocks_duplicate(): void
    {
        DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Quotation Manual Series',
            'prefix' => 'QTN',
            'numbering_method' => DocumentNumberSeries::METHOD_MANUAL,
            'is_active' => true,
        ]);

        $service = app(QuotationService::class);

        $q1 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-10',
            'quotation_no' => 'MANUAL-QTN-999',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);

        $this->assertEquals('MANUAL-QTN-999', $q1->quotation_no);

        // Try creating with duplicate number
        $this->expectException(ValidationException::class);
        $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-11',
            'quotation_no' => 'MANUAL-QTN-999',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);
    }

    public function test_quotation_automatic_manual_override(): void
    {
        DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Quotation Override Series',
            'prefix' => 'QTN',
            'separator' => '-',
            'padding' => 3,
            'include_financial_year' => true,
            'financial_year_style' => 'yy_yy',
            'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC_MANUAL_OVERRIDE,
            'restart_each_fy' => true,
            'is_active' => true,
        ]);

        $service = app(QuotationService::class);

        // 1. Without manual override: auto-generated
        $q1 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-10',
            'quotation_no' => '',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);

        $this->assertEquals('QTN-2627-001', $q1->quotation_no);

        // 2. With manual override: custom number is used
        $q2 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-10',
            'quotation_no' => 'CUSTOM-OVERRIDE-55',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);

        $this->assertEquals('CUSTOM-OVERRIDE-55', $q2->quotation_no);

        // 3. Next auto creation resumes sequence correctly
        $q3 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-10',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);

        $this->assertEquals('QTN-2627-002', $q3->quotation_no);
    }

    public function test_quotation_multi_user_auto_concurrency_and_uniqueness(): void
    {
        DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Quotation Multi-User Series',
            'prefix' => 'QTN',
            'separator' => '-',
            'padding' => 4,
            'include_financial_year' => true,
            'financial_year_style' => 'yy_yy',
            'numbering_method' => DocumentNumberSeries::METHOD_MULTI_USER_AUTO,
            'restart_each_fy' => true,
            'is_active' => true,
        ]);

        $service = app(QuotationService::class);
        $generatedNumbers = [];

        // Simulate multiple users creating quotations rapidly
        for ($i = 1; $i <= 10; $i++) {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $q = $service->create([
                'customer_id' => $this->customer->id,
                'quotation_date' => '2026-06-01',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'uom_id' => $this->uom->id,
                        'quantity' => 1,
                        'unit_price' => 150,
                    ],
                ],
            ], $user);

            $this->assertFalse(in_array($q->quotation_no, $generatedNumbers, true), "Duplicate quotation number detected: {$q->quotation_no}");
            $generatedNumbers[] = $q->quotation_no;
            $this->assertStringStartsWith('QTN-2627-', $q->quotation_no);
        }

        $this->assertCount(10, array_unique($generatedNumbers));
        $this->assertEquals('QTN-2627-0001', $generatedNumbers[0]);
        $this->assertEquals('QTN-2627-0010', $generatedNumbers[9]);
    }

    public function test_multi_user_auto_skips_colliding_pre_existing_number(): void
    {
        DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Quotation Collision Test',
            'prefix' => 'QTN',
            'separator' => '-',
            'padding' => 3,
            'include_financial_year' => true,
            'financial_year_style' => 'yy_yy',
            'numbering_method' => DocumentNumberSeries::METHOD_MULTI_USER_AUTO,
            'restart_each_fy' => true,
            'is_active' => true,
        ]);

        // Manually occupy QTN-2627-002
        Quotation::create([
            'quotation_no' => 'QTN-2627-002',
            'customer_id' => $this->customer->id,
            'salesperson_id' => $this->user->id,
            'quotation_date' => '2026-06-01',
            'status' => 'draft',
            'subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100,
        ]);

        $service = app(QuotationService::class);

        // First auto number generated is 001
        $q1 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-06-01',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);
        $this->assertEquals('QTN-2627-001', $q1->quotation_no);

        // Next auto number detects 002 is taken and advances to 003 without error
        $q2 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-06-01',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);
        $this->assertEquals('QTN-2627-003', $q2->quotation_no);
    }

    public function test_existing_quotation_number_is_preserved_on_update(): void
    {
        $series = DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Quotation Series',
            'prefix' => 'QTN',
            'separator' => '-',
            'padding' => 3,
            'include_financial_year' => true,
            'financial_year_style' => 'yy_yy',
            'numbering_method' => DocumentNumberSeries::METHOD_MULTI_USER_AUTO,
            'restart_each_fy' => true,
            'is_active' => true,
        ]);

        $service = app(QuotationService::class);

        $q = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-10',
            'notes' => 'Original note',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 1,
                    'unit_price' => 150,
                ],
            ],
        ], $this->user);

        $originalNo = $q->quotation_no;
        $this->assertEquals('QTN-2627-001', $originalNo);

        // Update the quotation
        $updated = $service->update($q, [
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-15',
            'notes' => 'Updated note',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'uom_id' => $this->uom->id,
                    'quantity' => 5,
                    'unit_price' => 140,
                ],
            ],
        ], $this->user);

        // Quotation number remains unchanged
        $this->assertEquals($originalNo, $updated->quotation_no);
        $this->assertEquals('Updated note', $updated->notes);
    }

    public function test_quotation_form_view_renders_appropriate_controls(): void
    {
        // 1. Manual mode
        $series = DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Quotation Manual',
            'prefix' => 'QTN',
            'numbering_method' => DocumentNumberSeries::METHOD_MANUAL,
            'is_active' => true,
        ]);

        $res1 = $this->actingAs($this->user)->get(route('quotations.create'));
        $res1->assertOk();
        $res1->assertSee('Manual voucher numbering is active.');

        // 2. Multi-user auto mode
        $series->update(['numbering_method' => DocumentNumberSeries::METHOD_MULTI_USER_AUTO]);
        $res2 = $this->actingAs($this->user)->get(route('quotations.create'));
        $res2->assertOk();
        $res2->assertSee('Auto-generated on Save');
        $res2->assertSee('Multi-user Auto sequence');

        // 3. Override mode
        $series->update(['numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC_MANUAL_OVERRIDE]);
        $res3 = $this->actingAs($this->user)->get(route('quotations.create'));
        $res3->assertOk();
        $res3->assertSee('Quotation No. (Manual Override)');
    }

    public function test_can_store_and_update_date_wise_numbering_configuration(): void
    {
        $response = $this->actingAs($this->user)->post(route('organization.document-number-series.store'), [
            'company_id' => $this->company->id,
            'document_type' => 'PO',
            'name' => 'Date-Wise Purchase Orders',
            'prefix' => 'DEFAULT-PO',
            'separator' => '-',
            'padding' => 4,
            'starting_number' => 10,
            'prefix_zero' => 1,
            'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
            'financial_year_style' => 'yy_yy',
            'include_financial_year' => 0,
            'restart_each_fy' => 1,
            'is_active' => 1,
            'restart_rules' => [
                [
                    'applicable_from' => '2026-04-01',
                    'applicable_to' => '2027-03-31',
                    'starting_number' => 1,
                    'periodicity' => 'yearly',
                ],
            ],
            'prefix_rules' => [
                [
                    'applicable_from' => '2026-04-01',
                    'applicable_to' => '2027-03-31',
                    'prefix' => 'AVIT26-27',
                ],
            ],
            'suffix_rules' => [
                [
                    'applicable_from' => '2026-04-01',
                    'applicable_to' => '2027-03-31',
                    'suffix' => '/MAIN',
                ],
            ],
        ]);

        $response->assertRedirect(route('organization.document-number-series.index'));

        $series = DocumentNumberSeries::where('name', 'Date-Wise Purchase Orders')->first();
        $this->assertNotNull($series);
        $this->assertEquals(10, $series->starting_number);
        $this->assertTrue($series->prefix_zero);
        $this->assertCount(1, $series->restart_rules);
        $this->assertEquals('yearly', $series->restart_rules[0]['periodicity']);
        $this->assertCount(1, $series->prefix_rules);
        $this->assertEquals('AVIT26-27', $series->prefix_rules[0]['prefix']);
        $this->assertCount(1, $series->suffix_rules);
        $this->assertEquals('/MAIN', $series->suffix_rules[0]['suffix']);
    }

    public function test_validation_blocks_applicable_to_earlier_than_applicable_from(): void
    {
        $response = $this->actingAs($this->user)->post(route('organization.document-number-series.store'), [
            'company_id' => $this->company->id,
            'document_type' => 'PO',
            'name' => 'Invalid Dates PO',
            'prefix' => 'PO',
            'separator' => '-',
            'padding' => 4,
            'starting_number' => 1,
            'prefix_zero' => 1,
            'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
            'financial_year_style' => 'yy_yy',
            'include_financial_year' => 0,
            'restart_rules' => [
                [
                    'applicable_from' => '2026-04-01',
                    'applicable_to' => '2026-03-31', // Earlier than applicable_from!
                    'starting_number' => 1,
                    'periodicity' => 'yearly',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['restart_rules.0.applicable_to']);
    }

    public function test_validation_blocks_overlapping_date_ranges(): void
    {
        $response = $this->actingAs($this->user)->post(route('organization.document-number-series.store'), [
            'company_id' => $this->company->id,
            'document_type' => 'PO',
            'name' => 'Overlapping PO',
            'prefix' => 'PO',
            'separator' => '-',
            'padding' => 4,
            'starting_number' => 1,
            'prefix_zero' => 1,
            'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
            'financial_year_style' => 'yy_yy',
            'include_financial_year' => 0,
            'prefix_rules' => [
                [
                    'applicable_from' => '2026-01-01',
                    'applicable_to' => '2026-06-30',
                    'prefix' => 'P1',
                ],
                [
                    'applicable_from' => '2026-05-01', // Overlaps with May-June!
                    'applicable_to' => '2026-12-31',
                    'prefix' => 'P2',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['prefix_rules']);
    }

    public function test_expected_numbering_logic_with_prompt_example(): void
    {
        $series = DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Prompt Example Series',
            'separator' => '-',
            'padding' => 4,
            'starting_number' => 1,
            'prefix_zero' => true,
            'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
            'include_financial_year' => false,
            'restart_rules' => [
                [
                    'applicable_from' => '2026-04-01',
                    'applicable_to' => '2027-03-31',
                    'starting_number' => 1,
                    'periodicity' => 'yearly',
                ],
                [
                    'applicable_from' => '2027-04-01',
                    'applicable_to' => '2028-03-31',
                    'starting_number' => 1,
                    'periodicity' => 'yearly',
                ],
            ],
            'prefix_rules' => [
                [
                    'applicable_from' => '2026-04-01',
                    'applicable_to' => '2027-03-31',
                    'prefix' => 'AVIT26-27',
                ],
                [
                    'applicable_from' => '2027-04-01',
                    'applicable_to' => '2028-03-31',
                    'prefix' => 'AVIT27-28',
                ],
            ],
            'is_active' => true,
        ]);

        $service = app(QuotationService::class);

        // 1. First voucher in 2026-27 period
        $q1 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-05-15',
            'items' => [
                ['product_id' => $this->product->id, 'uom_id' => $this->uom->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ], $this->user);

        $this->assertEquals('AVIT26-27-0001', $q1->quotation_no);

        // 2. Second voucher in 2026-27 period
        $q2 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2026-06-20',
            'items' => [
                ['product_id' => $this->product->id, 'uom_id' => $this->uom->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ], $this->user);

        $this->assertEquals('AVIT26-27-0002', $q2->quotation_no);

        // 3. First voucher in 2027-28 period (sequence restarts, new prefix)
        $q3 = $service->create([
            'customer_id' => $this->customer->id,
            'quotation_date' => '2027-05-10',
            'items' => [
                ['product_id' => $this->product->id, 'uom_id' => $this->uom->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ], $this->user);

        $this->assertEquals('AVIT27-28-0001', $q3->quotation_no);
    }

    public function test_date_wise_periodicity_and_prefix_zero_formatting(): void
    {
        $series = DocumentNumberSeries::create([
            'company_id' => $this->company->id,
            'document_type' => 'QTN',
            'name' => 'Monthly Non-Zero Series',
            'prefix' => 'MNTH',
            'separator' => '/',
            'padding' => 4,
            'starting_number' => 100,
            'prefix_zero' => false, // Prefix with zero = No
            'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
            'include_financial_year' => false,
            'restart_rules' => [
                [
                    'applicable_from' => '2026-01-01',
                    'applicable_to' => '2026-12-31',
                    'starting_number' => 100,
                    'periodicity' => 'monthly',
                ],
            ],
            'suffix_rules' => [
                [
                    'applicable_from' => '2026-01-01',
                    'applicable_to' => '2026-12-31',
                    'suffix' => '-EXP',
                ],
            ],
            'is_active' => true,
        ]);

        $docNumService = app(DocumentNumberService::class);

        // May 2026 sequence starts at 100, no zero-padding, custom suffix
        $no1 = $docNumService->nextFromSeries($series, \Illuminate\Support\Carbon::parse('2026-05-01'));
        $this->assertEquals('MNTH/100-EXP', $no1);

        $no2 = $docNumService->nextFromSeries($series, \Illuminate\Support\Carbon::parse('2026-05-02'));
        $this->assertEquals('MNTH/101-EXP', $no2);

        // June 2026 sequence resets back to 100 because periodicity is monthly
        $noJune = $docNumService->nextFromSeries($series, \Illuminate\Support\Carbon::parse('2026-06-01'));
        $this->assertEquals('MNTH/100-EXP', $noJune);
    }
}
