<?php

namespace App\Console\Commands;

use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sync\Handlers\GoodsReceiptSync;
use App\Domains\Sync\Handlers\PurchaseInvoiceSync;
use App\Domains\Sync\Support\SyncLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tally\Invoicing\InvoiceKind;
use Tally\Models\GoodsReceipt;
use Tally\Models\Invoice as BooksInvoice;

/**
 * Detect historical PI + RN double-stock cases in DMS / Books.
 *
 * Default is dry-run report only. Use --fix-books-pi-godown to clear godown
 * on Books purchase invoice lines that also have a linked Receipt Note GRN
 * (prevents further InvoiceStock double posts; does not reverse past qty).
 */
class DetectDuplicatePurchaseStockCommand extends Command
{
    protected $signature = 'dms:detect-duplicate-purchase-stock
        {--fix-books-pi-godown : Clear godown on Books PI lines that also have RN GRN stock}
        {--company= : Limit to DMS organization company id}';

    protected $description = 'Detect (and optionally harden) PI+RN double-stock linking cases';

    public function handle(): int
    {
        $companyId = $this->option('company') ? (int) $this->option('company') : null;

        $this->info('Scanning purchase invoices with receipt notes…');

        $query = PurchaseInvoice::query()
            ->with(['receiptNote', 'items'])
            ->whereNotNull('receipt_note_id')
            ->where('status', 'posted');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $suspects = [];
        $fixed = 0;

        foreach ($query->cursor() as $invoice) {
            $rn = $invoice->receiptNote;
            if (! $rn) {
                continue;
            }

            $dmsPiMoves = StockMovement::query()
                ->where('reference_type', $invoice->getMorphClass())
                ->where('reference_id', $invoice->id)
                ->count();

            $dmsRnMoves = StockMovement::query()
                ->where('reference_type', $rn->getMorphClass())
                ->where('reference_id', $rn->id)
                ->count();

            $booksPi = BooksInvoice::query()->find(SyncLinks::acctId(PurchaseInvoiceSync::KEY, $invoice));
            $booksGrn = GoodsReceipt::query()->find(SyncLinks::acctId(GoodsReceiptSync::KEY, $rn));

            $booksPiHasGodown = false;
            if ($booksPi && $booksPi->kind === InvoiceKind::Purchase) {
                $booksPi->loadMissing('lines');
                $booksPiHasGodown = $booksPi->lines->contains(fn ($line) => ! empty($line->godown_id));
            }

            $overlap = ($dmsPiMoves > 0 && $dmsRnMoves > 0)
                || ($booksPiHasGodown && $booksGrn && $booksGrn->status === 'posted')
                || ($booksPiHasGodown && $dmsRnMoves > 0);

            if (! $overlap) {
                continue;
            }

            $suspects[] = [
                'pi' => $invoice->invoice_no,
                'rn' => $rn->receipt_note_no,
                'dms_pi_moves' => $dmsPiMoves,
                'dms_rn_moves' => $dmsRnMoves,
                'books_pi_godown' => $booksPiHasGodown ? 'yes' : 'no',
                'books_grn' => $booksGrn?->status ?? '—',
            ];

            if ($this->option('fix-books-pi-godown') && $booksPiHasGodown && $booksPi) {
                DB::transaction(function () use ($booksPi) {
                    $booksPi->lines()->whereNotNull('godown_id')->update(['godown_id' => null]);
                });
                $fixed++;
            }
        }

        if ($suspects === []) {
            $this->info('No overlapping PI+RN stock cases found.');

            return self::SUCCESS;
        }

        $this->table(
            ['PI', 'RN', 'DMS PI moves', 'DMS RN moves', 'Books PI godown', 'Books GRN'],
            $suspects
        );

        $this->warn(count($suspects).' candidate case(s). Review stock ledgers before reversing history.');
        if ($this->option('fix-books-pi-godown')) {
            $this->info("Cleared godown on {$fixed} Books PI twin(s) (future InvoiceStock skip).");
        } else {
            $this->line('Re-run with --fix-books-pi-godown to harden Books PI lines (no historical qty reverse).');
        }

        return self::SUCCESS;
    }
}
