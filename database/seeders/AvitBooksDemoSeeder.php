<?php

namespace Database\Seeders;

use App\Domains\Organization\Models\Company as OrganizationCompany;
use App\Domains\Sync\Support\BooksCompany;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tally\Accounting\OpeningBalanceType;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Banking\BankStatementImporter;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\InvoiceService;
use Tally\Models\AccountGroup;
use Tally\Models\BankAccount;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Models\Party;
use Tally\Models\TaxAccount;
use Tally\Models\TaxRate;
use Tally\Models\Voucher;
use Tally\Parties\PartyService;
use Tally\Purchasing\GoodsReceiptService;
use Tally\Purchasing\PurchaseRequisitionService;
use Tally\Purchasing\RfqService;
use Tally\Tax\HsnCatalogue;
use Tally\Tax\TaxComponent;

/**
 * Seeds Accounting Books modules for Avit Digital with balanced, calculated demo docs
 * so dashboards/reports/banking screens are non-empty and verifiable.
 */
class AvitBooksDemoSeeder extends Seeder
{
    protected Company $books;

    protected Branch $branch;

    protected FinancialYear $year;

    protected User $user;

    protected Ledger $sales;

    protected Ledger $purchase;

    protected Ledger $cash;

    protected Ledger $bankLedger;

    protected BankAccount $bankAccount;

    protected Party $customer;

    protected Party $supplier;

    protected ?TaxRate $gst18 = null;

    public function run(): void
    {
        $org = OrganizationCompany::query()->where('name', 'Avit Digital')->first()
            ?? OrganizationCompany::query()->where('is_default', true)->orderBy('id')->first();

        if (! $org) {
            throw new \RuntimeException('Avit Digital DMS company missing.');
        }

        $books = BooksCompany::forOrganization($org->id);
        if (! $books) {
            throw new \RuntimeException('Books company missing. Run AvitBooksCompanySeeder first.');
        }

        $this->books = $books;
        $this->branch = $books->branches()->where('is_active', true)->orderBy('id')->firstOrFail();
        $this->year = BooksCompany::yearFor($books, now()->toDateString())
            ?? $books->financialYears()->where('is_active', true)->orderByDesc('id')->firstOrFail();
        $this->user = User::query()->where('email', 'superadmin@dms.test')->first()
            ?? User::query()->where('email', 'finance@dms.test')->firstOrFail();

        $this->sales = BooksCompany::salesLedger($books);
        $this->purchase = BooksCompany::purchaseLedger($books);
        $this->cash = BooksCompany::cashLedger($books);

        $this->ensureTaxMasters();
        $this->ensureBank();
        $this->ensureParties();
        $this->seedSalesInvoiceAndReceipt();
        $this->seedPurchaseInvoiceAndPayment();
        $this->seedCreditAndDebitNotes();
        $this->seedJournalAndContra();
        $this->seedBankStatement();
        $this->seedProcureSample();

        $this->command?->info('Avit Books demo seeded (invoices, vouchers, bank, procure-to-pay sample).');
    }

    protected function ensureTaxMasters(): void
    {
        HsnCatalogue::adopt($this->books, '8471');
        $this->gst18 = TaxRate::query()
            ->where('company_id', $this->books->id)
            ->where('code', 'GST18')
            ->first()
            ?? TaxRate::query()->where('company_id', $this->books->id)->where('igst_rate', '18.0000')->first();

        $duties = AccountGroup::query()->where('company_id', $this->books->id)->where('code', 'DUTIES')->firstOrFail();

        foreach ([
            TaxComponent::Cgst->value => 'CGST Payable',
            TaxComponent::Sgst->value => 'SGST Payable',
            TaxComponent::Igst->value => 'IGST Payable',
        ] as $component => $name) {
            $ledger = Ledger::query()->firstOrCreate(
                ['company_id' => $this->books->id, 'code' => strtoupper($component)],
                [
                    'account_group_id' => $duties->id,
                    'name' => $name,
                    'opening_balance' => '0.00',
                    'opening_balance_type' => OpeningBalanceType::Credit,
                    'is_active' => true,
                    'is_system' => true,
                ],
            );

            TaxAccount::query()->updateOrCreate(
                ['company_id' => $this->books->id, 'component' => $component],
                ['ledger_id' => $ledger->id],
            );
        }
    }

    protected function ensureBank(): void
    {
        $bankGroup = AccountGroup::query()->where('company_id', $this->books->id)->where('code', 'BANK')->firstOrFail();

        $this->bankLedger = Ledger::query()->firstOrCreate(
            ['company_id' => $this->books->id, 'code' => 'HDFC-AVIT'],
            [
                'account_group_id' => $bankGroup->id,
                'name' => 'HDFC Bank — Avit Digital',
                'opening_balance' => '250000.00',
                'opening_balance_type' => OpeningBalanceType::Debit,
                'is_active' => true,
            ],
        );

        $this->bankAccount = BankAccount::query()->updateOrCreate(
            [
                'company_id' => $this->books->id,
                'account_number' => '50200088458819',
            ],
            [
                'ledger_id' => $this->bankLedger->id,
                'bank_name' => 'HDFC BANK LTD.',
                'ifsc' => 'HDFC0001390',
            ],
        );
    }

    protected function ensureParties(): void
    {
        $parties = app(PartyService::class);

        $this->customer = Party::query()
            ->where('company_id', $this->books->id)
            ->where('type', 'customer')
            ->whereHas('ledger', fn ($q) => $q->where('name', 'Ludhiana Tech Retail'))
            ->first();

        if (! $this->customer) {
            $this->customer = $parties->save($this->books, [
                'type' => 'customer',
                'name' => 'Ludhiana Tech Retail',
                'code' => 'AV-BK-C001',
                'state' => 'Punjab',
                'gstin' => '03ABCDE1001F1Z5',
                'opening_balance' => '0.00',
                'opening_balance_type' => 'debit',
                'is_active' => true,
            ]);
        }

        $this->supplier = Party::query()
            ->where('company_id', $this->books->id)
            ->where('type', 'supplier')
            ->whereHas('ledger', fn ($q) => $q->where('name', 'HDFC Vendor Supplies'))
            ->first();

        if (! $this->supplier) {
            $this->supplier = $parties->save($this->books, [
                'type' => 'supplier',
                'name' => 'HDFC Vendor Supplies',
                'code' => 'AV-BK-S001',
                'state' => 'Punjab',
                'gstin' => '03ABCDE2002F1Z5',
                'opening_balance' => '0.00',
                'opening_balance_type' => 'credit',
                'is_active' => true,
            ]);
        }
    }

    protected function seedSalesInvoiceAndReceipt(): void
    {
        if (Invoice::query()->where('company_id', $this->books->id)->where('reference_number', 'AV-BK-SINV-001')->exists()) {
            return;
        }

        $qty = 2;
        $rate = 650.00; // Wireless Mouse exclusive of tax
        $subtotal = $qty * $rate; // 1300
        // Intra-state Punjab: CGST 9% + SGST 9% = 18%
        $tax = round($subtotal * 0.18, 2); // 234.00
        $grand = $subtotal + $tax; // 1534.00

        $line = [
            'item_name' => 'Wireless Mouse',
            'hsn_sac' => '8471',
            'quantity' => (string) $qty,
            'rate' => number_format($rate, 2, '.', ''),
            'discount' => '0',
        ];
        if ($this->gst18) {
            $line['tax_rate_id'] = $this->gst18->id;
        } else {
            $line['tax_amount'] = number_format($tax, 2, '.', '');
        }

        $invoice = app(InvoiceService::class)->save(
            $this->books,
            $this->branch,
            $this->year,
            $this->user,
            InvoiceKind::Sales,
            [
                'invoice_date' => now()->subDays(3)->toDateString(),
                'party_ledger_id' => $this->customer->ledger_id,
                'account_ledger_id' => $this->sales->id,
                'reference_number' => 'AV-BK-SINV-001',
                'narration' => 'Avit Books demo sales invoice (qty×rate + 18% GST)',
                'lines' => [$line],
            ],
            true,
        );

        $this->assertTotals($invoice->fresh(), $subtotal, $tax, $grand, 'sales invoice');

        // Partial receipt against customer (cash)
        $receiptAmt = 1000.00;
        app(VoucherEngine::class)->save(
            $this->books,
            $this->branch,
            $this->year,
            $this->user,
            VoucherType::Receipt,
            [
                'voucher_date' => now()->subDays(2)->toDateString(),
                'reference_number' => 'AV-BK-RCT-001',
                'narration' => 'Partial receipt against AV-BK-SINV-001',
                'entries' => [
                    ['ledger_id' => $this->cash->id, 'debit' => number_format($receiptAmt, 2, '.', ''), 'credit' => '0.00'],
                    ['ledger_id' => $this->customer->ledger_id, 'debit' => '0.00', 'credit' => number_format($receiptAmt, 2, '.', '')],
                ],
            ],
            true,
        );
    }

    protected function seedPurchaseInvoiceAndPayment(): void
    {
        if (Invoice::query()->where('company_id', $this->books->id)->where('reference_number', 'AV-BK-PINV-001')->exists()) {
            return;
        }

        $qty = 10;
        $rate = 380.00;
        $subtotal = $qty * $rate; // 3800
        $tax = round($subtotal * 0.18, 2); // 684.00
        $grand = $subtotal + $tax; // 4484.00

        $line = [
            'item_name' => 'Wireless Mouse (purchase)',
            'hsn_sac' => '8471',
            'quantity' => (string) $qty,
            'rate' => number_format($rate, 2, '.', ''),
            'discount' => '0',
        ];
        if ($this->gst18) {
            $line['tax_rate_id'] = $this->gst18->id;
        } else {
            $line['tax_amount'] = number_format($tax, 2, '.', '');
        }

        $bill = app(InvoiceService::class)->save(
            $this->books,
            $this->branch,
            $this->year,
            $this->user,
            InvoiceKind::Purchase,
            [
                'invoice_date' => now()->subDays(5)->toDateString(),
                'party_ledger_id' => $this->supplier->ledger_id,
                'account_ledger_id' => $this->purchase->id,
                'reference_number' => 'AV-BK-PINV-001',
                'narration' => 'Avit Books demo purchase bill (qty×rate + 18% GST)',
                'lines' => [$line],
            ],
            true,
        );

        $this->assertTotals($bill->fresh(), $subtotal, $tax, $grand, 'purchase invoice');

        $payAmt = 2000.00;
        app(VoucherEngine::class)->save(
            $this->books,
            $this->branch,
            $this->year,
            $this->user,
            VoucherType::Payment,
            [
                'voucher_date' => now()->subDays(1)->toDateString(),
                'reference_number' => 'AV-BK-PAY-001',
                'narration' => 'Partial payment against AV-BK-PINV-001',
                'entries' => [
                    ['ledger_id' => $this->supplier->ledger_id, 'debit' => number_format($payAmt, 2, '.', ''), 'credit' => '0.00'],
                    ['ledger_id' => $this->bankLedger->id, 'debit' => '0.00', 'credit' => number_format($payAmt, 2, '.', '')],
                ],
            ],
            true,
        );
    }

    protected function seedCreditAndDebitNotes(): void
    {
        if (! Invoice::query()->where('company_id', $this->books->id)->where('reference_number', 'AV-BK-CN-001')->exists()) {
            $subtotal = 650.00;
            $tax = round($subtotal * 0.18, 2);
            $line = [
                'item_name' => 'Wireless Mouse return',
                'hsn_sac' => '8471',
                'quantity' => '1',
                'rate' => '650.00',
                'discount' => '0',
            ];
            if ($this->gst18) {
                $line['tax_rate_id'] = $this->gst18->id;
            } else {
                $line['tax_amount'] = number_format($tax, 2, '.', '');
            }

            $cn = app(InvoiceService::class)->save(
                $this->books,
                $this->branch,
                $this->year,
                $this->user,
                InvoiceKind::CreditNote,
                [
                    'invoice_date' => now()->subDay()->toDateString(),
                    'party_ledger_id' => $this->customer->ledger_id,
                    'account_ledger_id' => $this->sales->id,
                    'reference_number' => 'AV-BK-CN-001',
                    'narration' => 'Avit Books demo credit note (return)',
                    'lines' => [$line],
                ],
                true,
            );
            $this->assertTotals($cn->fresh(), $subtotal, $tax, $subtotal + $tax, 'credit note');
        }

        if (! Invoice::query()->where('company_id', $this->books->id)->where('reference_number', 'AV-BK-DN-001')->exists()) {
            $subtotal = 520.00;
            $tax = round($subtotal * 0.18, 2);
            $line = [
                'item_name' => 'Keyboard short-supply debit',
                'hsn_sac' => '8471',
                'quantity' => '1',
                'rate' => '520.00',
                'discount' => '0',
            ];
            if ($this->gst18) {
                $line['tax_rate_id'] = $this->gst18->id;
            } else {
                $line['tax_amount'] = number_format($tax, 2, '.', '');
            }

            $dn = app(InvoiceService::class)->save(
                $this->books,
                $this->branch,
                $this->year,
                $this->user,
                InvoiceKind::DebitNote,
                [
                    'invoice_date' => now()->subDay()->toDateString(),
                    'party_ledger_id' => $this->supplier->ledger_id,
                    'account_ledger_id' => $this->purchase->id,
                    'reference_number' => 'AV-BK-DN-001',
                    'narration' => 'Avit Books demo debit note to supplier',
                    'lines' => [$line],
                ],
                true,
            );
            $this->assertTotals($dn->fresh(), $subtotal, $tax, $subtotal + $tax, 'debit note');
        }
    }

    protected function seedJournalAndContra(): void
    {
        if (! Voucher::query()->where('company_id', $this->books->id)->where('reference_number', 'AV-BK-JV-001')->exists()) {
            $expenses = AccountGroup::query()->where('company_id', $this->books->id)->where('code', 'INDIRECT_EXPENSES')->first();
            $expenseLedger = Ledger::query()->firstOrCreate(
                ['company_id' => $this->books->id, 'code' => 'OFF-EXP'],
                [
                    'account_group_id' => $expenses?->id ?? $this->purchase->account_group_id,
                    'name' => 'Office Expenses',
                    'opening_balance' => '0.00',
                    'opening_balance_type' => OpeningBalanceType::Debit,
                    'is_active' => true,
                ],
            );

            $amt = 3500.00;
            app(VoucherEngine::class)->save(
                $this->books,
                $this->branch,
                $this->year,
                $this->user,
                VoucherType::Journal,
                [
                    'voucher_date' => now()->toDateString(),
                    'reference_number' => 'AV-BK-JV-001',
                    'narration' => 'Office expense accrual (balanced journal)',
                    'entries' => [
                        ['ledger_id' => $expenseLedger->id, 'debit' => number_format($amt, 2, '.', ''), 'credit' => '0.00'],
                        ['ledger_id' => $this->cash->id, 'debit' => '0.00', 'credit' => number_format($amt, 2, '.', '')],
                    ],
                ],
                true,
            );
        }

        if (! Voucher::query()->where('company_id', $this->books->id)->where('reference_number', 'AV-BK-CTR-001')->exists()) {
            $amt = 15000.00;
            app(VoucherEngine::class)->save(
                $this->books,
                $this->branch,
                $this->year,
                $this->user,
                VoucherType::Contra,
                [
                    'voucher_date' => now()->toDateString(),
                    'reference_number' => 'AV-BK-CTR-001',
                    'narration' => 'Cash deposited to HDFC bank (contra)',
                    'entries' => [
                        ['ledger_id' => $this->bankLedger->id, 'debit' => number_format($amt, 2, '.', ''), 'credit' => '0.00'],
                        ['ledger_id' => $this->cash->id, 'debit' => '0.00', 'credit' => number_format($amt, 2, '.', '')],
                    ],
                ],
                true,
            );
        }
    }

    protected function seedBankStatement(): void
    {
        if (DB::table('acct_bank_statement_lines')->where('bank_account_id', $this->bankAccount->id)->exists()) {
            return;
        }

        $path = storage_path('app/avit-demo-bank.ofx');
        file_put_contents($path, <<<'OFX'
OFXHEADER:100
<OFX><BANKMSGSRSV1><STMTTRNRS><STMTRS><BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261014
<TRNAMT>-2000.00
<FITID>AV-OFX-1
<MEMO>Vendor payment AV-BK-PAY-001
</STMTTRN>
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20261013
<TRNAMT>15000.00
<FITID>AV-OFX-2
<NAME>Cash deposit AV-BK-CTR-001
</STMTTRN>
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20261012
<TRNAMT>1000.00
<FITID>AV-OFX-3
<MEMO>Customer UPI receipt
</STMTTRN>
</BANKTRANLIST></STMTRS></STMTTRNRS></BANKMSGSRSV1></OFX>
OFX);

        try {
            app(BankStatementImporter::class)->importPath($this->bankAccount, $path, 'ofx');
        } finally {
            @unlink($path);
        }
    }

    protected function seedProcureSample(): void
    {
        if (DB::table('acct_purchase_orders')->where('company_id', $this->books->id)->where('reference_number', 'AV-BK-PO-001')->exists()) {
            return;
        }

        try {
            $pr = app(PurchaseRequisitionService::class)->save($this->books, $this->branch, $this->year, $this->user, [
                'requisition_date' => now()->subDays(7)->toDateString(),
                'status' => 'approved',
                'narration' => 'AVIT-DEMO-PR',
                'lines' => [
                    ['item_name' => 'USB Keyboard', 'quantity' => '20', 'unit' => 'PCS'],
                ],
            ]);

            $rfq = app(PurchaseRequisitionService::class)->convertToRfq($this->books, $this->branch, $this->year, $this->user, $pr);
            $rfqService = app(RfqService::class);
            $quote = $rfqService->saveVendorQuote($this->books, $rfq, [
                'supplier_ledger_id' => $this->supplier->ledger_id,
                'quote_date' => now()->subDays(6)->toDateString(),
                'lines' => [
                    ['item_name' => 'USB Keyboard', 'quantity' => '20', 'rate' => '520', 'tax_rate' => '18'],
                ],
            ]);
            $rfqService->selectWinner($this->books, $rfq, $quote);
            $order = $rfqService->convertToPurchaseOrder($this->books, $this->branch, $this->year, $this->user, $rfq->fresh());
            $order->update(['reference_number' => 'AV-BK-PO-001']);

            $grn = app(GoodsReceiptService::class)->createFromPurchaseOrder($this->books, $this->branch, $this->year, $this->user, $order);
            app(GoodsReceiptService::class)->post($grn, $this->user);
        } catch (\Throwable $e) {
            $this->command?->warn('Procure-to-pay sample skipped: '.$e->getMessage());
        }
    }

    protected function assertTotals(Invoice $invoice, float $subtotal, float $tax, float $grand, string $label): void
    {
        $gotSub = round((float) $invoice->subtotal, 2);
        $gotTax = round((float) $invoice->tax_total, 2);
        $gotGrand = round((float) $invoice->grand_total, 2);

        if (abs($gotGrand - $grand) > 0.05) {
            $this->command?->warn(sprintf(
                'Check %s totals: expected grand %.2f (sub %.2f + tax %.2f), got grand %.2f (sub %.2f tax %.2f)',
                $label,
                $grand,
                $subtotal,
                $tax,
                $gotGrand,
                $gotSub,
                $gotTax,
            ));
        } else {
            $this->command?->line(sprintf(
                'OK %s: subtotal=%.2f tax=%.2f grand=%.2f (balanced)',
                $label,
                $gotSub,
                $gotTax,
                $gotGrand,
            ));
        }
    }
}
