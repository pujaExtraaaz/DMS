<?php

namespace App\Console\Commands;

use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sync\Handlers\InvoiceSync;
use App\Domains\Sync\Handlers\PaymentSync;
use App\Domains\Sync\Support\SyncGuard;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tally\Accounting\VoucherType;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\Invoice as BooksInvoice;
use Tally\Models\Voucher;

/**
 * Link unlinked DMS customer receipts / tax invoices to existing Books twins by number,
 * then push remaining orphans to create Books drafts.
 */
class LinkVoucherTwinsCommand extends Command
{
    protected $signature = 'dms:link-voucher-twins
                            {--receipts : Link customer receipts only}
                            {--invoices : Link tax invoices only}
                            {--push : After matching, push still-unlinked rows to Books}
                            {--dry-run : Report matches without writing links}
                            {--limit=0 : Max rows per entity (0 = all)}';

    protected $description = 'Match and link unlinked DMS receipts/invoices to Books twins (then optionally push)';

    public function handle(): int
    {
        $doReceipts = $this->option('receipts') || (! $this->option('receipts') && ! $this->option('invoices'));
        $doInvoices = $this->option('invoices') || (! $this->option('receipts') && ! $this->option('invoices'));
        $dry = (bool) $this->option('dry-run');
        $push = (bool) $this->option('push');
        $limit = (int) $this->option('limit');

        $linkedReceipts = 0;
        $pushedReceipts = 0;
        $failedReceipts = 0;
        $linkedInvoices = 0;
        $pushedInvoices = 0;
        $failedInvoices = 0;

        if ($doReceipts) {
            [$linkedReceipts, $pushedReceipts, $failedReceipts] = $this->linkReceipts($dry, $push, $limit);
        }
        if ($doInvoices) {
            [$linkedInvoices, $pushedInvoices, $failedInvoices] = $this->linkInvoices($dry, $push, $limit);
        }

        $this->newLine();
        $this->info('Summary'.($dry ? ' (dry-run)' : ''));
        $this->table(
            ['Entity', 'Matched/linked', 'Pushed', 'Failed'],
            [
                ['Customer receipts', $linkedReceipts, $pushedReceipts, $failedReceipts],
                ['Tax invoices', $linkedInvoices, $pushedInvoices, $failedInvoices],
            ]
        );

        $this->reportRemaining();

        return ($failedReceipts + $failedInvoices) > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    protected function linkReceipts(bool $dry, bool $push, int $limit): array
    {
        $this->info('Customer receipts…');
        $linkedIds = DB::table('sync_entity_links')->where('entity_key', PaymentSync::KEY)->pluck('dms_id');
        $query = Payment::query()->whereNotIn('id', $linkedIds)->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $matched = 0;
        $pushed = 0;
        $failed = 0;
        $usedAcctIds = DB::table('sync_entity_links')
            ->where('entity_key', PaymentSync::KEY)
            ->pluck('acct_id')
            ->flip();

        $payments = $query->get();
        $this->line('  Unlinked in scope: '.$payments->count());

        // Prefetch Books receipts once for fast number matching (DMS RCT-R-* ↔ Books R-*).
        $vouchersByNumber = Voucher::query()
            ->where('voucher_type', VoucherType::Receipt)
            ->get(['id', 'voucher_number'])
            ->groupBy(fn (Voucher $v) => mb_strtoupper(trim((string) $v->voucher_number)));

        foreach ($payments as $payment) {
            $voucher = null;
            foreach ($this->paymentNumberCandidates((string) $payment->payment_no) as $candidate) {
                $group = $vouchersByNumber->get($candidate);
                if (! $group) {
                    continue;
                }
                $voucher = $group->first(function (Voucher $v) use ($usedAcctIds) {
                    return ! isset($usedAcctIds[$v->id]) && ! SyncLinks::dmsId(PaymentSync::KEY, $v);
                });
                if ($voucher) {
                    break;
                }
            }

            if ($voucher) {
                if (! $dry) {
                    SyncLinks::store(PaymentSync::KEY, $payment, $voucher);
                }
                $usedAcctIds[$voucher->id] = true;
                $matched++;
                continue;
            }

            if (! $push) {
                continue;
            }

            try {
                if (! $dry) {
                    SyncGuard::run(fn () => app(PaymentSync::class)->push($payment));
                }
                $pushed++;
            } catch (Throwable $e) {
                $failed++;
                $this->error("  Payment #{$payment->id} {$payment->payment_no}: ".$e->getMessage());
            }
        }

        $this->line("  Matched: {$matched}, pushed: {$pushed}, failed: {$failed}");

        return [$matched, $pushed, $failed];
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    protected function linkInvoices(bool $dry, bool $push, int $limit): array
    {
        $this->info('Tax invoices…');
        $linkedIds = DB::table('sync_entity_links')->where('entity_key', InvoiceSync::KEY)->pluck('dms_id');
        $query = Invoice::query()->whereNotIn('id', $linkedIds)->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $matched = 0;
        $pushed = 0;
        $failed = 0;
        $usedAcctIds = DB::table('sync_entity_links')
            ->where('entity_key', InvoiceSync::KEY)
            ->pluck('acct_id')
            ->flip();

        $invoices = $query->get();
        $this->line('  Unlinked in scope: '.$invoices->count());

        foreach ($invoices as $invoice) {
            $number = mb_substr((string) $invoice->invoice_no, 0, 30);
            $books = BooksInvoice::query()
                ->where('kind', InvoiceKind::Sales)
                ->where('invoice_number', $number)
                ->orderBy('id')
                ->get()
                ->first(function (BooksInvoice $b) use ($usedAcctIds) {
                    return ! isset($usedAcctIds[$b->id]) && ! SyncLinks::dmsId(InvoiceSync::KEY, $b);
                });

            if ($books) {
                if (! $dry) {
                    SyncLinks::store(InvoiceSync::KEY, $invoice, $books);
                }
                $usedAcctIds[$books->id] = true;
                $matched++;
                continue;
            }

            if (! $push) {
                continue;
            }

            try {
                if (! $dry) {
                    SyncGuard::run(fn () => app(InvoiceSync::class)->push($invoice));
                }
                $pushed++;
            } catch (Throwable $e) {
                $failed++;
                $this->error("  Invoice #{$invoice->id} {$invoice->invoice_no}: ".$e->getMessage());
            }
        }

        $this->line("  Matched: {$matched}, pushed: {$pushed}, failed: {$failed}");

        return [$matched, $pushed, $failed];
    }

    protected function reportRemaining(): void
    {
        $payLinked = DB::table('sync_entity_links')->where('entity_key', PaymentSync::KEY)->pluck('dms_id');
        $invLinked = DB::table('sync_entity_links')->where('entity_key', InvoiceSync::KEY)->pluck('dms_id');
        $this->line('Remaining unlinked receipts: '.Payment::query()->whereNotIn('id', $payLinked)->count());
        $this->line('Remaining unlinked tax invoices: '.Invoice::query()->whereNotIn('id', $invLinked)->count());
    }

    /**
     * DMS often stores RCT-R-123/26-27 while Books has R-123/26-27.
     *
     * @return list<string>
     */
    protected function paymentNumberCandidates(string $paymentNo): array
    {
        $n = trim($paymentNo);
        $out = [mb_strtoupper(mb_substr($n, 0, 30))];

        if (preg_match('/^RCT-(.+)$/i', $n, $m)) {
            $out[] = mb_strtoupper(mb_substr($m[1], 0, 30));
        }
        if (preg_match('/^PAY-(.+)$/i', $n, $m)) {
            $out[] = mb_strtoupper(mb_substr('P-'.$m[1], 0, 30));
            $out[] = mb_strtoupper(mb_substr($m[1], 0, 30));
        }

        return array_values(array_unique($out));
    }
}
