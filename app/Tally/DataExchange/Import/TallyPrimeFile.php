<?php

namespace Tally\DataExchange\Import;

use Tally\Accounting\OpeningBalanceType;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\AccountGroup;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;

/**
 * Reads a TallyPrime XML export of ledgers and accounting vouchers.
 */
final class TallyPrimeFile
{
    /**
     * @var array<string, string>
     */
    private const PARENTS = [
        'cash-in-hand' => 'CASH',
        'cash' => 'CASH',
        'bank accounts' => 'BANK',
        'bank' => 'BANK',
        'sundry debtors' => 'DEBTORS',
        'sundry creditors' => 'CREDITORS',
        'sales accounts' => 'SALES',
        'purchase accounts' => 'PURCHASE',
        'direct expenses' => 'DIRECT_EXPENSES',
        'indirect expenses' => 'INDIRECT_EXPENSES',
        'direct incomes' => 'DIRECT_INCOMES',
        'indirect incomes' => 'INDIRECT_INCOMES',
        'duties & taxes' => 'DUTIES',
        'duties and taxes' => 'DUTIES',
        'capital account' => 'CAPITAL',
        'current assets' => 'CURRENT_ASSETS',
        'current liabilities' => 'CURRENT_LIABILITIES',
        'fixed assets' => 'FIXED_ASSETS',
        'investments' => 'INVESTMENTS',
        'loans (liability)' => 'LOANS',
        'stock-in-hand' => 'STOCK',
    ];

    public function __construct(private readonly VoucherEngine $engine) {}

    /**
     * @return array{ledgers: int, vouchers: int, skipped: list<string>}
     */
    public function import(string $xml, Company $company, Branch $branch, FinancialYear $year, User $user): array
    {
        $document = simplexml_load_string($xml);

        if (! $document instanceof SimpleXMLElement) {
            throw ValidationException::withMessages([
                'file' => 'This file is not a TallyPrime XML export.',
            ]);
        }

        return DB::transaction(function () use ($document, $company, $branch, $year, $user) {
            $ledgers = 0;
            $vouchers = 0;
            $skipped = [];
            $groups = AccountGroup::query()->where('company_id', $company->id)->get()->keyBy('code');

            foreach ($document->xpath('//LEDGER') ?: [] as $node) {
                $name = trim((string) ($node['NAME'] ?? ''));

                if ($name === '' || Ledger::query()->where('company_id', $company->id)->whereRaw('lower(name) = ?', [mb_strtolower($name)])->exists()) {
                    continue;
                }

                $parent = mb_strtolower(trim((string) ($node->PARENT ?? '')));
                $code = self::PARENTS[$parent] ?? null;
                $group = $code ? $groups->get($code) : null;

                if (! $group) {
                    throw ValidationException::withMessages([
                        'file' => 'Ledger '.$name.' uses the group "'.trim((string) ($node->PARENT ?? '')).'", which is not in this company.',
                    ]);
                }

                $opening = (float) str_replace(',', '', (string) ($node->OPENINGBALANCE ?? '0'));
                Ledger::query()->create([
                    'company_id' => $company->id,
                    'account_group_id' => $group->id,
                    'name' => $name,
                    'opening_balance' => number_format(abs($opening), 2, '.', ''),
                    'opening_balance_type' => $opening < 0 ? OpeningBalanceType::Credit : OpeningBalanceType::Debit,
                    'is_active' => true,
                ]);
                $ledgers++;
            }

            foreach ($document->xpath('//VOUCHER') ?: [] as $node) {
                $typeName = trim((string) ($node['VCHTYPE'] ?? ''));
                $type = match (strtolower($typeName)) {
                    'payment' => VoucherType::Payment,
                    'receipt' => VoucherType::Receipt,
                    'contra' => VoucherType::Contra,
                    'journal' => VoucherType::Journal,
                    default => null,
                };

                if ($type === null) {
                    $skipped[] = ($typeName !== '' ? $typeName : 'Voucher').' '.trim((string) ($node->VOUCHERNUMBER ?? ''));

                    continue;
                }

                $entries = [];

                foreach ($node->xpath('.//ALLLEDGERENTRIES.LIST|.//LEDGERENTRIES.LIST') ?: [] as $line) {
                    $ledgerName = trim((string) ($line->LEDGERNAME ?? ''));
                    $ledger = Ledger::query()->where('company_id', $company->id)->whereRaw('lower(name) = ?', [mb_strtolower($ledgerName)])->first();

                    if (! $ledger) {
                        throw ValidationException::withMessages([
                            'file' => 'Voucher '.$typeName.' uses ledger "'.$ledgerName.'", which is not in this company.',
                        ]);
                    }

                    $amount = (float) str_replace(',', '', (string) ($line->AMOUNT ?? '0'));
                    $entries[] = [
                        'ledger_id' => $ledger->id,
                        'debit' => $amount < 0 ? number_format(abs($amount), 2, '.', '') : '0',
                        'credit' => $amount > 0 ? number_format($amount, 2, '.', '') : '0',
                    ];
                }

                $rawDate = preg_replace('/\D/', '', (string) ($node->DATE ?? '')) ?? '';
                $date = strlen($rawDate) === 8
                    ? substr($rawDate, 0, 4).'-'.substr($rawDate, 4, 2).'-'.substr($rawDate, 6, 2)
                    : '';

                $this->engine->save($company, $branch, $year, $user, $type, [
                    'voucher_date' => $date,
                    'narration' => trim((string) ($node->NARRATION ?? '')),
                    'reference_number' => trim((string) ($node->VOUCHERNUMBER ?? '')),
                    'entries' => $entries,
                ], true);
                $vouchers++;
            }

            return compact('ledgers', 'vouchers', 'skipped');
        });
    }
}
