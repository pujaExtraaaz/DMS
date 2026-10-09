<?php

namespace Tally\Banking;

use Tally\Accounting\Money;
use Tally\Models\BankReconciliation;
use Tally\Models\FinancialYear;
use App\Models\User;
use Tally\Models\Voucher;
use Tally\Models\VoucherEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Marks a posted bank line as reconciled. It never posts a voucher.
 */
class BankReconciliationService
{
    public function __construct(private readonly BankBook $book) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function reconcile(VoucherEntry $entry, User $user, FinancialYear $year, array $input): BankReconciliation
    {
        $this->assertOwner($user);

        return DB::transaction(function () use ($entry, $year, $input) {
            $entry = $this->lockPostedBankLine($entry);
            $book = $this->book->bookAmount($entry);

            if ($book['cents'] <= 0) {
                throw ValidationException::withMessages([
                    'bank_amount' => 'This line has no book amount to reconcile.',
                ]);
            }

            $bank = $this->amount((string) ($input['bank_amount'] ?? ''));
            $transactionDate = $this->date($year, (string) ($input['transaction_date'] ?? ''), 'transaction_date');
            $reconciledOn = $this->date($year, (string) ($input['reconciliation_date'] ?? ''), 'reconciliation_date');
            $before = Voucher::query()->count();

            $reconciliation = BankReconciliation::query()->updateOrCreate(
                ['voucher_entry_id' => $entry->id],
                [
                    'company_id' => $entry->voucher->company_id,
                    'ledger_id' => $entry->ledger_id,
                    'reference' => $this->blank($input['reference'] ?? null),
                    'transaction_date' => $transactionDate,
                    'book_amount' => $book['amount'],
                    'bank_amount' => Money::format($bank),
                    'status' => ReconciliationStatus::Reconciled,
                    'reconciled_on' => $reconciledOn,
                ],
            );

            if (Voucher::query()->count() !== $before) {
                throw ValidationException::withMessages([
                    'status' => 'Reconciliation cannot create an accounting entry.',
                ]);
            }

            return $reconciliation;
        });
    }

    public function unreconcile(VoucherEntry $entry, User $user): BankReconciliation
    {
        $this->assertOwner($user);

        return DB::transaction(function () use ($entry) {
            $entry = VoucherEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $reconciliation = BankReconciliation::query()->where('voucher_entry_id', $entry->id)->lockForUpdate()->first();

            if (! $reconciliation || $reconciliation->status !== ReconciliationStatus::Reconciled) {
                throw ValidationException::withMessages([
                    'status' => 'This transaction is not reconciled.',
                ]);
            }

            $before = Voucher::query()->count();
            $reconciliation->update([
                'status' => ReconciliationStatus::Unreconciled,
                'reconciled_on' => null,
            ]);

            if (Voucher::query()->count() !== $before) {
                throw ValidationException::withMessages([
                    'status' => 'Reconciliation cannot create an accounting entry.',
                ]);
            }

            return $reconciliation->fresh();
        });
    }

    private function lockPostedBankLine(VoucherEntry $entry): VoucherEntry
    {
        $entry = VoucherEntry::query()->with(['voucher', 'ledger.accountGroup.parent'])->whereKey($entry->id)->lockForUpdate()->firstOrFail();

        if (! $entry->voucher->isPosted()) {
            throw ValidationException::withMessages([
                'status' => 'Only a posted transaction can be reconciled.',
            ]);
        }

        if (! $entry->ledger->isBank()) {
            throw ValidationException::withMessages([
                'ledger_id' => 'Choose a line on a bank ledger.',
            ]);
        }

        return $entry;
    }

    private function assertOwner(User $user): void
    {
        if (! tally_super_admin($user)) {
            throw ValidationException::withMessages([
                'status' => 'Only the Super Admin can reconcile bank transactions.',
            ]);
        }
    }

    private function amount(string $value): int
    {
        try {
            $cents = Money::cents(trim($value));
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'bank_amount' => 'Enter a bank amount with up to 2 decimal places.',
            ]);
        }

        if ($cents < 0) {
            throw ValidationException::withMessages([
                'bank_amount' => 'Enter a bank amount that is zero or more.',
            ]);
        }

        return $cents;
    }

    private function date(FinancialYear $year, string $value, string $field): string
    {
        if ($value === '' || ! strtotime($value)) {
            throw ValidationException::withMessages([
                $field => 'Enter a date.',
            ]);
        }

        $date = date('Y-m-d', strtotime($value));
        $start = $year->start_date->toDateString();
        $end = $year->end_date->toDateString();

        if ($date < $start || $date > $end) {
            throw ValidationException::withMessages([
                $field => 'The date must fall in '.$year->name.' ('.$year->rangeLabel().').',
            ]);
        }

        return $date;
    }

    private function blank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
