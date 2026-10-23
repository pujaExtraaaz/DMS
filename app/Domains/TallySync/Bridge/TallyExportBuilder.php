<?php

namespace App\Domains\TallySync\Bridge;

use Tally\Models\FinancialYear;

class TallyExportBuilder
{
    public const VOUCHER_SPAN = 10000;

    public const VOUCHER_SCAN_UNTIL = 200000;

    /** @var list<string> */
    public const ORDER = [
        'groups',
        'stock_groups',
        'units',
        'godowns',
        'cost_categories',
        'cost_centres',
        'ledgers',
        'bank_accounts',
        'voucher_types',
        'stock_items',
        'vouchers',
    ];

    /**
     * Tally report name and whether the pull is the current month or the open financial year.
     *
     * @var array<string, array{0:string,1:string}>
     */
    public const REPORTS = [
        'day_book' => ['Day Book', 'month'],
        'profit_and_loss' => ['Profit & Loss', 'year'],
        'trial_balance' => ['Trial Balance', 'year'],
        'balance_sheet' => ['Balance Sheet', 'year'],
        'cash_flow' => ['Cash Flow', 'year'],
        'funds_flow' => ['Funds Flow', 'year'],
        'ratio_analysis' => ['Ratio Analysis', 'year'],
        'gst_sales' => ['GST Sales Invoice', 'month'],
        'gst_purchase' => ['GST Purchase Invoice', 'month'],
        'gstr_1' => ['GSTR-1', 'month'],
        'gstr_3b' => ['GSTR-3B', 'month'],
        'gstr_2b' => ['GSTR-2B', 'month'],
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

        foreach (self::REPORTS as $key => [$name, $span]) {
            $items[] = [
                'key' => $key,
                'alter_from' => 0,
                'xml' => $this->reportEnvelope($name, $span),
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
        $formula = '$AlterID > '.$alterFrom;
        if ($key === 'vouchers' && $alterFrom < self::VOUCHER_SCAN_UNTIL) {
            $formula .= ' AND $AlterID <= '.($alterFrom + self::VOUCHER_SPAN);
        }
        if ($key === 'bank_accounts') {
            $formula .= ' AND $$IsBelongsTo:$Parent:"Bank Accounts"';
        }
        $formula = htmlspecialchars($formula, ENT_XML1);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<ENVELOPE><HEADER><VERSION>1</VERSION><TALLYREQUEST>Export</TALLYREQUEST><TYPE>Collection</TYPE><ID>'.$id.'</ID></HEADER>'
            .'<BODY><DESC><STATICVARIABLES><SVCURRENTCOMPANY>'.$company.'</SVCURRENTCOMPANY><SVEXPORTFORMAT>$$SysName:XML</SVEXPORTFORMAT></STATICVARIABLES>'
            .'<TDL><TDLMESSAGE>'
            .'<COLLECTION NAME="'.$id.'" ISMODIFY="No"><TYPE>'.$type.'</TYPE>'.$fetchXml.'<FILTER>DmsChanged</FILTER></COLLECTION>'
            .'<SYSTEM TYPE="Formulae" NAME="DmsChanged">'.$formula.'</SYSTEM>'
            .'</TDLMESSAGE></TDL></DESC></BODY></ENVELOPE>';
    }

    public function reportEnvelope(string $report, string $span): string
    {
        [$from, $to] = $this->period($span);
        $company = htmlspecialchars((string) config('tally_sync.company', ''), ENT_XML1);
        $report = htmlspecialchars($report, ENT_XML1);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<ENVELOPE><HEADER><VERSION>1</VERSION><TALLYREQUEST>Export</TALLYREQUEST><TYPE>Data</TYPE><ID>'.$report.'</ID></HEADER>'
            .'<BODY><DESC><STATICVARIABLES>'
            .'<SVCURRENTCOMPANY>'.$company.'</SVCURRENTCOMPANY>'
            .'<SVEXPORTFORMAT>$$SysName:XML</SVEXPORTFORMAT>'
            .'<SVFROMDATE TYPE="Date">'.$from.'</SVFROMDATE>'
            .'<SVTODATE TYPE="Date">'.$to.'</SVTODATE>'
            .'<EXPLODEFLAG>No</EXPLODEFLAG>'
            .'</STATICVARIABLES></DESC></BODY></ENVELOPE>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function period(string $span): array
    {
        if ($span === 'month') {
            return [now()->startOfMonth()->format('Ymd'), now()->endOfMonth()->format('Ymd')];
        }

        $year = FinancialYear::query()
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        if ($year) {
            return [$year->start_date->format('Ymd'), $year->end_date->format('Ymd')];
        }

        return [now()->startOfYear()->format('Ymd'), now()->endOfYear()->format('Ymd')];
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
            'ledgers', 'bank_accounts' => ['Ledger', array_merge($common, [
                'Parent', 'MailingName', 'Address', 'LedgerPhone', 'Email', 'LedgerContact', 'PartyGSTIN',
                'GSTRegistrationType', 'IncomeTaxNumber', 'StateName', 'CountryName', 'PinCode', 'OpeningBalance',
                'IsBillWiseOn', 'IsCostCentresOn', 'CreditLimit', 'BillCreditPeriod', 'IsInterestOn', 'PaymentDetails',
            ])],
            'voucher_types' => ['VoucherType', array_merge($common, ['Parent', 'NumberingMethod', 'MailingName'])],
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