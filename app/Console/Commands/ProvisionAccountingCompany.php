<?php

namespace App\Console\Commands;

use App\Domains\Organization\Models\Branch as OrganizationBranch;
use App\Domains\Organization\Models\Company as OrganizationCompany;
use App\Domains\Organization\Models\FinancialYear as OrganizationYear;
use App\Domains\Sync\Support\BooksCompany;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;

class ProvisionAccountingCompany extends Command
{
    protected $signature = 'accounting:provision-company {company? : DMS company id}';

    protected $description = 'Create books company, branch, year, and chart for a DMS company without touching operational tables';

    public function handle(): int
    {
        $ids = $this->argument('company')
            ? [(int) $this->argument('company')]
            : OrganizationCompany::query()->orderBy('id')->pluck('id')->all();

        foreach ($ids as $id) {
            $organization = OrganizationCompany::query()->find($id);
            if (! $organization) {
                $this->error("DMS company {$id} was not found.");

                return self::FAILURE;
            }

            if (BooksCompany::forOrganization($organization->id)) {
                $this->line("Company {$organization->id} already has books.");

                continue;
            }

            $this->provision($organization);
            $this->info("Provisioned books for {$organization->name}.");
        }

        return self::SUCCESS;
    }

    private function provision(OrganizationCompany $organization): void
    {
        DB::transaction(function () use ($organization) {
            [$start, $end, $years] = $this->years($organization);
            $gstin = $organization->gstin && ! Company::query()->where('gstin', $organization->gstin)->exists()
                ? $organization->gstin
                : null;

            $books = Company::provision([
                'name' => $organization->name,
                'legal_name' => $organization->legal_name ?: $organization->name,
                'address' => $organization->address,
                'state' => $organization->state,
                'country' => 'India',
                'pincode' => $organization->pincode,
                'phone' => $organization->phone,
                'email' => $organization->email,
                'gstin' => $gstin,
                'pan' => $organization->pan,
                'financial_year_start' => $start->toDateString(),
                'financial_year_end' => $end->toDateString(),
                'is_active' => (bool) $organization->is_active,
            ]);

            $primaryYear = $books->financialYears()->first();
            $yearMap = [];
            foreach ($years as $index => $year) {
                if ($index === 0) {
                    $yearMap[(string) $year->id] = $primaryYear->id;

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

            // Company::provision already creates a default Head Office (HO) branch.
            $defaultBranch = $books->branches()->orderBy('id')->first();
            $branches = OrganizationBranch::query()->where('company_id', $organization->id)->get();
            $branchMap = [];
            $acctBranch = $defaultBranch;

            if ($branches->isEmpty()) {
                $acctBranch = $defaultBranch;
            } else {
                foreach ($branches as $branch) {
                    $code = $branch->code ?: ('B'.$branch->id);
                    $existing = $books->branches()
                        ->where(function ($q) use ($code, $branch) {
                            $q->where('code', $code)->orWhere('name', $branch->name);
                        })
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'name' => $branch->name ?: $existing->name,
                            'address' => $branch->address ?: $existing->address,
                            'state' => $branch->state ?: $existing->state,
                            'pincode' => $branch->pincode ?: $existing->pincode,
                            'phone' => $branch->phone ?: $existing->phone,
                            'is_active' => (bool) $branch->is_active,
                        ]);
                        $created = $existing;
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
            }

            $current = $years->firstWhere('is_current', true) ?? $years->first();
            $linkedYear = $current ? FinancialYear::query()->find($yearMap[(string) $current->id]) : $primaryYear;

            BooksCompany::rememberMaps($organization, $books, $acctBranch, $linkedYear, $branchMap, $yearMap);
            BooksCompany::salesLedger($books);
            BooksCompany::cashLedger($books);
            BooksCompany::generalGroup($books);
        });
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: \Illuminate\Support\Collection<int, OrganizationYear>}
     */
    private function years(OrganizationCompany $organization): array
    {
        $years = OrganizationYear::query()->where('company_id', $organization->id)->orderBy('starts_on')->get();
        if ($years->isEmpty()) {
            $start = now()->month >= 4
                ? now()->startOfYear()->month(4)->startOfMonth()
                : now()->subYear()->startOfYear()->month(4)->startOfMonth();

            return [$start, $start->copy()->addYear()->subDay(), $years];
        }

        return [$years->first()->starts_on, $years->first()->ends_on, $years];
    }
}
