<?php

namespace Tests\Unit\TallySync;

use App\Domains\Organization\Models\Company;
use App\Domains\TallySync\Bridge\DmsVoucherImporter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DmsVoucherGstImportTest extends TestCase
{
    #[Test]
    public function it_parses_voucher_gst_duty_ledgers_and_rate_details(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<ENVELOPE>
 <BODY>
  <DATA>
   <TALLYMESSAGE>
    <VOUCHER VCHTYPE="Gst Tax Invoice">
     <VOUCHERTYPENAME>Gst Tax Invoice</VOUCHERTYPENAME>
     <VOUCHERNUMBER>AVIT26-270244</VOUCHERNUMBER>
     <DATE>20260704</DATE>
     <PARTYLEDGERNAME>LEEN TRADERS</PARTYLEDGERNAME>
     <ENTEREDBY>VIVEK GUPTA</ENTEREDBY>
     <ALLINVENTORYENTRIES.LIST>
      <STOCKITEMNAME>Demo Projector</STOCKITEMNAME>
      <RATE>50000.00/Pcs</RATE>
      <AMOUNT>50000.00</AMOUNT>
      <BILLEDQTY>1 Pcs</BILLEDQTY>
      <ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE>
     </ALLINVENTORYENTRIES.LIST>
     <ALLLEDGERENTRIES.LIST>
      <LEDGERNAME>LEEN TRADERS</LEDGERNAME>
      <ISPARTYLEDGER>Yes</ISPARTYLEDGER>
      <AMOUNT>-59000.00</AMOUNT>
     </ALLLEDGERENTRIES.LIST>
     <ALLLEDGERENTRIES.LIST>
      <LEDGERNAME>SGST</LEDGERNAME>
      <AMOUNT>4500.00</AMOUNT>
      <RATEDETAILS.LIST>
       <GSTRATEDUTYHEAD>SGST/UTGST</GSTRATEDUTYHEAD>
       <GSTRATE>9</GSTRATE>
      </RATEDETAILS.LIST>
     </ALLLEDGERENTRIES.LIST>
     <ALLLEDGERENTRIES.LIST>
      <LEDGERNAME>CGST</LEDGERNAME>
      <AMOUNT>4500.00</AMOUNT>
      <RATEDETAILS.LIST>
       <GSTRATEDUTYHEAD>CGST</GSTRATEDUTYHEAD>
       <GSTRATE>9</GSTRATE>
      </RATEDETAILS.LIST>
     </ALLLEDGERENTRIES.LIST>
     <CATEGORYALLOCATIONS.LIST>
      <CATEGORY>Sales Person</CATEGORY>
      <COSTCENTREALLOCATIONS.LIST>
       <NAME>KAPIL JI</NAME>
       <AMOUNT>59000.00</AMOUNT>
      </COSTCENTREALLOCATIONS.LIST>
     </CATEGORYALLOCATIONS.LIST>
    </VOUCHER>
   </TALLYMESSAGE>
  </DATA>
 </BODY>
</ENVELOPE>
XML;

        $dom = new \DOMDocument;
        $this->assertTrue($dom->loadXML($xml));
        $voucher = $dom->getElementsByTagName('VOUCHER')->item(0);
        $this->assertInstanceOf(\DOMElement::class, $voucher);

        $company = new Company;
        $company->id = 1;
        $counts = ['created' => 0, 'updated' => 0, 'conflicts' => 0, 'skipped' => 0];
        $importer = new DmsVoucherImporter($company, $counts);

        $ref = new \ReflectionClass($importer);
        $gstMethod = $ref->getMethod('voucherGst');
        $gstMethod->setAccessible(true);
        $gst = $gstMethod->invoke($importer, $voucher);

        $this->assertSame(9.0, $gst['cgst_rate']);
        $this->assertSame(9.0, $gst['sgst_rate']);
        $this->assertSame(18.0, $gst['tax_rate']);
        $this->assertSame(4500.0, $gst['cgst_amount']);
        $this->assertSame(4500.0, $gst['sgst_amount']);
        $this->assertSame(9000.0, $gst['tax_amount']);
        $this->assertSame('intra', $gst['tax_type']);

        $namesMethod = $ref->getMethod('salespersonNamesFromVoucher');
        $namesMethod->setAccessible(true);
        $names = $namesMethod->invoke($importer, $voucher);

        $this->assertContains('KAPIL JI', $names);
        $this->assertContains('VIVEK GUPTA', $names);
    }
}
