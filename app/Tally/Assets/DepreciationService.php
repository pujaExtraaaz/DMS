<?php

namespace Tally\Assets;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Company;
use Tally\Models\DepreciationEntry;
use Tally\Models\FinancialYear;
use Tally\Models\FixedAsset;

class DepreciationService
{
    public function __construct(
        private readonly FixedAssetService $assets,
        private readonly VoucherEngine $vouchers,
    ) {}

    /**
     * Straight-line monthly amount = (gross − residual) / life_months.
     * WDV monthly amount = book_value × annual_rate / 12.
     */
    public function monthlyAmount(FixedAsset $asset): float
    {
        $asset->loadMissing('category');
        $category = $asset->category;
        $method = $category?->depreciation_method ?? 'straight_line';
        $gross = (float) $asset->gross_value;
        $residual = (float) $asset->residual_value;
        $book = $this->assets->bookValue($gross, $asset->accumulated_depreciation);

        if ($book <= $residual + 0.00001 || $gross <= 0) {
            return 0.0;
        }

        if ($method === 'wdv') {
            $rate = $this->wdvAnnualRate($asset);
            $amount = round($book * $rate / 12, 2);

            return min($amount, round($book - $residual, 2));
        }

        $lifeMonths = $category?->lifeMonths() ?? max(1, (int) round(((float) ($category?->useful_life_years ?? 5)) * 12));
        $amount = round(($gross - $residual) / $lifeMonths, 2);

        return min(max(0, $amount), round($book - $residual, 2));
    }

    /**
     * Annual WDV rate derived so book approaches residual over useful life,
     * falling back to category residual_percent when needed.
     */
    public function wdvAnnualRate(FixedAsset $asset): float
    {
        $asset->loadMissing('category');
        $gross = (float) $asset->gross_value;
        $residual = (float) $asset->residual_value;
        $years = max(0.01, (float) ($asset->category?->useful_life_years ?? 5));

        if ($gross > 0 && $residual > 0 && $residual < $gross) {
            return 1 - pow($residual / $gross, 1 / $years);
        }

        $percent = (float) ($asset->category?->residual_percent ?? 5);

        // Approximate declining rate when residual is zero: use residual_percent as annual %.
        return max(0.01, min(0.99, $percent / 100));
    }

    /**
     * Run depreciation for YYYY-MM across active non-CIP assets.
     *
     * @return list<DepreciationEntry>
     */
    public function runForPeriod(Company $company, string $period, ?string $entryDate = null, ?User $user = null): array
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw ValidationException::withMessages([
                'period' => 'Period must be YYYY-MM.',
            ]);
        }

        $date = $entryDate
            ? Carbon::parse($entryDate)->toDateString()
            : Carbon::createFromFormat('Y-m', $period)->endOfMonth()->toDateString();

        $user ??= User::query()->first();

        return DB::transaction(function () use ($company, $period, $date, $user) {
            $created = [];

            $assets = FixedAsset::query()
                ->with('category')
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->where('is_cip', false)
                ->orderBy('asset_code')
                ->get();

            $year = FinancialYear::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->first();

            $branch = $company->branches()->where('is_active', true)->orderBy('name')->first();

            foreach ($assets as $asset) {
                if (DepreciationEntry::query()
                    ->where('fixed_asset_id', $asset->id)
                    ->where('period', $period)
                    ->exists()) {
                    continue;
                }

                $amount = $this->monthlyAmount($asset);
                if ($amount <= 0) {
                    continue;
                }

                $method = $asset->category?->depreciation_method ?? 'straight_line';
                $entry = DepreciationEntry::query()->create([
                    'company_id' => $company->id,
                    'fixed_asset_id' => $asset->id,
                    'period' => $period,
                    'entry_date' => $date,
                    'amount' => number_format($amount, 2, '.', ''),
                    'method' => $method,
                ]);

                $accum = round((float) $asset->accumulated_depreciation + $amount, 2);
                $asset->update([
                    'accumulated_depreciation' => number_format($accum, 2, '.', ''),
                    'book_value' => number_format($this->assets->bookValue($asset->gross_value, $accum), 2, '.', ''),
                ]);

                $category = $asset->category;
                if (
                    $user
                    && $year
                    && $branch
                    && $category?->depr_expense_ledger_id
                    && $category?->accum_depr_ledger_id
                ) {
                    $cents = Money::cents(number_format($amount, 2, '.', ''));
                    $voucher = $this->vouchers->save(
                        $company,
                        $branch,
                        $year,
                        $user,
                        VoucherType::Journal,
                        [
                            'voucher_date' => $date,
                            'narration' => 'Depreciation '.$asset->asset_code.' '.$period,
                            'entries' => [
                                ['ledger_id' => $category->depr_expense_ledger_id, 'debit' => Money::format($cents), 'credit' => '0.00'],
                                ['ledger_id' => $category->accum_depr_ledger_id, 'debit' => '0.00', 'credit' => Money::format($cents)],
                            ],
                        ],
                        true
                    );

                    $entry->update(['voucher_id' => $voucher->id]);
                }

                $created[] = $entry->fresh();
            }

            return $created;
        });
    }
}
