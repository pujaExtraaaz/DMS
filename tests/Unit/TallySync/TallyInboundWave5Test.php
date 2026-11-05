<?php

namespace Tests\Unit\TallySync;

use App\Domains\TallySync\Bridge\TallyInbound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\VoucherType;
use Tally\Models\Godown;
use Tally\Models\GoodsReceipt;
use Tally\Models\Ledger;
use Tally\Models\Product;
use Tally\Models\Unit;
use Tally\Models\Voucher;

class TallyInboundWave5Test extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_receipt_note_inbound_creates_goods_receipt_not_payment_receipt(): void
    {
        $company = $this->makeCompany(['name' => 'Wave5 RN Co']);
        config([
            'tally_sync.company' => $company->name,
            'services.tally.company' => $company->name,
        ]);
        User::factory()->create();

        Unit::query()->create([
            'company_id' => $company->id,
            'name' => 'Nos',
            'symbol' => 'Nos',
            'decimal_places' => 2,
            'is_active' => true,
        ]);
        $godown = Godown::query()->create([
            'company_id' => $company->id,
            'name' => 'Main Location',
            'is_active' => true,
        ]);
        $unit = Unit::query()->where('company_id', $company->id)->where('name', 'Nos')->first();
        $group = $company->productGroups()->orderBy('id')->first()
            ?? \Tally\Models\ProductGroup::query()->create([
                'company_id' => $company->id,
                'name' => 'Primary',
                'is_active' => true,
            ]);
        $product = Product::query()->create([
            'company_id' => $company->id,
            'product_group_id' => $group->id,
            'name' => 'RN Widget',
            'code' => 'RN-W5',
            'primary_unit_id' => $unit->id,
            'is_active' => true,
            'maintain_in_batches' => false,
        ]);

        $creditors = $company->accountGroups()->where('name', 'Sundry Creditors')->first()
            ?? $company->accountGroups()->first();
        $supplier = Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $creditors->id,
            'name' => 'RN Supplier',
            'mailing_name' => 'RN Supplier',
            'opening_balance' => 0,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $xml = <<<XML
<?xml version="1.0"?>
<ENVELOPE>
 <BODY>
  <DATA>
   <TALLYMESSAGE>
    <VOUCHER VCHTYPE="Receipt Note" ACTION="Create" REMOTEID="tally-rn-1">
     <DATE>20250420</DATE>
     <VOUCHERTYPENAME>Receipt Note</VOUCHERTYPENAME>
     <VOUCHERNUMBER>RN-W5-1</VOUCHERNUMBER>
     <PARTYLEDGERNAME>{$supplier->name}</PARTYLEDGERNAME>
     <NARRATION>Inbound GRN</NARRATION>
     <GUID>guid-rn-1</GUID>
     <ALLINVENTORYENTRIES.LIST>
      <STOCKITEMNAME>{$product->name}</STOCKITEMNAME>
      <ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
      <RATE>100.00/Nos</RATE>
      <AMOUNT>-200.00</AMOUNT>
      <ACTUALQTY>2 Nos</ACTUALQTY>
      <BILLEDQTY>2 Nos</BILLEDQTY>
      <BATCHALLOCATIONS.LIST>
       <GODOWNNAME>{$godown->name}</GODOWNNAME>
       <BATCHNAME>Primary Batch</BATCHNAME>
       <AMOUNT>-200.00</AMOUNT>
       <ACTUALQTY>2 Nos</ACTUALQTY>
       <BILLEDQTY>2 Nos</BILLEDQTY>
      </BATCHALLOCATIONS.LIST>
     </ALLINVENTORYENTRIES.LIST>
    </VOUCHER>
   </TALLYMESSAGE>
  </DATA>
 </BODY>
</ENVELOPE>
XML;

        $result = (new TallyInbound)->apply('vouchers', $xml);

        $this->assertSame(0, Voucher::query()->where('company_id', $company->id)->where('voucher_type', VoucherType::Receipt)->count());
        $this->assertSame(0, Voucher::query()->where('company_id', $company->id)->where('voucher_type', VoucherType::Adjustment)->count());

        $receipt = GoodsReceipt::query()->where('company_id', $company->id)->where('number', 'RN-W5-1')->first();
        $this->assertNotNull($receipt, 'result='.json_encode($result));
        $this->assertSame('posted', $receipt->status);
        $this->assertSame($supplier->id, $receipt->supplier_ledger_id);
        $this->assertSame(1, $receipt->lines()->count());
        $this->assertEquals(2.0, (float) $receipt->lines->first()->received_qty);
    }
}
