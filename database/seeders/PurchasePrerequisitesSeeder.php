<?php

namespace Database\Seeders;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\DocumentNumberSeries;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures Setup prerequisites required for Purchase Orders / Invoices.
 * Idempotent — safe to re-run.
 */
class PurchasePrerequisitesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('id')->first()
            ?? Company::query()->orderBy('id')->first();

        if (! $company) {
            $company = Company::create([
                'name' => 'Default Company',
                'code' => 'MAIN',
                'legal_name' => 'Default Company',
                'is_active' => true,
                'is_default' => true,
            ]);
            $this->command?->info("Created company #{$company->id}");
        } elseif (Schema::hasColumn('companies', 'is_default') && ! Company::query()->where('is_default', true)->exists()) {
            $company->update(['is_default' => true]);
        }

        $branch = Branch::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('id')->first()
            ?? Branch::withTrashed()->where('company_id', $company->id)->orderBy('id')->first();

        if ($branch?->trashed()) {
            $branch->restore();
            $branch->update(['is_active' => true]);
        }

        if (! $branch) {
            $branch = Branch::create([
                'company_id' => $company->id,
                'name' => 'Head Office',
                'code' => 'HO',
                'is_active' => true,
            ]);
            $this->command?->info("Created branch #{$branch->id}");
        }

        $warehouse = Warehouse::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first()
            ?? Warehouse::withTrashed()->where('company_id', $company->id)->orderBy('id')->first();

        if ($warehouse?->trashed()) {
            $warehouse->restore();
            $warehouse->update(['is_active' => true, 'is_default' => true]);
        }

        if (! $warehouse) {
            $warehouse = Warehouse::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => 'Main Warehouse',
                'code' => 'WH1',
                'is_default' => true,
                'is_active' => true,
            ]);
            $this->command?->info("Created warehouse #{$warehouse->id}");
        } elseif (! $warehouse->is_default) {
            Warehouse::query()->where('company_id', $company->id)->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
            $warehouse->update(['is_default' => true, 'branch_id' => $warehouse->branch_id ?: $branch->id]);
        }

        $today = Carbon::today();
        $fy = FinancialYear::query()
            ->where('company_id', $company->id)
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->orderByDesc('is_current')
            ->first();

        if (! $fy) {
            $fyStart = $today->month >= 4
                ? $today->copy()->month(4)->startOfMonth()
                : $today->copy()->subYear()->month(4)->startOfMonth();
            $fyEnd = $fyStart->copy()->addYear()->subDay();

            FinancialYear::query()->where('company_id', $company->id)->update(['is_current' => false]);

            $fy = FinancialYear::create([
                'company_id' => $company->id,
                'name' => $fyStart->format('Y').'-'.$fyEnd->format('y'),
                'starts_on' => $fyStart->toDateString(),
                'ends_on' => $fyEnd->toDateString(),
                'is_closed' => false,
                'is_current' => true,
            ]);
            $this->command?->info("Created financial year {$fy->name}");
        } elseif (! $fy->is_current || $fy->is_closed) {
            FinancialYear::query()->where('company_id', $company->id)->update(['is_current' => false]);
            $fy->update(['is_current' => true, 'is_closed' => false]);
        }

        foreach ([
            'PO' => 'Purchase Order',
            'PI' => 'Purchase Invoice',
            'GRN' => 'Goods Receipt',
            'QTN' => 'Sales Quotation',
        ] as $type => $label) {
            $series = DocumentNumberSeries::query()
                ->where('document_type', $type)
                ->where(function ($q) use ($company) {
                    $q->whereNull('company_id')->orWhere('company_id', $company->id);
                })
                ->orderByRaw('company_id is null')
                ->first();

            if (! $series) {
                DocumentNumberSeries::create([
                    'company_id' => null,
                    'document_type' => $type,
                    'name' => $label,
                    'prefix' => 'AVIT'.$type,
                    'suffix' => null,
                    'include_financial_year' => true,
                    'financial_year_style' => 'yy_yy',
                    'separator' => '-',
                    'padding' => 3,
                    'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
                    'restart_each_fy' => true,
                    'is_active' => true,
                ]);
                $this->command?->info("Created {$type} document number series");
            } elseif (! $series->is_active) {
                $series->update(['is_active' => true]);
            }
        }

        $supplierExists = Customer::query()
            ->whereIn('party_type', [
                Customer::PARTY_TYPE_SUNDRY_CREDITORS,
                'supplier',
                Customer::PARTY_TYPE_BOTH,
            ])
            ->where('is_active', true)
            ->exists();

        if (! $supplierExists) {
            Customer::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => 'Default Supplier',
                'code' => 'SUP-001',
                'party_type' => Customer::PARTY_TYPE_SUNDRY_CREDITORS,
                'is_active' => true,
            ]);
            $this->command?->info('Created default sundry creditor supplier');
        }

        if (class_exists(\App\Domains\Catalog\Models\Brand::class)
            && class_exists(\App\Domains\Master\Models\Product::class)
            && Schema::hasTable('brands')
            && Schema::hasTable('products')) {
            $generalBrand = \App\Domains\Catalog\Models\Brand::query()
                ->whereRaw('LOWER(name) = ?', ['general'])
                ->first();

            if (! $generalBrand) {
                $generalBrand = \App\Domains\Catalog\Models\Brand::create([
                    'company_id' => $company->id,
                    'name' => 'General',
                    'is_active' => true,
                ]);
                $this->command?->info("Created General brand #{$generalBrand->id}");
            }

            $backfilled = \App\Domains\Master\Models\Product::withTrashed()
                ->whereNull('brand_id')
                ->update(['brand_id' => $generalBrand->id]);

            if ($backfilled > 0) {
                $this->command?->info("Backfilled brand_id on {$backfilled} products");
            }
        }

        $this->call(TaxRateSeeder::class);

        $this->command?->info('Purchase prerequisites ready for company #'.$company->id);
    }
}
