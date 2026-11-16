<?php

namespace Tests\Feature\Master;

use App\Domains\Master\Imports\ProductsImport;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Models\UomMapping;
use App\Domains\Master\Services\UomLookupService;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductBulkImportUomMappingTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected Uom $pcsUom;
    protected Uom $boxUom;
    protected Uom $kgUom;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->company = Company::firstOrCreate(
            ['code' => 'CMP-TEST-IMPORT'],
            ['name' => 'Import Test Corp', 'is_active' => true]
        );

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR-TEST-IMPORT'],
            ['company_id' => $this->company->id, 'name' => 'Import Test Branch', 'is_active' => true]
        );

        $this->user = User::firstOrCreate(
            ['email' => 'import-admin@dms.test'],
            [
                'name' => 'Import Admin',
                'password' => bcrypt('password'),
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
            ]
        );
        $this->user->assignRole($role);

        $this->pcsUom = Uom::firstOrCreate(
            ['code' => 'PCS'],
            ['name' => 'Piece', 'is_active' => true]
        );

        $this->boxUom = Uom::firstOrCreate(
            ['code' => 'BOX'],
            ['name' => 'Box', 'is_active' => true]
        );

        $this->kgUom = Uom::firstOrCreate(
            ['code' => 'KG'],
            ['name' => 'Kg', 'is_active' => true]
        );
    }

    public function test_uom_lookup_service_resolves_various_formats(): void
    {
        $this->assertSame($this->pcsUom->id, UomLookupService::resolve('PCS')?->id);
        $this->assertSame($this->pcsUom->id, UomLookupService::resolve('PCS (PCS-PIECES)')?->id);
        $this->assertSame($this->pcsUom->id, UomLookupService::resolve('PIECES')?->id);
        $this->assertSame($this->pcsUom->id, UomLookupService::resolve('pcs')?->id);
        $this->assertSame($this->pcsUom->id, UomLookupService::resolve('  Pieces  ')?->id);
        $this->assertSame($this->boxUom->id, UomLookupService::resolve('BOX (BOX-BOXES)')?->id);
        $this->assertSame($this->boxUom->id, UomLookupService::resolve('BOXES')?->id);
        $this->assertSame($this->kgUom->id, UomLookupService::resolve('KGS')?->id);
        $this->assertSame($this->kgUom->id, UomLookupService::resolve('KILOGRAMS')?->id);

        $this->assertNull(UomLookupService::resolve(''));
        $this->assertNull(UomLookupService::resolve('   '));
        $this->assertNull(UomLookupService::resolve('NON_EXISTENT_UOM_XYZ'));
    }

    public function test_products_import_resolves_pcs_pieces_and_composite_uoms(): void
    {
        $import = new ProductsImport($this->company->id);
        $import->collection(collect([
            [
                'name' => 'Standard Item A',
                'part_number' => 'PN-UOM-001',
                'uom_code' => 'PCS',
            ],
            [
                'name' => 'Standard Item B',
                'part_number' => 'PN-UOM-002',
                'uom_code' => 'PCS (PCS-PIECES)',
            ],
            [
                'name' => 'Standard Item C',
                'part_number' => 'PN-UOM-003',
                'uom_code' => 'PIECES',
            ],
            [
                'name' => 'Standard Item D',
                'part_number' => 'PN-UOM-004',
                'uom_code' => '  Pieces  ',
            ],
            [
                'name' => 'Box Item E',
                'part_number' => 'PN-UOM-005',
                'uom_code' => 'BOX (BOX-BOXES)',
            ],
            [
                'name' => 'Weight Item F',
                'part_number' => 'PN-UOM-006',
                'uom_code' => 'KGS',
            ],
        ]));

        $this->assertSame(0, $import->failed, json_encode($import->errors));
        $this->assertSame(0, $import->skipped);
        $this->assertSame(6, $import->created);

        $prod1 = Product::where('sku', 'PN-UOM-001')->first();
        $prod2 = Product::where('sku', 'PN-UOM-002')->first();
        $prod3 = Product::where('sku', 'PN-UOM-003')->first();
        $prod4 = Product::where('sku', 'PN-UOM-004')->first();
        $prod5 = Product::where('sku', 'PN-UOM-005')->first();
        $prod6 = Product::where('sku', 'PN-UOM-006')->first();

        $this->assertSame($this->pcsUom->id, $prod1->base_uom_id);
        $this->assertSame($this->pcsUom->id, $prod2->base_uom_id);
        $this->assertSame($this->pcsUom->id, $prod3->base_uom_id);
        $this->assertSame($this->pcsUom->id, $prod4->base_uom_id);
        $this->assertSame($this->boxUom->id, $prod5->base_uom_id);
        $this->assertSame($this->kgUom->id, $prod6->base_uom_id);
    }

    public function test_unknown_uom_fails_with_clear_error_and_counts_failed_not_skipped(): void
    {
        $import = new ProductsImport($this->company->id);
        $import->collection(collect([
            [
                'name' => 'Item with Unknown UOM',
                'part_number' => 'PN-UNKNOWN-001',
                'uom_code' => 'UNKNOWN_UNIT_999',
            ],
        ]));

        $this->assertSame(1, $import->failed);
        $this->assertSame(0, $import->skipped);
        $this->assertSame(0, $import->created);
        $this->assertCount(1, $import->errors);

        $error = $import->errors[0];
        $this->assertSame(2, $error['row']);
        $this->assertSame('PN-UNKNOWN-001', $error['product_identifier']);
        $this->assertSame('UNKNOWN_UNIT_999', $error['imported_uom']);
        $this->assertSame('uom_code', $error['field']);
        $this->assertStringContainsString("Unknown UOM 'UNKNOWN_UNIT_999'", $error['message']);
        $this->assertArrayHasKey('UNKNOWN_UNIT_999', $import->missingUoms);
    }

    public function test_blank_uom_code_fails_with_meaningful_error(): void
    {
        $import = new ProductsImport($this->company->id);
        $import->collection(collect([
            [
                'name' => 'Item with Blank UOM',
                'part_number' => 'PN-BLANK-001',
                'uom_code' => '',
            ],
        ]));

        $this->assertSame(1, $import->failed);
        $this->assertSame(0, $import->skipped);
        $this->assertSame(0, $import->created);
        $this->assertSame('UOM is required', $import->errors[0]['message']);
    }

    public function test_job_uom_fails_initially_then_succeeds_after_uom_created_in_master(): void
    {
        // Ensure JOB is not in database
        Uom::whereRaw('UPPER(code) = ?', ['JOB'])->delete();
        UomMapping::where('imported_uom', 'JOB')->delete();

        // 1. Initial attempt fails because JOB does not exist in master
        $import1 = new ProductsImport($this->company->id);
        $import1->collection(collect([
            [
                'name' => 'Service Item Job',
                'part_number' => 'PN-JOB-001',
                'uom_code' => 'JOB',
            ],
        ]));

        $this->assertSame(1, $import1->failed);
        $this->assertSame(0, $import1->created);
        $this->assertArrayHasKey('JOB', $import1->missingUoms);

        // 2. Add JOB to UOM Master
        $jobUom = Uom::create([
            'name' => 'Job',
            'code' => 'JOB',
            'is_active' => true,
        ]);

        // 3. Retry import now succeeds
        $import2 = new ProductsImport($this->company->id);
        $import2->collection(collect([
            [
                'name' => 'Service Item Job',
                'part_number' => 'PN-JOB-001',
                'uom_code' => 'JOB',
            ],
        ]));

        $this->assertSame(0, $import2->failed);
        $this->assertSame(1, $import2->created);

        $product = Product::where('sku', 'PN-JOB-001')->first();
        $this->assertNotNull($product);
        $this->assertSame($jobUom->id, $product->base_uom_id);
    }

    public function test_custom_uom_mapping_resolves_unrecognized_uom_alias(): void
    {
        // Create custom mapping
        UomMapping::create([
            'imported_uom' => 'CUSTOM_SPECIAL_PIECE',
            'uom_id' => $this->pcsUom->id,
        ]);

        $import = new ProductsImport($this->company->id);
        $import->collection(collect([
            [
                'name' => 'Custom Mapped Item',
                'part_number' => 'PN-MAP-001',
                'uom_code' => 'CUSTOM_SPECIAL_PIECE',
            ],
        ]));

        $this->assertSame(0, $import->failed);
        $this->assertSame(1, $import->created);

        $product = Product::where('sku', 'PN-MAP-001')->first();
        $this->assertNotNull($product);
        $this->assertSame($this->pcsUom->id, $product->base_uom_id);
    }

    public function test_counts_are_accurate_and_not_duplicated(): void
    {
        $row = [
            'name' => 'Existing Static Item',
            'part_number' => 'PN-EXISTING-001',
            'uom_code' => 'PCS',
            'selling_price' => 100,
        ];

        // 1. Initial import creates the product
        $initial = new ProductsImport($this->company->id);
        $initial->collection(collect([$row]));
        $this->assertSame(1, $initial->created);
        $this->assertSame(0, $initial->updated);
        $this->assertSame(0, $initial->skipped);
        $this->assertSame(0, $initial->failed);

        // 2. Second import with a mix of:
        // - 1 new product (Created)
        // - 1 unchanged existing product (Skipped)
        // - 1 changed existing product (Updated)
        // - 1 error row with unknown UOM (Failed)
        $changedExisting = $row;
        $changedExisting['selling_price'] = 250;

        $import = new ProductsImport($this->company->id);
        $import->collection(collect([
            // Row 1: Brand new product (Created)
            [
                'name' => 'Brand New Item',
                'part_number' => 'PN-NEW-999',
                'uom_code' => 'PCS',
                'selling_price' => 500,
            ],
            // Row 2: Changed existing product (Updated)
            $changedExisting,
            // Row 3: Error row (Failed)
            [
                'name' => 'Invalid UOM Item',
                'part_number' => 'PN-FAIL-001',
                'uom_code' => 'UNKNOWN_RANDOM_UOM',
            ],
        ]));

        $this->assertSame(1, $import->created, 'Created count should be 1');
        $this->assertSame(1, $import->updated, 'Updated count should be 1');
        $this->assertSame(0, $import->skipped, 'Skipped count should be 0');
        $this->assertSame(1, $import->failed, 'Failed count should be 1');

        // 3. Third import with identical unchanged row (Skipped)
        $skipImport = new ProductsImport($this->company->id);
        $skipImport->collection(collect([$changedExisting]));
        $this->assertSame(0, $skipImport->created);
        $this->assertSame(0, $skipImport->updated);
        $this->assertSame(1, $skipImport->skipped, 'Skipped count should be 1');
        $this->assertSame(0, $skipImport->failed);
    }

    public function test_controller_map_uom_and_retry_workflow(): void
    {
        $this->actingAs($this->user);
        Storage::fake('local');

        // Create a test CSV with unknown UOM 'UNRECOGNIZED_UOM'
        $csvContent = "name,part_number,uom_code,selling_price\n";
        $csvContent .= "Test Retry Item,PN-RETRY-001,UNRECOGNIZED_UOM,450\n";

        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        // Upload to bulk import controller
        $response = $this->post(route('masters.bulk-import.upload', 'products'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('masters.bulk-import.index'));

        $summary = session('bulk_import_summary');
        $this->assertNotNull($summary);
        $this->assertSame(1, $summary['failed']);
        $this->assertArrayHasKey('UNRECOGNIZED_UOM', $summary['missing_uoms']);
        $this->assertNotNull($summary['pending_file']);

        // Now map the missing UOM via controller
        $mapResponse = $this->post(route('masters.bulk-import.map-uom'), [
            'imported_uom' => 'UNRECOGNIZED_UOM',
            'uom_id' => $this->pcsUom->id,
        ]);

        $mapResponse->assertSessionHas('status');
        $this->assertDatabaseHas('uom_mappings', [
            'imported_uom' => 'UNRECOGNIZED_UOM',
            'uom_id' => $this->pcsUom->id,
        ]);

        // Retry the import
        $retryResponse = $this->post(route('masters.bulk-import.retry'));
        $retryResponse->assertSessionHas('status');

        $updatedSummary = session('bulk_import_summary');
        $this->assertSame(0, $updatedSummary['failed']);
        $this->assertSame(1, $updatedSummary['created']);

        $product = Product::where('sku', 'PN-RETRY-001')->first();
        $this->assertNotNull($product);
        $this->assertSame($this->pcsUom->id, $product->base_uom_id);
    }
}
