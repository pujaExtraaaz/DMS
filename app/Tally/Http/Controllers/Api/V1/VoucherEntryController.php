<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Models\Company;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;
use Illuminate\Http\JsonResponse;

class VoucherEntryController extends ApiController
{
    public function index(Company $company, Voucher $voucher): JsonResponse
    {
        $entries = $voucher->entries()->with('ledger')->orderBy('line_number')->get();

        return $this->data($entries->map(fn (VoucherEntry $entry) => [
            'id' => $entry->id,
            'voucher_id' => $entry->voucher_id,
            'line_number' => $entry->line_number,
            'ledger_id' => $entry->ledger_id,
            'ledger_name' => $entry->ledger?->name,
            'debit' => (string) $entry->debit,
            'credit' => (string) $entry->credit,
            'narration' => $entry->narration,
            'reference' => $entry->reference,
            'created_at' => $entry->created_at?->toIso8601String(),
            'updated_at' => $entry->updated_at?->toIso8601String(),
        ])->values());
    }
}
