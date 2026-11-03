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

    /**
     * Create the Books company (and link) from a DMS company when it is missing.
     * Safe to call on every DMS company save.
     */
    public static function ensureForOrganization(OrganizationCompany $organization): Company
    {
        $existing = self::forOrganization($organization->id);
        if ($existing) {
            self::mirror($organization, $existing);

            return $existing->fresh(['financialYears', 'branches']) ?: $existing;
        }

        return DB::transaction(function () use ($organization) {
            [$start, $end, $years] = self::organizationYears($organization);
            $gstin = filled($organization->gstin)
                && filled($organization->state)
                && ! Company::query()->where('gstin', $organization->gstin)->exists()
                ? $organization->gstin
                : null;

            $books = Company::provision([
                'name' => $organization->name,
                'legal_name' => $organization->legal_name ?: $organization->name,
                'mailing_name' => $organization->name,
                'address' => $organization->address,
                'city' => $organization->city,
                'state' => $organization->state,
                'country' => $organization->country ?: 'India',
                'pincode' => $organization->pincode,
                'phone' => $organization->phone,
                'email' => $organization->email,
                'website' => $organization->website,
                'gstin' => $gstin,
                'pan' => $organization->pan,
                'sales_terms' => $organization->selling_terms_and_conditions,
                'purchase_terms' => $organization->purchase_terms_and_conditions,
                'financial_year_start' => $start->toDateString(),
                'financial_year_end' => $end->toDateString(),
                'is_active' => (bool) $organization->is_active,
            ]);

            $primaryYear = $books->financialYears()->orderBy('id')->first();
            $yearMap = [];
            foreach ($years as $index => $year) {
                if ($index === 0 && $primaryYear) {
                    $yearMap[(string) $year->id] = $primaryYear->id;
                    $primaryYear->update([
                        'name' => FinancialYear::labelForPeriod($year->starts_on, $year->ends_on),
                        'start_date' => $year->starts_on->toDateString(),
                        'end_date' => $year->ends_on->toDateString(),
                        'is_active' => ! $year->is_closed,
                    ]);

                    continue;
                }
                $created = $books->financialYears()->create([
                    'name' => FinancialYear::labelForPeriod($year->starts_on, $year->ends_on),
                    'start_date' => $year->starts_on->toDateString(),
                    'end_date' => $year->ends_on->toDateString(),
                    'is_active' => ! $year->is_closed,
                ]);
                $yearMap[(string) $year->id] = $created->id;
            }

            $defaultBranch = $books->branches()->orderBy('id')->first();
            $orgBranches = \App\Domains\Organization\Models\Branch::query()->where('company_id', $organization->id)->get();
            $branchMap = [];
            $acctBranch = $defaultBranch;

            foreach ($orgBranches as $branch) {
                $code = $branch->code ?: ('B'.$branch->id);
                $existingBranch = $books->branches()
                    ->where(function ($query) use ($code, $branch) {
                        $query->where('code', $code)->orWhere('name', $branch->name);
                    })
                    ->first();

                if ($existingBranch) {
                    $existingBranch->update([
                        'name' => $branch->name ?: $existingBranch->name,
                        'address' => $branch->address ?: $existingBranch->address,
                        'state' => $branch->state ?: $existingBranch->state,
                        'pincode' => $branch->pincode ?: $existingBranch->pincode,
                        'phone' => $branch->phone ?: $existingBranch->phone,
                        'is_active' => (bool) $branch->is_active,
                    ]);
                    $created = $existingBranch;
                } else {
                    $created = Branch::query()->create([
                        'company_id' => $books->id,
                        'name' => $branch->name,
                        'code' => $code,
                        'address' => $branch->address,
                        'state' => $branch->state,
                        'country' => 'India',
                        'pincode' => $branch->pincode,
                        'phone' => $branch->phone,
                        'is_active' => (bool) $branch->is_active,
                    ]);
                }
                $branchMap[(string) $branch->id] = $created->id;
                $acctBranch ??= $created;
            }

            $current = $years->firstWhere('is_current', true) ?? $years->first();
            $linkedYear = $current && isset($yearMap[(string) $current->id])
                ? FinancialYear::query()->find($yearMap[(string) $current->id])
                : $primaryYear;

            self::rememberMaps($organization, $books, $acctBranch, $linkedYear, $branchMap, $yearMap);
            self::salesLedger($books);
            self::cashLedger($books);
            self::generalGroup($books);

            return $books->fresh(['financialYears', 'branches']) ?: $books;
        });
    }

    public static function mirror(OrganizationCompany $organization, Company $books): void
    {
        $gstin = $organization->gstin;
        if ($gstin && Company::query()->where('gstin', $gstin)->where('id', '!=', $books->id)->exists()) {
            $gstin = $books->gstin;
        }

        $books->fill([
            'name' => $organization->name,
            'legal_name' => $organization->legal_name ?: $organization->name,
            'mailing_name' => $organization->name,
            'address' => $organization->address,
            'city' => $organization->city,
            'state' => $organization->state,
            'country' => $organization->country ?: 'India',
            'pincode' => $organization->pincode,
            'phone' => $organization->phone,
            'email' => $organization->email,
            'website' => $organization->website,
            'gstin' => $gstin,
            'pan' => $organization->pan,
            'sales_terms' => $organization->selling_terms_and_conditions,
            'purchase_terms' => $organization->purchase_terms_and_conditions,
            'is_active' => (bool) $organization->is_active,
        ])->save();
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon, 2: \Illuminate\Support\Collection<int, \App\Domains\Organization\Models\FinancialYear>}
     */
    private static function organizationYears(OrganizationCompany $organization): array
    {
        $years = \App\Domains\Organization\Models\FinancialYear::query()
            ->where('company_id', $organization->id)
            ->orderBy('starts_on')
            ->get();

        if ($years->isEmpty()) {
            $start = now()->month >= 4
                ? now()->startOfYear()->month(4)->startOfMonth()
                : now()->subYear()->startOfYear()->month(4)->startOfMonth();

            return [$start, $start->copy()->addYear()->subDay(), $years];
        }

        return [$years->first()->starts_on, $years->first()->ends_on, $years];
    }
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

        if ($dmsBranchId) {
            $fromLink = DB::table('sync_entity_links')
                ->where('entity_key', 'branch')
                ->where('dms_type', \App\Domains\Organization\Models\Branch::class)
                ->where('dms_id', $dmsBranchId)
                ->value('acct_id');
            if ($fromLink) {
                return (int) $fromLink;
            }
        }

        $map = self::settings($link)['branches'] ?? [];
        if ($dmsBranchId && isset($map[(string) $dmsBranchId])) {
            return (int) $map[(string) $dmsBranchId];
        }

        return $link->acct_branch_id ? (int) $link->acct_branch_id : null;
    }

    /** Merge one DMS→Books branch id into accounting_company_links.settings.branches. */
    public static function putBranchMap(int $organizationCompanyId, int $dmsBranchId, int $acctBranchId): void
    {
        self::putSettingsMap($organizationCompanyId, 'branches', $dmsBranchId, $acctBranchId, 'acct_branch_id');
    }

    /** Merge one DMS→Books financial year id into accounting_company_links.settings.years. */
    public static function putYearMap(int $organizationCompanyId, int $dmsYearId, int $acctYearId, bool $isCurrent = false): void
    {
        self::putSettingsMap(
            $organizationCompanyId,
            'years',
            $dmsYearId,
            $acctYearId,
            $isCurrent ? 'acct_financial_year_id' : null
        );
    }

    private static function putSettingsMap(
        int $organizationCompanyId,
        string $key,
        int $dmsId,
        int $acctId,
        ?string $defaultColumn = null,
    ): void {
        $link = self::link($organizationCompanyId);
        if (! $link) {
            return;
        }

        $settings = self::settings($link);
        $map = $settings[$key] ?? [];
        $map[(string) $dmsId] = $acctId;
        $settings[$key] = $map;

        $payload = [
            'settings' => json_encode($settings),
            'updated_at' => now(),
        ];
        if ($defaultColumn && empty($link->{$defaultColumn})) {
            $payload[$defaultColumn] = $acctId;
        } elseif ($defaultColumn === 'acct_financial_year_id') {
            $payload[$defaultColumn] = $acctId;
        }

        DB::table('accounting_company_links')
            ->where('organization_company_id', $organizationCompanyId)
            ->update($payload);
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

    /**
     * Prefer an existing Bank Accounts ledger (company HDFC etc.), else create a default bank ledger.
     */
    public static function bankLedger(Company $company): Ledger
    {
        $bankGroup = AccountGroup::query()->where('company_id', $company->id)->where('code', 'BANK')->first();
        if ($bankGroup) {
            $existing = Ledger::query()
                ->where('company_id', $company->id)
                ->where('account_group_id', $bankGroup->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return self::namedLedger($company, 'BANK', 'Bank Account', 'BANK');
    }

    /**
     * Map DMS payment method → cash or bank ledger for receipts/payments.
     */
    public static function receiptLedger(Company $company, ?string $method = null): Ledger
    {
        $method = strtolower(trim((string) $method));

        return match (true) {
            in_array($method, ['cash', ''], true) => self::cashLedger($company),
            in_array($method, ['bank', 'neft', 'rtgs', 'imps', 'upi', 'card', 'cheque', 'online', 'transfer'], true) => self::bankLedger($company),
            default => self::bankLedger($company),
        };
    }

    public static function clearingLedger(Company $company): Ledger
    {
        return self::namedLedger($company, 'CURRENT_ASSETS', 'Bank clearing', 'BANK-CLEAR');
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
