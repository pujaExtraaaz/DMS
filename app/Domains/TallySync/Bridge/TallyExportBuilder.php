<?php

namespace App\Domains\TallySync\Bridge;

use Tally\Models\FinancialYear;

class TallyExportBuilder
{
    /**
     * Legacy AlterID span (unused for vouchers — TallyPrime returns empty for AlterID voucher filters).
     * Kept so older finish() callers/tests that reference the constant still resolve.
     */
    public const VOUCHER_SPAN = 250;

    public const VOUCHER_SCAN_UNTIL = 200000;

    /** Voucher pulls use calendar windows; AlterID filters return empty on this TallyPrime build. */
    public const VOUCHER_DAY_SPAN = 3;

    /** Company books start (AVIT DIGITAL StartingFrom). Cursor values below this are treated as epoch. */
    public const VOUCHER_EPOCH_YMD = 20230401;

    /** Chunk large master collections on first/full pulls. */
    public const MASTER_SPAN = 2500;

    public const MASTER_SCAN_UNTIL = 100000;

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
     * Heavy Tally reports (day book / GST) are pulled on demand, not every connector poll.
     * Polling them with busy companies routinely exceeds PHP post_max_size.
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
    public function pending(bool $includeReports = false): array
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

        if ($includeReports) {
            foreach (self::REPORTS as $key => [$name, $span]) {
                $items[] = [
                    'key' => $key,
                    'alter_from' => 0,
                    'xml' => $this->reportEnvelope($name, $span),
                ];
            }
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

        if ($key === 'vouchers') {
            [$fromYmd, $toYmd] = $this->voucherDateWindow($alterFrom);

            return '<?xml version="1.0" encoding="UTF-8"?>'
                .'<ENVELOPE><HEADER><VERSION>1</VERSION><TALLYREQUEST>Export</TALLYREQUEST><TYPE>Collection</TYPE><ID>'.$id.'</ID></HEADER>'
                .'<BODY><DESC><STATICVARIABLES>'
                .'<SVCURRENTCOMPANY>'.$company.'</SVCURRENTCOMPANY>'
                .'<SVEXPORTFORMAT>$$SysName:XML</SVEXPORTFORMAT>'
                .'<SVFROMDATE TYPE="Date">'.$fromYmd.'</SVFROMDATE>'
                .'<SVTODATE TYPE="Date">'.$toYmd.'</SVTODATE>'
                .'</STATICVARIABLES>'
                .'<TDL><TDLMESSAGE>'
                .'<COLLECTION NAME="'.$id.'" ISMODIFY="No"><TYPE>'.$type.'</TYPE>'.$fetchXml.'</COLLECTION>'
                .'</TDLMESSAGE></TDL></DESC></BODY></ENVELOPE>';
        }

        $formula = $this->filterFormula($key, $alterFrom);
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

    /**
     * @return array{0:string,1:string} fromYmd, toYmd
     */
    public function voucherDateWindow(int $cursor): array
    {
        $today = (int) now()->format('Ymd');
        if ($cursor >= $today) {
            // Catch-up done: keep re-pulling the last week so late edits still land.
            return [now()->subDays(7)->format('Ymd'), now()->format('Ymd')];
        }

        $start = $cursor >= self::VOUCHER_EPOCH_YMD ? $cursor : self::VOUCHER_EPOCH_YMD;
        $from = \Illuminate\Support\Carbon::createFromFormat('Ymd', (string) $start)->startOfDay();
        $to = $from->copy()->addDays(self::VOUCHER_DAY_SPAN - 1);
        if ($to->greaterThan(now())) {
            $to = now()->copy()->startOfDay();
        }

        return [$from->format('Ymd'), $to->format('Ymd')];
    }

    public function nextVoucherCursor(int $cursor): int
    {
        $today = (int) now()->format('Ymd');
        if ($cursor >= $today) {
            return $today;
        }

        $start = $cursor >= self::VOUCHER_EPOCH_YMD ? $cursor : self::VOUCHER_EPOCH_YMD;
        $next = (int) \Illuminate\Support\Carbon::createFromFormat('Ymd', (string) $start)
            ->addDays(self::VOUCHER_DAY_SPAN)
            ->format('Ymd');

        return min($next, $today);
    }

    public function filterFormula(string $key, int $alterFrom): string
    {
        $from = max(0, $alterFrom);

        if (in_array($key, ['ledgers', 'stock_items'], true)) {
            // Always window these — a 1=1 pull of stock items routinely exceeds 40–100MB as JSON.
            $formula = '$AlterID >= '.$from;
            if ($from < self::MASTER_SCAN_UNTIL) {
                $formula .= ' AND $AlterID <= '.($from + self::MASTER_SPAN);
            } elseif ($alterFrom > 0) {
                return '$AlterID > '.$alterFrom;
            }

            return $formula;
        }

        if ($alterFrom <= 0) {
            return '1 = 1';
        }

        return '$AlterID > '.$alterFrom;
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
                'GSTApplicable', 'GSTDetails', 'HSNCode', 'BatchAllocations',
            ])],
            'vouchers' => ['Voucher', [
                'Date', 'EffectiveDate', 'VoucherTypeName', 'VoucherNumber', 'Reference', 'ReferenceDate',
                'PartyLedgerName', 'PartyGSTIN', 'PlaceOfSupply', 'Narration', 'IsInvoice', 'IsOptional',
                'IsPostDated', 'IsMemo', 'GUID', 'AlterID', 'MasterID', 'AllLedgerEntries', 'AllInventoryEntries',
                'BillAllocations',
            ]],
            default => ['Ledger', $common],
        };
    }
}
