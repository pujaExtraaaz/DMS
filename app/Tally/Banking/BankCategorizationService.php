<?php

namespace Tally\Banking;

use Illuminate\Support\Facades\Schema;
use Tally\Models\BankCategorizationRule;
use Tally\Models\BankStatementLine;
use Tally\Models\Company;

class BankCategorizationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Company $company, array $data, ?BankCategorizationRule $rule = null): BankCategorizationRule
    {
        $payload = [
            'company_id' => $company->id,
            'name' => $data['name'],
            'match_field' => $data['match_field'] ?? 'narration',
            'match_type' => $data['match_type'] ?? 'contains',
            'match_value' => $data['match_value'],
            'ledger_id' => (int) $data['ledger_id'],
            'priority' => (int) ($data['priority'] ?? 100),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];

        if ($rule) {
            $rule->update($payload);

            return $rule->fresh();
        }

        return BankCategorizationRule::query()->create($payload);
    }

    public function delete(BankCategorizationRule $rule): void
    {
        $rule->delete();
    }

    /**
     * Apply active rules to unmatched statement lines when the table/columns exist.
     *
     * @return int Number of lines categorized
     */
    public function apply(Company $company): int
    {
        if (! class_exists(BankStatementLine::class) || ! Schema::hasTable('acct_bank_statement_lines')) {
            return 0;
        }

        if (! Schema::hasColumn('acct_bank_statement_lines', 'categorized_ledger_id')) {
            return 0;
        }

        $rules = BankCategorizationRule::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        if ($rules->isEmpty()) {
            return 0;
        }

        $lines = BankStatementLine::query()
            ->where('company_id', $company->id)
            ->whereNull('voucher_entry_id')
            ->whereNull('categorized_ledger_id')
            ->orderBy('statement_date')
            ->get();

        $count = 0;

        foreach ($lines as $line) {
            foreach ($rules as $rule) {
                if ($this->matches($line, $rule)) {
                    $line->update(['categorized_ledger_id' => $rule->ledger_id]);
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    private function matches(BankStatementLine $line, BankCategorizationRule $rule): bool
    {
        $haystack = match ($rule->match_field) {
            'reference' => (string) $line->reference,
            'amount' => number_format((float) $line->debit + (float) $line->credit, 2, '.', ''),
            default => (string) $line->narration,
        };

        $needle = (string) $rule->match_value;

        return match ($rule->match_type) {
            'equals' => strcasecmp($haystack, $needle) === 0,
            'starts_with' => str_starts_with(mb_strtolower($haystack), mb_strtolower($needle)),
            'regex' => @preg_match('/'.$needle.'/i', $haystack) === 1,
            default => str_contains(mb_strtolower($haystack), mb_strtolower($needle)),
        };
    }
}
