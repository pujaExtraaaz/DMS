<?php

namespace Tests\Unit\TallySync;

use App\Domains\TallySync\Bridge\TallyInbound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Models\BillAllocation;
use Tally\Models\Godown;
use Tally\Models\HsnSac;
use Tally\Models\Product;
use Tally\Models\StockGodownOpening;
use Tally\Models\Unit;

class TallyInboundWave4Test extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_stock_item_inbound_maps_hsn_gst_and_godown_openings(): void
    {
        $company = $this->makeCompany(['name' => 'Wave4 Inbound Co']);
        config([
            'tally_sync.company' => $company->name,
            'services.tally.company' => $company->name,
        ]);

        Unit::query()->create([
            'company_id' => $company->id,
            'name' => 'Nos',
            'symbol' => 'Nos',
            'decimal_places' => 2,
            'is_active' => true,
        ]);

        Godown::query()->create([
            'company_id' => $company->id,
            'name' => 'Main Location',
            'is_active' => true,
        ]);

        $xml = <<<'XML'
<?xml version="1.0"?>
<ENVELOPE>
 <BODY>
  <DATA>
   <TALLYMESSAGE>
    <STOCKITEM NAME="Inbound Widget" RESERVEDNAME="">
     <NAME.LIST TYPE="String"><NAME>Inbound Widget</NAME></NAME.LIST>
     <GUID>guid-widget-1</GUID>
     <ALTERID>42</ALTERID>
     <PARENT>Primary</PARENT>
     <BASEUNITS>Nos</BASEUNITS>
     <DESCRIPTION>Test item</DESCRIPTION>
     <OPENINGBALANCE>10 Nos</OPENINGBALANCE>
     <OPENINGRATE>100.00/Nos</OPENINGRATE>
     <OPENINGVALUE>1000.00</OPENINGVALUE>
     <STANDARDCOST>100.00</STANDARDCOST>
     <STANDARDPRICE>150.00</STANDARDPRICE>
     <GSTTYPEOFSUPPLY>Goods</GSTTYPEOFSUPPLY>
     <ISBATCHWISEON>Yes</ISBATCHWISEON>
     <GSTDETAILS.LIST>
      <HSNCODE>84713010</HSNCODE>
      <STATEWISEDETAILS.LIST>
       <RATEDETAILS.LIST><GSTRATEDUTYHEAD>CGST</GSTRATEDUTYHEAD><GSTRATE>9</GSTRATE></RATEDETAILS.LIST>
       <RATEDETAILS.LIST><GSTRATEDUTYHEAD>SGST/UTGST</GSTRATEDUTYHEAD><GSTRATE>9</GSTRATE></RATEDETAILS.LIST>
       <RATEDETAILS.LIST><GSTRATEDUTYHEAD>IGST</GSTRATEDUTYHEAD><GSTRATE>18</GSTRATE></RATEDETAILS.LIST>
      </STATEWISEDETAILS.LIST>
     </GSTDETAILS.LIST>
     <BATCHALLOCATIONS.LIST>
      <GODOWNNAME>Main Location</GODOWNNAME>
      <BATCHNAME>Primary Batch</BATCHNAME>
      <OPENINGBALANCE>10 Nos</OPENINGBALANCE>
      <OPENINGRATE>100.00</OPENINGRATE>
      <OPENINGVALUE>1000.00</OPENINGVALUE>
     </BATCHALLOCATIONS.LIST>
    </STOCKITEM>
   </TALLYMESSAGE>
  </DATA>
 </BODY>
</ENVELOPE>
XML;

        $result = (new TallyInbound)->apply('stock_items', $xml);

        $conflicts = \Illuminate\Support\Facades\DB::table('tally_sync_conflicts')->orderByDesc('id')->limit(3)->get();
        $this->assertGreaterThanOrEqual(
            1,
            $result['created'] + $result['updated'],
            'result='.json_encode($result).' conflicts='.$conflicts->toJson()
        );

        $product = Product::query()->where('company_id', $company->id)->where('name', 'Inbound Widget')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->hsn_sac_id);
        $this->assertNotNull($product->tax_rate_id);

        $hsn = HsnSac::query()->find($product->hsn_sac_id);
        $this->assertSame('84713010', $hsn?->code);
        $this->assertEquals(18.0, (float) $product->taxRate?->igst_rate);

        $opening = StockGodownOpening::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($opening);
        $this->assertEquals(10.0, (float) $opening->quantity);
        $this->assertSame('Primary Batch', $opening->batch_name);
    }

    public function test_voucher_inbound_persists_bill_allocations(): void
    {
        $company = $this->makeCompany(['name' => 'Wave4 Bill Co']);
        config([
            'tally_sync.company' => $company->name,
            'services.tally.company' => $company->name,
        ]);
        User::factory()->create();

        $party = app(\Tally\Parties\PartyService::class)->save($company, [
            'type' => 'customer',
            'name' => 'Wave4 Customer',
            'opening_balance' => '0.00',
            'opening_balance_type' => 'debit',
            'is_active' => true,
            'maintain_bill_wise' => true,
        ]);

        $cashGroup = $company->accountGroups()->where('name', 'Cash-in-Hand')->first()
            ?? $company->accountGroups()->where('name', 'like', '%Cash%')->first()
            ?? $company->accountGroups()->first();
        $cash = \Tally\Models\Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $cashGroup->id,
            'name' => 'Cash',
            'mailing_name' => 'Cash',
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $party = $party->ledger;

        $xml = <<<XML
<?xml version="1.0"?>
<ENVELOPE>
 <BODY>
  <DATA>
   <TALLYMESSAGE>
    <VOUCHER VCHTYPE="Receipt" ACTION="Create" REMOTEID="tally-rcpt-1">
     <DATE>20250415</DATE>
     <EFFECTIVEDATE>20250415</EFFECTIVEDATE>
     <VOUCHERTYPENAME>Receipt</VOUCHERTYPENAME>
     <VOUCHERNUMBER>RCP-W4-1</VOUCHERNUMBER>
     <PARTYLEDGERNAME>{$party->name}</PARTYLEDGERNAME>
     <NARRATION>Inbound receipt</NARRATION>
     <ISMEMO>No</ISMEMO>
     <GUID>guid-rcpt-1</GUID>
     <ALTERID>99</ALTERID>
     <ALLLEDGERENTRIES.LIST>
      <LEDGERNAME>{$party->name}</LEDGERNAME>
      <ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE>
      <AMOUNT>500.00</AMOUNT>
      <BILLALLOCATIONS.LIST>
       <NAME>INV-OLD-1</NAME>
       <BILLTYPE>New Ref</BILLTYPE>
       <AMOUNT>500.00</AMOUNT>
      </BILLALLOCATIONS.LIST>
     </ALLLEDGERENTRIES.LIST>
     <ALLLEDGERENTRIES.LIST>
      <LEDGERNAME>{$cash->name}</LEDGERNAME>
      <ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
      <AMOUNT>-500.00</AMOUNT>
     </ALLLEDGERENTRIES.LIST>
    </VOUCHER>
   </TALLYMESSAGE>
  </DATA>
 </BODY>
</ENVELOPE>
XML;

        $result = (new TallyInbound)->apply('vouchers', $xml);

        $conflicts = \Illuminate\Support\Facades\DB::table('tally_sync_conflicts')->orderByDesc('id')->limit(3)->get();
        $this->assertSame(0, $result['conflicts'], 'conflicts: '.json_encode($result).' rows='.$conflicts->toJson());

        $allocation = BillAllocation::query()
            ->where('company_id', $company->id)
            ->where('reference_name', 'INV-OLD-1')
            ->first();

        $this->assertNotNull($allocation);
        $this->assertSame('new_ref', $allocation->reference_type);
        $this->assertEquals(500.0, (float) $allocation->amount);
    }
}
