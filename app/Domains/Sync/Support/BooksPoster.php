<?php

namespace App\Domains\Sync\Support;

use App\Models\User;
use Throwable;
use Tally\Accounting\VoucherEngine;
use Tally\Invoicing\InvoiceService;
use Tally\Models\Invoice;
use Tally\Models\Voucher;

/**
 * Posts a books draft when the DMS document is already live.
 * A tax or credit-limit rejection leaves the draft and is logged.
 */
final class BooksPoster
{
    public function invoice(Invoice $invoice, string $entityKey): void
    {
        $user = auth()->user() ?? User::query()->orderBy('id')->first();
        if (! $user) {
            return;
        }

        try {
            app(InvoiceService::class)->post($invoice->fresh(), $user);
        } catch (Throwable $exception) {
            SyncFailureLogger::write($entityKey, 'dms_to_books', $invoice, $exception);
        }
    }

    public function voucher(Voucher $voucher, string $entityKey): void
    {
        try {
            app(VoucherEngine::class)->post($voucher->fresh());
        } catch (Throwable $exception) {
            SyncFailureLogger::write($entityKey, 'dms_to_books', $voucher, $exception);
        }
    }
}
