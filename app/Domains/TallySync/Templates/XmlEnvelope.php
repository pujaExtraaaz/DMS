<?php

namespace App\Domains\TallySync\Templates;

/**
 * Shared TallyPrime Import Data envelopes. Used by dormant sync and unit tests.
 */
class XmlEnvelope
{
    public static function vouchers(string $type, string $inner, ?string $company = null): string
    {
        $inner = trim($inner);
        $companyXml = htmlspecialchars($company ?? (string) config('tally_sync.company', config('services.tally.company', '')), ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ENVELOPE>
 <HEADER>
  <TALLYREQUEST>Import Data</TALLYREQUEST>
 </HEADER>
 <BODY>
  <IMPORTDATA>
   <REQUESTDESC>
    <REPORTNAME>Vouchers</REPORTNAME>
    <STATICVARIABLES>
     <SVCURRENTCOMPANY>{$companyXml}</SVCURRENTCOMPANY>
    </STATICVARIABLES>
   </REQUESTDESC>
   <REQUESTDATA>
    <TALLYMESSAGE xmlns:UDF="TallyUDF">
     <!-- DMS document type: {$type} -->
     {$inner}
    </TALLYMESSAGE>
   </REQUESTDATA>
  </IMPORTDATA>
 </BODY>
</ENVELOPE>
XML;
    }

    public static function masters(string $type, string $inner, ?string $company = null): string
    {
        $inner = trim($inner);
        $companyXml = htmlspecialchars($company ?? (string) config('tally_sync.company', config('services.tally.company', '')), ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ENVELOPE>
 <HEADER>
  <TALLYREQUEST>Import Data</TALLYREQUEST>
 </HEADER>
 <BODY>
  <IMPORTDATA>
   <REQUESTDESC>
    <REPORTNAME>All Masters</REPORTNAME>
    <STATICVARIABLES>
     <SVCURRENTCOMPANY>{$companyXml}</SVCURRENTCOMPANY>
    </STATICVARIABLES>
   </REQUESTDESC>
   <REQUESTDATA>
    <TALLYMESSAGE xmlns:UDF="TallyUDF">
     <!-- DMS master type: {$type} -->
     {$inner}
    </TALLYMESSAGE>
   </REQUESTDATA>
  </IMPORTDATA>
 </BODY>
</ENVELOPE>
XML;
    }
}
