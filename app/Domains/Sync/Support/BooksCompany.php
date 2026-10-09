<?php

namespace App\Domains\Sync\Support;

use App\Domains\Organization\Models\Company as OrganizationCompany;
use Illuminate\Support\Facades\DB;
use Tally\Accounting\OpeningBalanceType;
use Tally\Models\AccountGroup;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Sync\Handlers\WarehouseSync;
use Tally\Models\Godown;
use Tally\Models\Ledger;
use Tally\Models\ProductGroup;

final class BooksCompany
{
    public static function forOrganization(int $organizationCompanyId): ?Company
    {
        $acctId = DB::table('accounting_company_links')
            ->where('organization_company_id', $organizationCompanyId)
            ->value('acct_company_id');

        return $acctId ? Company::query()->find($acctId) : null;
    }

    public static function organizationId(int $acctCompanyId): ?int
    {
        $id = DB::table('accounting_company_links')
            ->where('acct_company_id', $acctCompanyId)
            ->value('organization_company_id');

        return $id ? (int) $id : null;
    }

    public static function branchId(int $organizationCompanyId, ?int $dmsBranchId = null): ?int
    {
        $link = self::link($organizationCompanyId);
        if (! $link) {
            return null;
        }

        $map = self::settings($link)['branches'] ?? [];
        if ($dmsBranchId && isset($map[(string) $dmsBranchId])) {
            return (int) $map[(string) $dmsBranchId];
        }

        return $link->acct_branch_id ? (int) $link->acct_branch_id : null;
    }

    public static function defaultGodown(Company $company, int $organizationId): int
    {
        $existing = Godown::query()->where('company_id', $company->id)->where('is_active', true)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $warehouse = Warehouse::query()->where('company_id', $organizationId)->orderBy('id')->first();
        if ($warehouse) {
            app(WarehouseSync::class)->push($warehouse);
            $linked = SyncLinks::acctId(WarehouseSync::KEY, $warehouse);
            if ($linked) {
                return $linked;
            }
        }

        return Godown::query()->create([
            'company_id' => $company->id,
            'name' => 'Main Location',
            'code' => 'MAIN',
            'is_active' => true,
        ])->id;
    }

    public static function yearFor(Company $company, string $date): ?FinancialYear
    {
        return FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first()
            ?: FinancialYear::query()->where('company_id', $company->id)->where('is_active', true)->first();
    }

    public static function salesLedger(Company $company): Ledger
    {
        return self::namedLedger($company, 'SALES', 'Sales', 'SALES');
    }

    public static function purchaseLedger(Company $company): Ledger
    {
        return self::namedLedger($company, 'PURCHASE', 'Purchase', 'PURCHASE');
    }

    public static function cashLedger(Company $company): Ledger
    {
        return self::namedLedger($company, 'CASH', 'Cash', 'CASH');
    }

    public static function generalGroup(Company $company): ProductGroup
    {
        $group = ProductGroup::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'General'],
            ['code' => 'GENERAL', 'is_active' => true],
        );

        return $group;
    }

    public static function linkRow(int $organizationCompanyId): ?object
    {
        return self::link($organizationCompanyId);
    }

    private static function namedLedger(Company $company, string $groupCode, string $name, string $code): Ledger
    {
        $existing = Ledger::query()->where('company_id', $company->id)->where('code', $code)->first();
        if ($existing) {
            return $existing;
        }

        $group = AccountGroup::query()->where('company_id', $company->id)->where('code', $groupCode)->first();
        if (! $group) {
            throw new \RuntimeException("Account group {$groupCode} is missing for {$company->name}.");
        }

        return Ledger::query()->create([
            'company_id' => $company->id,
            'account_group_id' => $group->id,
            'name' => $name,
            'code' => $code,
            'opening_balance' => 0,
            'opening_balance_type' => OpeningBalanceType::Debit,
            'is_active' => true,
            'is_system' => true,
        ]);
    }

    private static function link(int $organizationCompanyId): ?object
    {
        return DB::table('accounting_company_links')
            ->where('organization_company_id', $organizationCompanyId)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private static function settings(object $link): array
    {
        $settings = $link->settings ?? null;
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }

        return is_array($settings) ? $settings : [];
    }

    public static function rememberMaps(OrganizationCompany $organization, Company $books, Branch $branch, FinancialYear $year, array $branches, array $years): void
    {
        $payload = [
            'acct_company_id' => $books->id,
            'acct_branch_id' => $branch->id,
            'acct_financial_year_id' => $year->id,
            'settings' => json_encode(['branches' => $branches, 'years' => $years]),
            'updated_at' => now(),
        ];
        $query = DB::table('accounting_company_links')->where('organization_company_id', $organization->id);
        if ($query->exists()) {
            $query->update($payload);

            return;
        }

        DB::table('accounting_company_links')->insert($payload + [
            'organization_company_id' => $organization->id,
            'created_at' => now(),
        ]);
    }
}
