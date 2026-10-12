<?php

namespace App\Domains\TallySync\Templates;

/**
 * Canonical master XML shapes for future Tally sync.
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
    ): string {
        $current = XmlHelpers::xml($currentName ?: $name);
        $nameXml = XmlHelpers::xml($name);
        $uom = XmlHelpers::xml($unit ?: 'Nos');
        $hsnXml = XmlHelpers::xml($hsn ?? '');
        $descXml = XmlHelpers::xml($description ?? '');
        $cgst = number_format($gstRate / 2, 2, '.', '');
        $sgst = number_format($gstRate / 2, 2, '.', '');
        $igst = number_format($gstRate, 2, '.', '');

        $inner = <<<XML
<STOCKITEM NAME="{$current}" ACTION="{$action}">
    <NAME>{$nameXml}</NAME>
    <BASEUNITS>{$uom}</BASEUNITS>
    <GSTAPPLICABLE>Applicable</GSTAPPLICABLE>
    <GSTTYPEOFSUPPLY>Goods</GSTTYPEOFSUPPLY>
    <DESCRIPTION>{$descXml}</DESCRIPTION>
    <GSTDETAILS.LIST>
        <APPLICABLEFROM>20260401</APPLICABLEFROM>
        <HSNCODE>{$hsnXml}</HSNCODE>
        <TAXABILITY>Taxable</TAXABILITY>
        <STATEWISEDETAILS.LIST>
            <STATENAME>Any</STATENAME>
            <RATEDETAILS.LIST><GSTRATEDUTYHEAD>CGST</GSTRATEDUTYHEAD><GSTRATE>{$cgst}</GSTRATE></RATEDETAILS.LIST>
            <RATEDETAILS.LIST><GSTRATEDUTYHEAD>SGST/UTGST</GSTRATEDUTYHEAD><GSTRATE>{$sgst}</GSTRATE></RATEDETAILS.LIST>
            <RATEDETAILS.LIST><GSTRATEDUTYHEAD>IGST</GSTRATEDUTYHEAD><GSTRATE>{$igst}</GSTRATE></RATEDETAILS.LIST>
        </STATEWISEDETAILS.LIST>
    </GSTDETAILS.LIST>
</STOCKITEM>
XML;

        return XmlEnvelope::masters('PRODUCT', $inner);
    }

    public function ledger(
        string $name,
        string $parent,
        string $action = 'Create',
        ?string $currentName = null,
        ?string $gstin = null,
        ?string $address = null,
        ?string $state = null,
    ): string {
        $current = XmlHelpers::xml($currentName ?: $name);
        $nameXml = XmlHelpers::xml($name);
        $parentXml = XmlHelpers::xml($parent);
        $gstinXml = XmlHelpers::xml($gstin ?? '');
        $addressXml = XmlHelpers::xml($address ?? '');
        $stateXml = XmlHelpers::xml($state ?? '');

        $inner = <<<XML
<LEDGER NAME="{$current}" ACTION="{$action}">
 <NAME>{$nameXml}</NAME>
 <PARENT>{$parentXml}</PARENT>
 <ISBILLWISEON>Yes</ISBILLWISEON>
 <GSTREGISTRATIONTYPE>Regular</GSTREGISTRATIONTYPE>
 <PARTYGSTIN>{$gstinXml}</PARTYGSTIN>
 <ADDRESS.LIST TYPE="String"><ADDRESS>{$addressXml}</ADDRESS></ADDRESS.LIST>
 <STATENAME>{$stateXml}</STATENAME>
</LEDGER>
XML;

        return XmlEnvelope::masters('CUSTOMER', $inner);
    }

    public function unit(string $name, string $action = 'Create', ?string $currentName = null): string
    {
        $current = XmlHelpers::xml($currentName ?: $name);
        $nameXml = XmlHelpers::xml($name);

        return XmlEnvelope::masters('UOM', <<<XML
<UNIT NAME="{$current}" ACTION="{$action}">
 <NAME>{$nameXml}</NAME>
 <ISSIMPLEUNIT>Yes</ISSIMPLEUNIT>
</UNIT>
XML);
    }

    public function godown(string $name, string $action = 'Create', ?string $currentName = null): string
    {
        $current = XmlHelpers::xml($currentName ?: $name);
        $nameXml = XmlHelpers::xml($name);

        return XmlEnvelope::masters('GODOWN', <<<XML
<GODOWN NAME="{$current}" ACTION="{$action}">
 <NAME>{$nameXml}</NAME>
</GODOWN>
XML);
    }
}
