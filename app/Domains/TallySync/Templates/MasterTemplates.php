<?php

namespace App\Domains\TallySync\Templates;

/**
 * TallyPrime master XML. Every stored Books field that Tally can import is a tag.
 * adjustment and transfer vouchers are not masters.
 */
class MasterTemplates
{
    public function stockItem(
        string $name,
        string $unit,
        string $action = 'Create',
        ?string $currentName = null,
        ?string $hsn = null,
        ?string $description = null,
        float $gstRate = 0,
        array $fields = [],
    ): string {
        $current = $currentName ?: $name;
        $cgst = number_format($gstRate / 2, 2, '.', '');
        $sgst = number_format($gstRate / 2, 2, '.', '');
        $igst = number_format($gstRate, 2, '.', '');
        $hsnXml = XmlHelpers::xml($hsn ?? '');
        $supply = XmlHelpers::xml((string) ($fields['GSTTYPEOFSUPPLY'] ?? 'Goods'));
        unset($fields['GSTTYPEOFSUPPLY']);

        $gst = <<<XML
<GSTAPPLICABLE>Applicable</GSTAPPLICABLE>
<GSTTYPEOFSUPPLY>{$supply}</GSTTYPEOFSUPPLY>
<GSTDETAILS.LIST>
 <APPLICABLEFROM>20260401</APPLICABLEFROM>
 <HSNCODE>{$hsnXml}</HSNCODE>
 <TAXABILITY>Taxable</TAXABILITY>
 <STATEWISEDETAILS.LIST>
  <STATENAME>Any</STATENAME>
  <RATEDETAILS.LIST><GSTRATEDUTYHEAD>CGST</GSTRATEDUTYHEAD><GSTRATE>{$cgst}</GSTRATE></RATEDETAILS.LIST>
  <RATEDETAILS.LIST><GSTRATEDUTYHEAD>SGST/UTGST</GSTRATEDUTYHEAD><GSTRATE>{$sgst}</GSTRATE></RATEDETAILS.LIST>
  <RATEDETAILS.LIST><GSTRATEDUTYHEAD>IGST</GSTRATEDUTYHEAD><GSTRATE>{$igst}</GSTRATE></RATEDETAILS.LIST>
  <RATEDETAILS.LIST><GSTRATEDUTYHEAD>Cess</GSTRATEDUTYHEAD><GSTRATE>0</GSTRATE></RATEDETAILS.LIST>
 </STATEWISEDETAILS.LIST>
</GSTDETAILS.LIST>
XML;

        return XmlEnvelope::masters('PRODUCT', $this->object('STOCKITEM', $current, $action, array_merge([
            'NAME' => $name,
            'BASEUNITS' => $unit ?: 'Nos',
            'DESCRIPTION' => $description ?? '',
        ], $fields), $gst));
    }

    public function ledger(
        string $name,
        string $parent,
        string $action = 'Create',
        ?string $currentName = null,
        ?string $gstin = null,
        ?string $address = null,
        ?string $state = null,
        array $fields = [],
    ): string {
        $addressXml = XmlHelpers::xml($address ?? '');
        $mailing = XmlHelpers::xml((string) ($fields['MAILINGNAME'] ?? $name));
        unset($fields['MAILINGNAME']);
        $bankName = trim((string) ($fields['BANKNAME'] ?? ''));
        $accountNumber = trim((string) ($fields['ACCOUNTNUMBER'] ?? ''));
        $ifsc = trim((string) ($fields['IFSCODE'] ?? ''));
        unset($fields['BANKNAME'], $fields['ACCOUNTNUMBER'], $fields['IFSCODE']);
        $payment = '';
        if ($bankName !== '' || $accountNumber !== '' || $ifsc !== '') {
            $payment = '<PAYMENTDETAILS.LIST>'
                .'<IFSCODE>'.XmlHelpers::xml($ifsc).'</IFSCODE>'
                .'<ACCOUNTNUMBER>'.XmlHelpers::xml($accountNumber).'</ACCOUNTNUMBER>'
                .'<PAYMENTFAVOURING>'.$mailing.'</PAYMENTFAVOURING>'
                .'<TRANSACTIONNAME>Primary</TRANSACTIONNAME>'
                .'<BANKNAME>'.XmlHelpers::xml($bankName).'</BANKNAME>'
                .'<SETASDEFAULT>Yes</SETASDEFAULT>'
                .'<DEFAULTTRANSACTIONTYPE>Inter Bank Transfer</DEFAULTTRANSACTIONTYPE>'
                .'</PAYMENTDETAILS.LIST>';
        }
        $extra = <<<XML
<MAILINGNAME.LIST TYPE="String"><MAILINGNAME>{$mailing}</MAILINGNAME></MAILINGNAME.LIST>
<ADDRESS.LIST TYPE="String"><ADDRESS>{$addressXml}</ADDRESS></ADDRESS.LIST>
{$payment}
XML;

        return XmlEnvelope::masters('CUSTOMER', $this->object('LEDGER', $currentName ?: $name, $action, array_merge([
            'NAME' => $name,
            'PARENT' => $parent,
            'ISBILLWISEON' => 'Yes',
            'PARTYGSTIN' => $gstin ?? '',
            'STATENAME' => $state ?? '',
        ], $fields), $extra));
    }

    public function unit(string $name, string $action = 'Create', ?string $currentName = null, array $fields = []): string
    {
        return XmlEnvelope::masters('UOM', $this->object('UNIT', $currentName ?: $name, $action, array_merge([
            'NAME' => $name,
            'ISSIMPLEUNIT' => 'Yes',
            'DECIMALPLACES' => '2',
        ], $fields)));
    }

    public function godown(string $name, string $action = 'Create', ?string $currentName = null, array $fields = []): string
    {
        return XmlEnvelope::masters('GODOWN', $this->object('GODOWN', $currentName ?: $name, $action, array_merge([
            'NAME' => $name,
            'PARENT' => 'Primary',
        ], $fields)));
    }

    public function group(string $name, string $parent, string $action = 'Create', ?string $currentName = null, array $fields = []): string
    {
        return XmlEnvelope::masters('GROUP', $this->object('GROUP', $currentName ?: $name, $action, array_merge([
            'NAME' => $name,
            'PARENT' => $parent !== '' && strcasecmp($parent, 'Primary') !== 0 ? $parent : "\x04 Primary",
        ], $fields)));
    }

    public function stockGroup(string $name, string $parent, string $action = 'Create', ?string $currentName = null, array $fields = []): string
    {
        return XmlEnvelope::masters('STOCKGROUP', $this->object('STOCKGROUP', $currentName ?: $name, $action, array_merge([
            'NAME' => $name,
            'PARENT' => $parent !== '' && strcasecmp($parent, 'Primary') !== 0 ? $parent : "\x04 Primary",
        ], $fields)));
    }

    public function costCentre(string $name, string $category, string $action = 'Create', ?string $currentName = null, array $fields = []): string
    {
        return XmlEnvelope::masters('COSTCENTRE', $this->object('COSTCENTRE', $currentName ?: $name, $action, array_merge([
            'NAME' => $name,
            'CATEGORY' => $category !== '' ? $category : 'Primary Cost Category',
            'PARENT' => 'Primary',
        ], $fields)));
    }

    public function costCategory(string $name, string $action = 'Create', ?string $currentName = null, array $fields = []): string
    {
        return XmlEnvelope::masters('COSTCATEGORY', $this->object('COSTCATEGORY', $currentName ?: $name, $action, array_merge([
            'NAME' => $name,
            'ALLOCATEREVENUE' => 'Yes',
            'ALLOCATENONREVENUE' => 'Yes',
        ], $fields)));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function object(string $tag, string $nameAttr, string $action, array $fields, string $extra = ''): string
    {
        $body = '';
        foreach ($fields as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            }
            $body .= '<'.$key.'>'.XmlHelpers::xml((string) $value).'</'.$key.">\n";
        }

        $name = XmlHelpers::xml($nameAttr);
        $action = XmlHelpers::xml($action);
        $extra = trim($extra);

        return "<{$tag} NAME=\"{$name}\" ACTION=\"{$action}\">\n{$body}{$extra}\n</{$tag}>";
    }
}