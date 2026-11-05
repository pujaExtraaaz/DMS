<?php

namespace Tests\Unit\TallySync;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Tally\Services\TallyExportService;
use App\Domains\Tally\Services\TallySyncMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DmsInventoryMasterXmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_uom_xml_includes_original_name_and_uqc(): void
    {
        $uom = Uom::query()->create([
            'name' => 'Numbers',
            'code' => 'NOS',
            'is_active' => true,
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildUomXml($uom);

        $this->assertStringContainsString('<NAME>NOS</NAME>', $xml);
        $this->assertStringContainsString('<ORIGINALNAME>Numbers</ORIGINALNAME>', $xml);
        $this->assertStringContainsString('<DECIMALPLACES>2</DECIMALPLACES>', $xml);
        $this->assertStringContainsString('<REPORTINGUQC>NOS</REPORTINGUQC>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_build_godown_xml_includes_address_and_parent(): void
    {
        $company = Company::query()->create([
            'name' => 'Wave2 Wh Co',
            'code' => 'W2W',
            'is_active' => true,
        ]);

        $branch = Branch::query()->create([
            'company_id' => $company->id,
            'name' => 'HO',
            'code' => 'HO',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Main Store',
            'code' => 'MS01',
            'address' => 'Plot 12, MIDC Pune',
            'is_active' => true,
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildGodownXml($warehouse);

        $this->assertStringContainsString('<NAME>Main Store</NAME>', $xml);
        $this->assertStringContainsString('<PARENT>Primary</PARENT>', $xml);
        $this->assertStringContainsString('<ADDRESS>Plot 12, MIDC Pune</ADDRESS>', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }

    public function test_build_stock_item_xml_maps_supply_prices_tracking_and_hsn(): void
    {
        $company = Company::query()->create([
            'name' => 'Wave2 Product Co',
            'code' => 'W2P',
            'is_active' => true,
        ]);

        $uom = Uom::query()->create([
            'name' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'company_id' => $company->id,
            'name' => 'Wave Two Widget',
            'sku' => 'W2-SKU-1',
            'serial_no' => 'SN-W2-1',
            'base_uom_id' => $uom->id,
            'hsn_code' => '84713010',
            'description' => 'Laptop widget',
            'tax_rate' => 18,
            'supply_type' => 'services',
            'purchase_price' => 1000,
            'selling_price' => 1500,
            'min_stock' => 5,
            'reorder_level' => 10,
            'tracking_type' => 'batch',
            'is_active' => true,
        ]);

        $xml = (new TallyExportService(new TallySyncMappingService))->buildStockItemXml($product);

        $this->assertStringContainsString('<BASEUNITS>PCS</BASEUNITS>', $xml);
        $this->assertStringContainsString('<GSTTYPEOFSUPPLY>Services</GSTTYPEOFSUPPLY>', $xml);
        $this->assertStringContainsString('<HSNCODE>84713010</HSNCODE>', $xml);
        $this->assertStringContainsString('<PARTNO>W2-SKU-1</PARTNO>', $xml);
        $this->assertStringContainsString('<STANDARDCOST>1000.00</STANDARDCOST>', $xml);
        $this->assertStringContainsString('<STANDARDPRICE>1500.00</STANDARDPRICE>', $xml);
        $this->assertStringContainsString('<ISBATCHWISEON>Yes</ISBATCHWISEON>', $xml);
        $this->assertStringContainsString('<REORDERLEVEL>10 PCS</REORDERLEVEL>', $xml);
        $this->assertStringContainsString('<MINORDERQTY>5 PCS</MINORDERQTY>', $xml);
        $this->assertMatchesRegularExpression('/<GSTRATE>9(\.00)?<\/GSTRATE>/', $xml);
        $this->assertTrue((bool) simplexml_load_string($xml));
    }
}
