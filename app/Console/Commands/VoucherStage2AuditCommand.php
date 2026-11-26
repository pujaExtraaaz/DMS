<?php

namespace App\Console\Commands;

use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\DebitNote;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\ReceiptNote;
use App\Domains\Purchasing\Models\SupplierPayable;
use App\Domains\Purchasing\Models\SupplierPayment;
use App\Domains\Sales\Models\DeliveryChallan;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Handlers\CreditNoteSync;
use App\Domains\Sync\Handlers\DebitNoteSync;
use App\Domains\Sync\Handlers\GoodsReceiptSync;
use App\Domains\Sync\Handlers\InvoiceSync;
use App\Domains\Sync\Handlers\PaymentSync;
use App\Domains\Sync\Handlers\PurchaseInvoiceSync;
use App\Domains\Sync\Handlers\PurchaseOrderSync;
use App\Domains\Sync\Handlers\SupplierPaymentSync;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Invoice as BooksInvoice;
use Tally\Models\Voucher;

/**
 * Stage 2 voucher audit: end-to-end matrix, Books twin coverage, bill-wise gaps.
 */
class VoucherStage2AuditCommand extends Command
{
    protected $signature = 'dms:voucher-stage2-audit
        {--json= : Optional path to write the full JSON report}
        {--limit=25 : Max sample rows per gap list}';

    protected $description = 'Stage 2 voucher matrix + Books twin / bill-wise reconciliation report';

    public function handle(): int
    {
        $limit = max(5, (int) $this->option('limit'));

        $matrix = [
            $this->row('Tax Invoice (SI)', Invoice::class, InvoiceSync::KEY, fn ($m) => SyncLinks::acctId(InvoiceSync::KEY, $m)),
            $this->row('Credit Note', CreditNote::class, CreditNoteSync::KEY, fn ($m) => SyncLinks::acctId(CreditNoteSync::KEY, $m)),
            $this->row('Customer Receipt', Payment::class, PaymentSync::KEY, fn ($m) => SyncLinks::acctId(PaymentSync::KEY, $m)),
            $this->row('Purchase Order', PurchaseOrder::class, PurchaseOrderSync::KEY, fn ($m) => SyncLinks::acctId(PurchaseOrderSync::KEY, $m)),
            $this->row('Receipt Note', ReceiptNote::class, GoodsReceiptSync::KEY, fn ($m) => SyncLinks::acctId(GoodsReceiptSync::KEY, $m)),
            $this->row('Purchase Invoice', PurchaseInvoice::class, PurchaseInvoiceSync::KEY, fn ($m) => SyncLinks::acctId(PurchaseInvoiceSync::KEY, $m)),
            $this->row('Debit Note', DebitNote::class, DebitNoteSync::KEY, fn ($m) => SyncLinks::acctId(DebitNoteSync::KEY, $m)),
            $this->row('Supplier Payment', SupplierPayment::class, SupplierPaymentSync::KEY, fn ($m) => SyncLinks::acctId(SupplierPaymentSync::KEY, $m)),
            $this->row('Delivery Challan', DeliveryChallan::class, '—', null, 'DMS-only ops (no Books money twin)'),
        ];

        $this->info('Voucher matrix (DMS ↔ Books twin)');
        $this->table(
            ['Document', 'DMS', 'Linked', 'Missing', 'Conflicts', 'Note'],
            array_map(fn ($r) => [
                $r['document'],
                $r['dms'],
                $r['linked'],
                $r['missing'],
                $r['conflicts'],
                $r['note'] ?? '',
            ], $matrix)
        );

        $billWiseGaps = $this->billWiseGaps($limit);
        $this->newLine();
        $this->info('Bill-wise gaps (posted Books Payment/Receipt with reference but no allocation)');
        if ($billWiseGaps === []) {
            $this->line('None found.');
        } else {
            $this->table(
                ['Voucher', 'Type', 'Date', 'Reference', 'Amount'],
                array_map(fn ($g) => [$g['number'], $g['type'], $g['date'], $g['reference'], $g['amount']], $billWiseGaps)
            );
        }

        $orphanBooks = $this->orphanBooksPayments($limit);
        $this->newLine();
        $this->info('Books Payment vouchers not linked to DMS supplier payments');
        if ($orphanBooks === []) {
            $this->line('None found.');
        } else {
            $this->table(
                ['Voucher', 'Date', 'Amount', 'Status', 'Reference'],
                array_map(fn ($g) => [$g['number'], $g['date'], $g['amount'], $g['status'], $g['reference']], $orphanBooks)
            );
        }

        $apSample = $this->payableBalanceSample($limit);
        $this->newLine();
        $this->info('Supplier payable balance sample (latest row per supplier)');
        if ($apSample === []) {
            $this->line('No supplier_payables rows.');
        } else {
            $this->table(
                ['Supplier ID', 'Balance', 'Last type', 'Notes'],
                array_map(fn ($g) => [$g['supplier_id'], $g['balance'], $g['type'], $g['notes']], $apSample)
            );
        }

        $arSample = $this->receivableBalanceSample($limit);
        $this->newLine();
        $this->info('Outstanding ledger sample (latest row per customer)');
        if ($arSample === []) {
            $this->line('No outstanding_ledger rows.');
        } else {
            $this->table(
                ['Customer ID', 'Balance', 'Last type', 'Notes'],
                array_map(fn ($g) => [$g['customer_id'], $g['balance'], $g['type'], $g['notes']], $arSample)
            );
        }

        $syncConflicts = DB::table('sync_entity_links')
            ->where('sync_status', 'conflict')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['entity_key', 'dms_type', 'dms_id', 'acct_type', 'acct_id', 'last_error', 'updated_at'])
            ->map(fn ($r) => (array) $r)
            ->all();

        $this->newLine();
        $this->info('Recent sync conflicts (max '.$limit.')');
        if ($syncConflicts === []) {
            $this->line('None.');
        } else {
            $this->table(
                ['Key', 'DMS', 'Acct', 'Error'],
                array_map(fn ($r) => [
                    $r['entity_key'],
                    class_basename($r['dms_type']).'#'.$r['dms_id'],
                    class_basename($r['acct_type']).'#'.$r['acct_id'],
                    mb_substr((string) $r['last_error'], 0, 80),
                ], $syncConflicts)
            );
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'matrix' => $matrix,
            'bill_wise_gaps' => $billWiseGaps,
            'orphan_books_payments' => $orphanBooks,
            'supplier_payable_sample' => $apSample,
            'outstanding_ledger_sample' => $arSample,
            'sync_conflicts' => $syncConflicts,
            'books_counts' => [
                'sales_invoices' => BooksInvoice::query()->where('kind', InvoiceKind::Sales)->count(),
                'purchase_invoices' => BooksInvoice::query()->where('kind', InvoiceKind::Purchase)->count(),
                'credit_notes' => BooksInvoice::query()->where('kind', InvoiceKind::CreditNote)->count(),
                'debit_notes' => BooksInvoice::query()->where('kind', InvoiceKind::DebitNote)->count(),
                'receipts' => Voucher::query()->where('voucher_type', VoucherType::Receipt)->count(),
                'payments' => Voucher::query()->where('voucher_type', VoucherType::Payment)->count(),
            ],
        ];

        $path = $this->option('json') ?: storage_path('app/reports/voucher-stage2-audit.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->newLine();
        $this->info('JSON report: '.$path);

        return self::SUCCESS;
    }

    /**
     * @param  callable(object): ?int|null  $linkResolver
     * @return array{document: string, dms: int, linked: int, missing: int, conflicts: int, note?: string}
     */
    private function row(string $document, string $modelClass, string $entityKey, $linkResolver, ?string $note = null): array
    {
        if (! class_exists($modelClass)) {
            return [
                'document' => $document,
                'dms' => 0,
                'linked' => 0,
                'missing' => 0,
                'conflicts' => 0,
                'note' => $note ?? 'model missing',
            ];
        }

        $dms = (int) $modelClass::query()->count();
        $linked = 0;
        $missing = 0;

        if ($linkResolver) {
            foreach ($modelClass::query()->orderBy('id')->cursor() as $model) {
                $acctId = $linkResolver($model);
                if ($acctId) {
                    $linked++;
                } else {
                    $missing++;
                }
            }
        } else {
            $missing = $dms;
        }

        $conflicts = $entityKey === '—'
            ? 0
            : (int) DB::table('sync_entity_links')
                ->where('entity_key', $entityKey)
                ->where('sync_status', 'conflict')
                ->count();

        return [
            'document' => $document,
            'dms' => $dms,
            'linked' => $linked,
            'missing' => $missing,
            'conflicts' => $conflicts,
            'note' => $note,
        ];
    }

    /**
     * @return list<array{number: string, type: string, date: string, reference: string, amount: string}>
     */
    private function billWiseGaps(int $limit): array
    {
        $rows = Voucher::query()
            ->where('status', VoucherStatus::Posted)
            ->whereIn('voucher_type', [VoucherType::Receipt, VoucherType::Payment])
            ->whereNotNull('reference_number')
            ->where('reference_number', '!=', '')
            ->whereDoesntHave('billAllocations')
            ->orderByDesc('voucher_date')
            ->limit($limit)
            ->get();

        return $rows->map(fn (Voucher $v) => [
            'number' => $v->voucher_number,
            'type' => $v->voucher_type?->value ?? (string) $v->voucher_type,
            'date' => $v->voucher_date?->toDateString() ?? '—',
            'reference' => (string) $v->reference_number,
            'amount' => number_format((float) $v->total_debit, 2, '.', ''),
        ])->all();
    }

    /**
     * @return list<array{number: string, date: string, amount: string, status: string, reference: string}>
     */
    private function orphanBooksPayments(int $limit): array
    {
        $linkedAcctIds = DB::table('sync_entity_links')
            ->where('entity_key', SupplierPaymentSync::KEY)
            ->pluck('acct_id')
            ->all();

        $rows = Voucher::query()
            ->where('voucher_type', VoucherType::Payment)
            ->where('status', '!=', VoucherStatus::Cancelled)
            ->when($linkedAcctIds !== [], fn ($q) => $q->whereNotIn('id', $linkedAcctIds))
            ->orderByDesc('voucher_date')
            ->limit($limit)
            ->get();

        return $rows->map(fn (Voucher $v) => [
            'number' => $v->voucher_number,
            'date' => $v->voucher_date?->toDateString() ?? '—',
            'amount' => number_format((float) $v->total_debit, 2, '.', ''),
            'status' => $v->status?->value ?? (string) $v->status,
            'reference' => (string) ($v->reference_number ?: '—'),
        ])->all();
    }

    /**
     * @return list<array{supplier_id: int, balance: string, type: string, notes: string}>
     */
    private function payableBalanceSample(int $limit): array
    {
        $ids = SupplierPayable::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('supplier_id')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('id');

        return SupplierPayable::query()->whereIn('id', $ids)->get()->map(fn (SupplierPayable $p) => [
            'supplier_id' => $p->supplier_id,
            'balance' => number_format((float) $p->balance, 2, '.', ''),
            'type' => (string) $p->type,
            'notes' => mb_substr((string) ($p->notes ?? ''), 0, 60),
        ])->all();
    }

    /**
     * @return list<array{customer_id: int, balance: string, type: string, notes: string}>
     */
    private function receivableBalanceSample(int $limit): array
    {
        if (! class_exists(OutstandingLedger::class)) {
            return [];
        }

        $ids = OutstandingLedger::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('customer_id')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('id');

        return OutstandingLedger::query()->whereIn('id', $ids)->get()->map(fn (OutstandingLedger $p) => [
            'customer_id' => $p->customer_id,
            'balance' => number_format((float) ($p->balance ?? 0), 2, '.', ''),
            'type' => (string) ($p->type ?? ''),
            'notes' => mb_substr((string) ($p->notes ?? ''), 0, 60),
        ])->all();
    }
}
