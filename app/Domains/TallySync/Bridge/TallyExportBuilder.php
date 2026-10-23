<?php

namespace App\Domains\TallySync\Bridge;

class TallyExportBuilder
{
    /** @var list<string> */
    public const ORDER = [
        'groups',
        'stock_groups',
        'units',
        'godowns',
        'cost_categories',
        'cost_centres',
        'ledgers',
        'stock_items',
        'vouchers',
    ];

    /**
     * @return list<array{key:string, alter_from:int, xml:string}>
     */
    public function pending(): array
    {
        $items = [];
        foreach (self::ORDER as $key) {
            $from = (int) \Illuminate\Support\Facades\DB::table('tally_sync_cursors')->where('collection', $key)->value('alter_id');
            $items[] = [
                'key' => $key,
                'alter_from' => $from,
                'xml' => $this->envelope($key, $from),
            ];
        }

        return $items;
    }

    public function envelope(string $key, int $alterFrom): string
    {
        [$type, $fetch] = $this->spec($key);
        $id = 'Dms'.str_replace('_', '', ucwords($key, '_'));
        $fetchXml = '';
        foreach ($fetch as $field) {
            $fetchXml .= '<FETCH>'.$field.'</FETCH>';
        }
        $company = htmlspecialchars((string) config('tally_sync.company', ''), ENT_XML1);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<ENVELOPE><HEADER><VERSION>1</VERSION><TALLYREQUEST>Export</TALLYREQUEST><TYPE>Collection</TYPE><ID>'.$id.'</ID></HEADER>'
            .'<BODY><DESC><STATICVARIABLES><SVCURRENTCOMPANY>'.$company.'</SVCURRENTCOMPANY><SVEXPORTFORMAT>$$SysName:XML</SVEXPORTFORMAT><DmsLastAlter>'.$alterFrom.'</DmsLastAlter></STATICVARIABLES>'
            .'<TDL><TDLMESSAGE>'
            .'<COLLECTION NAME="'.$id.'" ISMODIFY="No"><TYPE>'.$type.'</TYPE>'.$fetchXml.'<FILTER>DmsChanged</FILTER></COLLECTION>'
            .'<SYSTEM TYPE="Formulae" NAME="DmsChanged">$AlterID &gt; ##DmsLastAlter</SYSTEM>'
            .'</TDLMESSAGE></TDL></DESC></BODY></ENVELOPE>';
    }

    /**
     * @return array{0:string,1:list<string>}
     */
    private function spec(string $key): array
    {
        $common = ['Name', 'GUID', 'AlterID', 'MasterID'];

        return match ($key) {
            'groups' => ['Group', array_merge($common, ['Parent', 'IsRevenue', 'AffectsGrossProfit', 'IsDeemedPositive'])],
            'stock_groups' => ['StockGroup', array_merge($common, ['Parent'])],
            'units' => ['Unit', array_merge($common, ['OriginalName', 'DecimalPlaces', 'IsSimpleUnit', 'ReportingUQC'])],
            'godowns' => ['Godown', array_merge($common, ['Parent', 'Address'])],
            'cost_categories' => ['CostCategory', $common],
            'cost_centres' => ['CostCentre', array_merge($common, ['Parent', 'Category'])],
            'ledgers' => ['Ledger', array_merge($common, [
                'Parent', 'MailingName', 'Address', 'LedgerPhone', 'Email', 'LedgerContact', 'PartyGSTIN',
                'GSTRegistrationType', 'IncomeTaxNumber', 'StateName', 'CountryName', 'PinCode', 'OpeningBalance',
                'IsBillWiseOn', 'IsCostCentresOn', 'CreditLimit', 'BillCreditPeriod', 'IsInterestOn',
            ])],
            'stock_items' => ['StockItem', array_merge($common, [
                'Parent', 'BaseUnits', 'Description', 'OpeningBalance', 'OpeningRate', 'OpeningValue',
                'StandardCost', 'StandardPrice', 'GSTTypeOfSupply', 'IsBatchWiseOn',
            ])],
            'vouchers' => ['Voucher', [
                'Date', 'EffectiveDate', 'VoucherTypeName', 'VoucherNumber', 'Reference', 'ReferenceDate',
                'PartyLedgerName', 'PartyGSTIN', 'PlaceOfSupply', 'Narration', 'IsInvoice', 'IsOptional',
                'IsPostDated', 'IsMemo', 'GUID', 'AlterID', 'MasterID', 'AllLedgerEntries', 'AllInventoryEntries',
            ]],
            default => ['Ledger', $common],
        };
    }
}