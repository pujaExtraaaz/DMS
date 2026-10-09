<?php

namespace Tally\Context;

use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class WorkspaceContext
{
    public const COMPANY_ID = 'workspace.company_id';

    public const BRANCH_ID = 'workspace.branch_id';

    public const FINANCIAL_YEAR_ID = 'workspace.financial_year_id';

    private ?Company $company = null;

    private ?Branch $branch = null;

    private ?FinancialYear $financialYear = null;

    private ?Collection $companyOptions = null;

    private ?Collection $branchOptions = null;

    private ?Collection $financialYearOptions = null;

    public function resolve(): void
    {
        $this->company = null;
        $this->branch = null;
        $this->financialYear = null;
        $this->companyOptions = null;
        $this->branchOptions = null;
        $this->financialYearOptions = null;

        $company = $this->findActiveCompany(session(self::COMPANY_ID));

        if (! $company) {
            $active = $this->visibleCompanies()->limit(2)->get();
            $company = $active->count() === 1 ? $active->first() : null;

            if ($company) {
                session([self::COMPANY_ID => $company->id]);
            } else {
                session()->forget([self::COMPANY_ID, self::BRANCH_ID, self::FINANCIAL_YEAR_ID]);

                return;
            }
        }

        $this->company = $company;
        $this->branch = $this->resolveBranch($company);
        $this->financialYear = $this->resolveFinancialYear($company);
    }

    public function company(): ?Company
    {
        return $this->company;
    }

    public function branch(): ?Branch
    {
        return $this->branch;
    }

    public function financialYear(): ?FinancialYear
    {
        return $this->financialYear;
    }

    public function companyId(): ?int
    {
        return $this->company?->id;
    }

    public function branchId(): ?int
    {
        return $this->branch?->id;
    }

    public function financialYearId(): ?int
    {
        return $this->financialYear?->id;
    }

    /**
     * @return Collection<int, Company>
     */
    public function companies(): Collection
    {
        return $this->companyOptions ??= $this->visibleCompanies()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Branch>
     */
    public function branches(): Collection
    {
        if (! $this->company) {
            return new Collection;
        }

        return $this->branchOptions ??= $this->company
            ->branches()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, FinancialYear>
     */
    public function financialYears(): Collection
    {
        if (! $this->company) {
            return new Collection;
        }

        return $this->financialYearOptions ??= $this->company
            ->financialYears()
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->get();
    }

    public function setCompany(Company $company): void
    {
        if (! $company->is_active) {
            throw ValidationException::withMessages([
                'company_id' => 'Select an active company.',
            ]);
        }

        $branch = $this->preferredBranch($company);
        $financialYear = $this->preferredFinancialYear($company);

        session([
            self::COMPANY_ID => $company->id,
            self::BRANCH_ID => $branch?->id,
            self::FINANCIAL_YEAR_ID => $financialYear?->id,
        ]);

        $this->company = $company;
        $this->branch = $branch;
        $this->financialYear = $financialYear;
        $this->branchOptions = null;
        $this->financialYearOptions = null;
    }

    public function setBranch(Branch $branch): void
    {
        $company = $this->company;

        if (! $company || $branch->company_id !== $company->id || ! $branch->is_active) {
            throw ValidationException::withMessages([
                'branch_id' => 'Select an active branch of the current company.',
            ]);
        }

        session([self::BRANCH_ID => $branch->id]);
        $this->branch = $branch;
    }

    public function setFinancialYear(FinancialYear $financialYear): void
    {
        $company = $this->company;

        if (! $company || $financialYear->company_id !== $company->id || ! $financialYear->is_active) {
            throw ValidationException::withMessages([
                'financial_year_id' => 'Select an active financial year of the current company.',
            ]);
        }

        session([self::FINANCIAL_YEAR_ID => $financialYear->id]);
        $this->financialYear = $financialYear;
    }

    public function clear(): void
    {
        session()->forget([self::COMPANY_ID, self::BRANCH_ID, self::FINANCIAL_YEAR_ID]);
        $this->company = null;
        $this->branch = null;
        $this->financialYear = null;
        $this->branchOptions = null;
        $this->financialYearOptions = null;
    }

    public function forgetBranch(Branch $branch): void
    {
        if ((int) session(self::BRANCH_ID) === $branch->id) {
            session()->forget(self::BRANCH_ID);
            $this->branch = null;
        }
    }

    public function forgetFinancialYear(FinancialYear $financialYear): void
    {
        if ((int) session(self::FINANCIAL_YEAR_ID) === $financialYear->id) {
            session()->forget(self::FINANCIAL_YEAR_ID);
            $this->financialYear = null;
        }
    }

    private function findActiveCompany(mixed $id): ?Company
    {
        if (! is_numeric($id)) {
            return null;
        }

        $allowed = $this->allowedCompanyIds();

        return Company::query()
            ->whereKey((int) $id)
            ->where('is_active', true)
            ->when($allowed !== null, fn ($query) => $query->whereIn('id', $allowed === [] ? [0] : $allowed))
            ->first();
    }

    private function resolveBranch(Company $company): ?Branch
    {
        $branchId = session(self::BRANCH_ID);
        $branch = is_numeric($branchId)
            ? $company->branches()->whereKey((int) $branchId)->where('is_active', true)->first()
            : null;

        if (! $branch) {
            $branch = $this->preferredBranch($company);
        }

        $restricted = $this->restrictedBranchIds($company);

        if ($restricted !== null && ($branch === null || ! in_array($branch->id, $restricted, true))) {
            $branch = $company->branches()->whereIn('id', $restricted)->where('is_active', true)->orderBy('name')->first();
        }

        if ($branch) {
            session([self::BRANCH_ID => $branch->id]);
        } else {
            session()->forget(self::BRANCH_ID);
        }

        return $branch;
    }

    private function resolveFinancialYear(Company $company): ?FinancialYear
    {
        $yearId = session(self::FINANCIAL_YEAR_ID);
        $financialYear = is_numeric($yearId)
            ? $company->financialYears()->whereKey((int) $yearId)->where('is_active', true)->first()
            : null;

        if (! $financialYear) {
            $financialYear = $this->preferredFinancialYear($company);
        }

        if ($financialYear) {
            session([self::FINANCIAL_YEAR_ID => $financialYear->id]);
        } else {
            session()->forget(self::FINANCIAL_YEAR_ID);
        }

        return $financialYear;
    }

    private function preferredBranch(Company $company): ?Branch
    {
        $branches = $company->branches()->where('is_active', true)->orderBy('name')->limit(2)->get();

        return $branches->count() === 1 ? $branches->first() : null;
    }

    private function preferredFinancialYear(Company $company): ?FinancialYear
    {
        $years = $company->financialYears()->where('is_active', true)->orderByDesc('start_date')->get();
        $today = now()->startOfDay();

        $covering = $years->first(function (FinancialYear $year) use ($today) {
            return $year->start_date->copy()->startOfDay()->lte($today)
                && $year->end_date->copy()->endOfDay()->gte($today);
        });

        if ($covering) {
            return $covering;
        }

        return $years->count() === 1 ? $years->first() : null;
    }

    private function visibleCompanies(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Company::query()->where('is_active', true);
        $ids = $this->allowedCompanyIds();

        if ($ids !== null) {
            $query->whereIn('id', $ids === [] ? [0] : $ids);
        }

        return $query;
    }

    /**
     * @return list<int>|null
     */
    private function allowedCompanyIds(): ?array
    {
        return \Tally\Authorization\CompanyAccess::ids(auth()->user());
    }

    /**
     * @return list<int>|null
     */
    private function restrictedBranchIds(Company $company): ?array
    {
        $user = auth()->user();

        if (! $user || tally_super_admin($user)) {
            return null;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('acct_user_branches')) {
            return null;
        }

        $ids = \Illuminate\Support\Facades\DB::table('acct_user_branches')
            ->where('user_id', $user->id)
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $ids === [] ? null : $ids;
    }
}
