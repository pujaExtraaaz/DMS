<?php

namespace Tally\Tax;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Company;
use Tally\Models\GstRegistration;
use Tally\Models\Invoice;
use Tally\Support\Queries\DateRange;
use Illuminate\Support\Carbon;

/**
 * GST portal JSON prepared from posted invoices.
 * The file is for upload. It does not contain an IRN or an e-way bill number.
 */
final class GstReturnFile
{
    /**
     * @return array<string, mixed>
     */
    public function gstr1(Company $company, string $from, string $to, ?int $branchId, ?int $registrationId): array
    {
        $b2b = [];
        $b2cs = [];
        $cdnr = [];
        $hsn = [];

        foreach ($this->invoices($company, $from, $to, $branchId, $registrationId) as $invoice) {
            if (! in_array($invoice->kind, [InvoiceKind::Sales, InvoiceKind::CreditNote, InvoiceKind::DebitNote], true)) {
                continue;
            }

            $gstin = strtoupper(trim((string) $invoice->party?->gstin));
            $items = $this->items($invoice);

            if ($invoice->kind !== InvoiceKind::Sales) {
                if ($gstin !== '') {
                    $cdnr[$gstin][] = $this->note($invoice, $items);
                }

                continue;
            }

            if ($gstin !== '') {
                $b2b[$gstin][] = $this->invoice($invoice, $items);
            } else {
                foreach ($items as $item) {
                    $key = $item['pos'].'|'.$item['rt'].'|'.($item['iamt'] > 0 ? 'INTER' : 'INTRA');
                    $b2cs[$key] ??= [
                        'sply_ty' => $item['iamt'] > 0 ? 'INTER' : 'INTRA',
                        'rt' => $item['rt'],
                        'typ' => 'OE',
                        'pos' => $item['pos'],
                        'txval' => 0,
                        'iamt' => 0,
                        'camt' => 0,
                        'samt' => 0,
                        'csamt' => 0,
                    ];
                    foreach (['txval', 'iamt', 'camt', 'samt', 'csamt'] as $field) {
                        $b2cs[$key][$field] = round($b2cs[$key][$field] + $item[$field], 2);
                    }
                }
            }

            foreach ($invoice->lines as $line) {
                $code = $line->hsnSac?->code ?: '';
                if ($code === '') {
                    continue;
                }
                $sign = $invoice->kind === InvoiceKind::CreditNote ? -1 : 1;
                $hsn[$code] ??= ['num' => count($hsn) + 1, 'hsn_sc' => $code, 'txval' => 0, 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0];
                $hsn[$code]['txval'] = round($hsn[$code]['txval'] + $sign * $this->amount($line->taxable_amount), 2);
                $hsn[$code]['iamt'] = round($hsn[$code]['iamt'] + $sign * $this->amount($line->igst_amount), 2);
                $hsn[$code]['camt'] = round($hsn[$code]['camt'] + $sign * $this->amount($line->cgst_amount), 2);
                $hsn[$code]['samt'] = round($hsn[$code]['samt'] + $sign * $this->amount($line->sgst_amount), 2);
                $hsn[$code]['csamt'] = round($hsn[$code]['csamt'] + $sign * $this->amount($line->cess_amount), 2);
            }
        }

        return [
            'gstin' => $this->gstin($company, $registrationId),
            'fp' => Carbon::parse($from)->format('mY'),
            'b2b' => array_map(fn (string $ctin, array $inv) => ['ctin' => $ctin, 'inv' => array_values($inv)], array_keys($b2b), $b2b),
            'b2cs' => array_values($b2cs),
            'cdnr' => array_map(fn (string $ctin, array $notes) => ['ctin' => $ctin, 'nt' => array_values($notes)], array_keys($cdnr), $cdnr),
            'hsn' => ['data' => array_values($hsn)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function gstr3b(Company $company, string $from, string $to, ?int $branchId, ?int $registrationId): array
    {
        $out = $this->blank();
        $in = $this->blank();
        $rcm = $this->blank();

        foreach ($this->invoices($company, $from, $to, $branchId, $registrationId) as $invoice) {
            $bucket = $invoice->reverse_charge ? 'rcm' : ($invoice->kind->usesCostOfGoods() ? 'out' : 'in');
            $sign = in_array($invoice->kind, [InvoiceKind::CreditNote, InvoiceKind::DebitNote], true) ? -1 : 1;

            foreach ($invoice->lines as $line) {
                $target = match ($bucket) {
                    'rcm' => $rcm,
                    'out' => $out,
                    default => $in,
                };
                $target['txval'] += $sign * $this->amount($line->taxable_amount);
                $target['iamt'] += $sign * $this->amount($line->igst_amount);
                $target['camt'] += $sign * $this->amount($line->cgst_amount);
                $target['samt'] += $sign * $this->amount($line->sgst_amount);
                $target['csamt'] += $sign * $this->amount($line->cess_amount);
                if ($bucket === 'rcm') {
                    $rcm = $target;
                } elseif ($bucket === 'out') {
                    $out = $target;
                } else {
                    $in = $target;
                }
            }
        }

        $round = fn (array $row) => array_map(fn ($value) => round($value, 2), $row);

        return [
            'gstin' => $this->gstin($company, $registrationId),
            'ret_period' => Carbon::parse($from)->format('mY'),
            'sup_details' => [
                'osup_det' => $round($out),
            ],
            'itc_elg' => [
                'itc_avl' => [[
                    'ty' => 'OTH',
                    'iamt' => round($in['iamt'], 2),
                    'camt' => round($in['camt'], 2),
                    'samt' => round($in['samt'], 2),
                    'csamt' => round($in['csamt'], 2),
                ]],
            ],
            'inward_sup' => [
                'isup_rev' => $round($rcm),
            ],
            'tx_pmt' => [
                'tx_py' => [[
                    'tran_desc' => 'Other than reverse charge',
                    'iamt' => round($out['iamt'] - $in['iamt'], 2),
                    'camt' => round($out['camt'] - $in['camt'], 2),
                    'samt' => round($out['samt'] - $in['samt'], 2),
                    'csamt' => round($out['csamt'] - $in['csamt'], 2),
                ]],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function einvoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['company', 'party', 'lines.hsnSac', 'lines.product']);
        $company = $invoice->company;
        $sellerIsCompany = $invoice->kind !== InvoiceKind::Purchase;
        $seller = $sellerIsCompany ? $this->partyBlock($company->legal_name ?: $company->name, $company->gstin, $company->address, $company->city, $company->state, $company->pincode) : $this->partyBlock($invoice->party?->name, $invoice->party?->gstin, $invoice->party?->address, null, $invoice->party?->state, null);
        $buyer = $sellerIsCompany ? $this->partyBlock($invoice->party?->name, $invoice->party?->gstin, $invoice->party?->address, null, $invoice->party?->state, null) : $this->partyBlock($company->legal_name ?: $company->name, $company->gstin, $company->address, $company->city, $company->state, $company->pincode);

        $items = [];
        $index = 1;
        $ass = $cgst = $sgst = $igst = $cess = 0.0;

        foreach ($invoice->lines as $line) {
            $taxable = $this->amount($line->taxable_amount);
            $lineCgst = $this->amount($line->cgst_amount);
            $lineSgst = $this->amount($line->sgst_amount);
            $lineIgst = $this->amount($line->igst_amount);
            $lineCess = $this->amount($line->cess_amount);
            $ass += $taxable;
            $cgst += $lineCgst;
            $sgst += $lineSgst;
            $igst += $lineIgst;
            $cess += $lineCess;
            $gstRate = $taxable > 0 ? round((($lineCgst + $lineSgst + $lineIgst) / $taxable) * 100, 2) : 0;
            $items[] = [
                'SlNo' => (string) $index++,
                'PrdDesc' => $line->item_name,
                'IsServc' => $line->hsnSac?->kind === HsnKind::Sac ? 'Y' : 'N',
                'HsnCd' => $line->hsnSac?->code ?: '',
                'Qty' => (float) $line->quantity,
                'Unit' => 'NOS',
                'UnitPrice' => (float) $line->rate,
                'TotAmt' => round($taxable + $this->amount($line->discount), 2),
                'Discount' => $this->amount($line->discount),
                'AssAmt' => $taxable,
                'GstRt' => $gstRate,
                'IgstAmt' => $lineIgst,
                'CgstAmt' => $lineCgst,
                'SgstAmt' => $lineSgst,
                'CesAmt' => $lineCess,
                'TotItemVal' => round($taxable + $lineCgst + $lineSgst + $lineIgst + $lineCess, 2),
            ];
        }

        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => trim((string) $invoice->party?->gstin) === '' ? 'B2C' : 'B2B',
                'RegRev' => $invoice->reverse_charge ? 'Y' : 'N',
                'IgstOnIntra' => 'N',
            ],
            'DocDtls' => [
                'Typ' => match ($invoice->kind) {
                    InvoiceKind::CreditNote => 'CRN',
                    InvoiceKind::DebitNote => 'DBN',
                    default => 'INV',
                },
                'No' => $invoice->invoice_number,
                'Dt' => $invoice->invoice_date?->format('d/m/Y'),
            ],
            'SellerDtls' => $seller,
            'BuyerDtls' => $buyer,
            'ItemList' => $items,
            'ValDtls' => [
                'AssVal' => round($ass, 2),
                'CgstVal' => round($cgst, 2),
                'SgstVal' => round($sgst, 2),
                'IgstVal' => round($igst, 2),
                'CesVal' => round($cess, 2),
                'TotInvVal' => round((float) $invoice->grand_total, 2),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function eway(Invoice $invoice): array
    {
        $invoice->loadMissing(['company', 'party', 'lines.hsnSac']);
        $company = $invoice->company;
        $outward = $invoice->kind !== InvoiceKind::Purchase;
        $from = $outward ? $company : $invoice->party;
        $to = $outward ? $invoice->party : $company;
        $items = [];

        foreach ($invoice->lines as $line) {
            $items[] = [
                'productName' => $line->item_name,
                'hsnCode' => $line->hsnSac?->code ?: '',
                'quantity' => (float) $line->quantity,
                'qtyUnit' => 'NOS',
                'taxableAmount' => $this->amount($line->taxable_amount),
                'cgstRate' => 0,
                'sgstRate' => 0,
                'igstRate' => 0,
            ];
        }

        return [
            'supplyType' => $outward ? 'O' : 'I',
            'subSupplyType' => '1',
            'docType' => match ($invoice->kind) {
                InvoiceKind::CreditNote => 'CHL',
                InvoiceKind::DebitNote => 'OTH',
                default => 'INV',
            },
            'docNo' => $invoice->invoice_number,
            'docDate' => $invoice->invoice_date?->format('d/m/Y'),
            'fromGstin' => strtoupper(trim((string) ($from->gstin ?? ''))) ?: 'URP',
            'fromTrdName' => $from->legal_name ?? $from->name ?? '',
            'fromAddr1' => $from->address ?: 'Address',
            'fromPlace' => $from->city ?? $from->state ?? '',
            'fromPincode' => (int) ($from->pincode ?? 0),
            'fromStateCode' => (int) $this->stateCode($from->state ?? null, $from->gstin ?? null),
            'toGstin' => strtoupper(trim((string) ($to->gstin ?? ''))) ?: 'URP',
            'toTrdName' => $to->legal_name ?? $to->name ?? '',
            'toAddr1' => $to->address ?: 'Address',
            'toPlace' => $to->city ?? $to->state ?? '',
            'toPincode' => (int) ($to->pincode ?? 0),
            'toStateCode' => (int) $this->stateCode($to->state ?? null, $to->gstin ?? null),
            'totalValue' => $this->amount($invoice->subtotal),
            'cgstValue' => round((float) $invoice->lines->sum('cgst_amount'), 2),
            'sgstValue' => round((float) $invoice->lines->sum('sgst_amount'), 2),
            'igstValue' => round((float) $invoice->lines->sum('igst_amount'), 2),
            'totInvValue' => round((float) $invoice->grand_total, 2),
            'itemList' => $items,
        ];
    }

    /**
     * @return list<Invoice>
     */
    private function invoices(Company $company, string $from, string $to, ?int $branchId, ?int $registrationId): array
    {
        return Invoice::query()
            ->with(['party', 'lines.hsnSac'])
            ->where('company_id', $company->id)
            ->where('status', VoucherStatus::Posted)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($registrationId, function ($query) use ($company, $registrationId) {
                $registration = GstRegistration::query()->where('company_id', $company->id)->find($registrationId);
                $query->when($registration === null, fn ($query) => $query->whereRaw('1 = 0'), function ($query) use ($registration) {
                    $query->where('gst_registration_id', $registration->id);
                });
            })
            ->tap(fn ($query) => DateRange::apply($query, 'invoice_date', $from, $to))
            ->orderBy('invoice_date')
            ->get()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function items(Invoice $invoice): array
    {
        $rows = [];

        foreach ($invoice->lines as $index => $line) {
            $taxable = $this->amount($line->taxable_amount);
            $igst = $this->amount($line->igst_amount);
            $cgst = $this->amount($line->cgst_amount);
            $sgst = $this->amount($line->sgst_amount);
            $cess = $this->amount($line->cess_amount);
            $rows[] = [
                'num' => $index + 1,
                'itm_det' => [
                    'txval' => $taxable,
                    'rt' => $taxable > 0 ? round((($igst + $cgst + $sgst) / $taxable) * 100, 2) : 0,
                    'iamt' => $igst,
                    'camt' => $cgst,
                    'samt' => $sgst,
                    'csamt' => $cess,
                ],
                'pos' => $this->stateCode($invoice->place_of_supply ?: $invoice->party?->state, $invoice->party?->gstin),
                'rt' => $taxable > 0 ? round((($igst + $cgst + $sgst) / $taxable) * 100, 2) : 0,
                'txval' => $taxable,
                'iamt' => $igst,
                'camt' => $cgst,
                'samt' => $sgst,
                'csamt' => $cess,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function invoice(Invoice $invoice, array $items): array
    {
        return [
            'inum' => $invoice->invoice_number,
            'idt' => $invoice->invoice_date?->format('d-m-Y'),
            'val' => round((float) $invoice->grand_total, 2),
            'pos' => $this->stateCode($invoice->place_of_supply ?: $invoice->party?->state, $invoice->party?->gstin),
            'rchrg' => $invoice->reverse_charge ? 'Y' : 'N',
            'inv_typ' => 'R',
            'itms' => array_map(fn (array $item) => ['num' => $item['num'], 'itm_det' => $item['itm_det']], $items),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function note(Invoice $invoice, array $items): array
    {
        $row = $this->invoice($invoice, $items);
        $row['nt_num'] = $row['inum'];
        $row['nt_dt'] = $row['idt'];
        $row['ntty'] = $invoice->kind === InvoiceKind::CreditNote ? 'C' : 'D';
        unset($row['inum'], $row['idt'], $row['inv_typ']);

        return $row;
    }

    /**
     * @return array{txval: float, iamt: float, camt: float, samt: float, csamt: float}
     */
    private function blank(): array
    {
        return ['txval' => 0, 'iamt' => 0, 'camt' => 0, 'samt' => 0, 'csamt' => 0];
    }

    private function amount(mixed $value): float
    {
        return round(Money::cents((string) $value) / 100, 2);
    }

    private function gstin(Company $company, ?int $registrationId): string
    {
        if ($registrationId) {
            $gstin = GstRegistration::query()->where('company_id', $company->id)->whereKey($registrationId)->value('gstin');

            if ($gstin) {
                return strtoupper($gstin);
            }
        }

        return strtoupper((string) $company->gstin);
    }

    private function stateCode(?string $state, ?string $gstin): string
    {
        $gstin = strtoupper(trim((string) $gstin));

        if (preg_match('/^[0-9]{2}/', $gstin) === 1) {
            return substr($gstin, 0, 2);
        }

        $state = trim((string) $state);

        foreach (Gstin::STATES as $code => $name) {
            if (strcasecmp($name, $state) === 0) {
                return $code;
            }
        }

        return '00';
    }

    /**
     * @return array<string, mixed>
     */
    private function partyBlock(?string $name, ?string $gstin, ?string $address, ?string $city, ?string $state, ?string $pincode): array
    {
        $gstin = strtoupper(trim((string) $gstin));

        return [
            'Gstin' => $gstin !== '' ? $gstin : 'URP',
            'LglNm' => $name ?: '',
            'Addr1' => trim((string) $address) !== '' ? mb_substr((string) $address, 0, 100) : 'Address',
            'Loc' => $city ?: ($state ?: ''),
            'Pin' => (int) (preg_match('/^[1-9][0-9]{5}$/', (string) $pincode) ? $pincode : 0),
            'Stcd' => $this->stateCode($state, $gstin),
        ];
    }
}
