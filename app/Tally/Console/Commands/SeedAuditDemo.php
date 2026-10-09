<?php

namespace Tally\Console\Commands;

use Tally\Accounting\OpeningBalanceType;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Audit\AuditLogger;
use Tally\Demo\DemoData;
use Tally\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class SeedAuditDemo extends Command
{
    protected $signature = 'demo:audit';

    protected $description = 'Seed a separate development company that demonstrates the audit trail';

    public function handle(VoucherEngine $engine, AuditLogger $audit): int
    {
        app(DemoData::class)->assertAllowed();

        if (Company::query()->where('name', 'Audit Trail Demo')->exists()) {
            $this->info('Audit Trail Demo is already present. Open Settings → Audit Trail.');

            return self::SUCCESS;
        }

        $user = User::query()->first();

        if (! $user) {
            $this->error('Create the Super Admin before seeding the audit demo.');

            return self::FAILURE;
        }

        $company = Company::provision([
            'name' => 'Audit Trail Demo',
            'legal_name' => 'Audit Trail Demo',
            'state' => 'Karnataka',
            'country' => 'India',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'is_active' => true,
        ]);
        $branch = $company->branches()->create([
            'name' => 'Head Office',
            'code' => 'AUDIT',
            'state' => 'Karnataka',
            'country' => 'India',
            'is_active' => true,
        ]);
        $year = $company->financialYears()->firstOrFail();
        $ledger = function (string $group, string $name, string $code) use ($company) {
            return $company->ledgers()->create([
                'account_group_id' => $company->accountGroups()->where('code', $group)->value('id'),
                'name' => $name,
                'code' => $code,
                'opening_balance' => '0.00',
                'opening_balance_type' => OpeningBalanceType::Debit,
                'is_active' => true,
                'is_system' => false,
            ]);
        };
        $sample = $ledger('INDIRECT_EXPENSES', 'Audit sample', 'AUDIT-EXP');
        $sample->update(['name' => 'Audit sample expense']);
        $cash = $ledger('CASH', 'Audit cash', 'AUDIT-CASH');
        $voucher = $engine->save($company, $branch, $year, $user, VoucherType::Journal, [
            'voucher_date' => '2026-08-01',
            'narration' => 'Audit demonstration journal.',
            'entries' => [
                ['ledger_id' => $sample->id, 'debit' => '25.00', 'credit' => '0'],
                ['ledger_id' => $cash->id, 'debit' => '0', 'credit' => '25.00'],
            ],
        ], true);

        if (! $sample->fresh()->canBeDeleted()) {
            $audit->blocked($sample, 'This ledger cannot be deleted because accounting entries depend on it.');
        }

        $sample->update(['is_active' => false]);

        try {
            $engine->save($company, $branch, $year, $user, $voucher->voucher_type, [
                'voucher_date' => '2026-08-02',
                'narration' => 'This edit must be refused.',
                'entries' => [
                    ['ledger_id' => $sample->id, 'debit' => '25.00', 'credit' => '0'],
                    ['ledger_id' => $cash->id, 'debit' => '0', 'credit' => '25.00'],
                ],
            ], false, $voucher);
        } catch (ValidationException) {
            // The engine records change_blocked before refusing the edit.
        }

        $engine->cancel($voucher->fresh());
        $this->info('Audit Trail Demo is ready. Open Settings → Audit Trail and filter by that company.');

        return self::SUCCESS;
    }
}
