<?php

namespace App\Domains\TallySync\Templates;

class XmlHelpers
{
    public static function xml(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES);
    }

    public static function money(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }

    public static function quantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    public static function ledgerEntry(
        string $ledger,
        float $signedAmount,
        bool $isParty = false,
        string $billAllocation = '',
        string $tag = 'LEDGERENTRIES.LIST',
        ?string $costCentre = null,
    ): string {
        $name = self::xml($ledger);
        $deemed = $signedAmount < 0 ? 'Yes' : 'No';
        $partyFlag = $isParty ? 'Yes' : 'No';
        $amount = self::money($signedAmount);
        $cost = $costCentre
            ? '<CATEGORYALLOCATIONS.LIST><CATEGORY>Primary Cost Category</CATEGORY><COSTCENTREALLOCATIONS.LIST><NAME>'.self::xml($costCentre).'</NAME><AMOUNT>'.$amount.'</AMOUNT></COSTCENTREALLOCATIONS.LIST></CATEGORYALLOCATIONS.LIST>'
            : '';

        return <<<XML
 <{$tag}>
  <LEDGERNAME>{$name}</LEDGERNAME>
  <ISDEEMEDPOSITIVE>{$deemed}</ISDEEMEDPOSITIVE>
  <ISPARTYLEDGER>{$partyFlag}</ISPARTYLEDGER>
  <AMOUNT>{$amount}</AMOUNT>
  {$billAllocation}
  {$cost}
 </{$tag}>

XML;
    }

    public static function billAllocation(string $name, string $type, float $signedAmount): string
    {
        $billName = self::xml($name);
        $amount = self::money($signedAmount);

        return "<BILLALLOCATIONS.LIST><NAME>{$billName}</NAME><BILLTYPE>{$type}</BILLTYPE><AMOUNT>{$amount}</AMOUNT></BILLALLOCATIONS.LIST>";
    }

    public static function inventoryEntry(
        string $stockItem,
        float $qty,
        string $unit,
        float $rate,
        float $signedAmount,
        string $godown,
        string $ledger,
        string $batch = 'Primary Batch',
    ): string {
        $item = self::xml($stockItem);
        $deemed = $signedAmount < 0 ? 'Yes' : 'No';
        $amount = self::money($signedAmount);
        $unitXml = self::xml($unit);
        $qtyText = trim(self::quantity($qty).' '.$unitXml);
        $rateText = number_format($rate, 2, '.', '').($unitXml !== '' ? '/'.$unitXml : '');
        $godownXml = self::xml($godown);
        $batchXml = self::xml($batch);
        $ledgerXml = self::xml($ledger);

        return <<<XML
 <ALLINVENTORYENTRIES.LIST>
  <STOCKITEMNAME>{$item}</STOCKITEMNAME>
  <ISDEEMEDPOSITIVE>{$deemed}</ISDEEMEDPOSITIVE>
  <RATE>{$rateText}</RATE>
  <AMOUNT>{$amount}</AMOUNT>
  <ACTUALQTY>{$qtyText}</ACTUALQTY>
  <BILLEDQTY>{$qtyText}</BILLEDQTY>
  <BATCHALLOCATIONS.LIST>
   <GODOWNNAME>{$godownXml}</GODOWNNAME>
   <BATCHNAME>{$batchXml}</BATCHNAME>
   <AMOUNT>{$amount}</AMOUNT>
   <ACTUALQTY>{$qtyText}</ACTUALQTY>
   <BILLEDQTY>{$qtyText}</BILLEDQTY>
  </BATCHALLOCATIONS.LIST>
  <ACCOUNTINGALLOCATIONS.LIST>
   <LEDGERNAME>{$ledgerXml}</LEDGERNAME>
   <ISDEEMEDPOSITIVE>{$deemed}</ISDEEMEDPOSITIVE>
   <AMOUNT>{$amount}</AMOUNT>
  </ACCOUNTINGALLOCATIONS.LIST>
 </ALLINVENTORYENTRIES.LIST>

XML;
    }

    /**
     * @param  list<array{ledger:string,debit:float,credit:float,is_party?:bool,cost_centre?:?string,bill_name?:?string,bill_type?:?string}>  $lines
     */
    public static function accountingEntries(array $lines, string $tag = 'ALLLEDGERENTRIES.LIST'): string
    {
        $xml = '';

        foreach ($lines as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);
            $signed = $debit > 0 ? -$debit : $credit;
            $bill = '';

            if (! empty($line['bill_name'])) {
                $bill = self::billAllocation(
                    (string) $line['bill_name'],
                    (string) ($line['bill_type'] ?? 'New Ref'),
                    $signed,
                );
            }

            $xml .= self::ledgerEntry(
                (string) $line['ledger'],
                $signed,
                (bool) ($line['is_party'] ?? false),
                $bill,
                $tag,
                $line['cost_centre'] ?? null,
            );
        }

        return $xml;
    }

    public static function voucherBody(
        string $remoteId,
        string $voucherType,
        string $dateYmd,
        string $voucherNo,
        string $entries,
        string $action = 'Create',
        ?string $party = null,
        ?string $reference = null,
        ?string $narration = null,
        bool $isInvoice = false,
    ): string {
        $remote = self::xml($remoteId);
        $vchType = self::xml($voucherType);
        $number = self::xml($voucherNo);
        $partyXml = self::xml($party ?? '');
        $view = $isInvoice ? 'Invoice Voucher View' : 'Accounting Voucher View';
        $invoiceFlag = $isInvoice ? 'Yes' : 'No';
        $referenceXml = $reference ? '<REFERENCE>'.self::xml($reference).'</REFERENCE>' : '';
        $narrationXml = self::xml($narration ?? "DMS {$voucherType} {$voucherNo}");
        $partyBlock = $party
            ? "<PARTYLEDGERNAME>{$partyXml}</PARTYLEDGERNAME>\n <PARTYNAME>{$partyXml}</PARTYNAME>"
            : '';

        return <<<XML
<VOUCHER REMOTEID="{$remote}" VCHTYPE="{$vchType}" ACTION="{$action}" OBJVIEW="{$view}">
 <DATE>{$dateYmd}</DATE>
 <EFFECTIVEDATE>{$dateYmd}</EFFECTIVEDATE>
 <VOUCHERTYPENAME>{$vchType}</VOUCHERTYPENAME>
 <VOUCHERNUMBER>{$number}</VOUCHERNUMBER>
 {$referenceXml}
 {$partyBlock}
 <PERSISTEDVIEW>{$view}</PERSISTEDVIEW>
 <ISINVOICE>{$invoiceFlag}</ISINVOICE>
 <NARRATION>{$narrationXml}</NARRATION>
{$entries}
</VOUCHER>
XML;
    }
}
