<?php

namespace App\Console\Commands;

use App\Domains\Sync\Handlers\SupplierPaymentSync;
use App\Domains\Sync\Support\SyncGuard;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\Voucher;

/**
 * Backfill Books → DMS supplier payments for unlinked Payment vouchers.
 */
class PullSupplierPaymentsCommand extends Command
{
    protected $signature = 'dms:pull-supplier-payments
        {--limit=200 : Max vouchers to process}
        {--dry-run : Report only}';

    protected $description = 'Pull unlinked Books Payment vouchers into DMS supplier_payments';

    public function handle(SupplierPaymentSync $sync): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dry = (bool) $this->option('dry-run');

        $linkedAcctIds = DB::table('sync_entity_links')
            ->where('entity_key', SupplierPaymentSync::KEY)
            ->pluck('acct_id')
            ->all();

        $query = Voucher::query()
            ->where('voucher_type', VoucherType::Payment)
            ->where('status', '!=', VoucherStatus::Cancelled)
            ->when($linkedAcctIds !== [], fn ($q) => $q->whereNotIn('id', $linkedAcctIds))
            ->orderBy('id')
            ->limit($limit);

        $pulled = 0;
        $skipped = 0;

        foreach ($query->cursor() as $voucher) {
            if (SyncLinks::dmsId(SupplierPaymentSync::KEY, $voucher)) {
                $skipped++;
                continue;
            }

            if ($dry) {
                $this->line('[dry-run] would pull '.$voucher->voucher_number);
                $pulled++;
                continue;
            }

            try {
                SyncGuard::run(function () use ($sync, $voucher) {
                    $sync->pull($voucher->fresh(['entries.ledger.accountGroup']));
                });
                if (SyncLinks::dmsId(SupplierPaymentSync::KEY, $voucher)) {
                    $pulled++;
                    $this->line('Pulled '.$voucher->voucher_number);
                } else {
                    $skipped++;
                    $this->line('Skipped '.$voucher->voucher_number.' (no supplier debit / party)');
                }
            } catch (\Throwable $e) {
                $skipped++;
                $this->error($voucher->voucher_number.': '.$e->getMessage());
            }
        }

        $this->info(($dry ? 'Dry-run candidates: ' : 'Pulled: ').$pulled.'; skipped: '.$skipped);

        return self::SUCCESS;
    }
}
