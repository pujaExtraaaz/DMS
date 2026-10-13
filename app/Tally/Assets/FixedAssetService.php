<?php

namespace Tally\Assets;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\FixedAsset;
use Tally\Models\FixedAssetCategory;

class FixedAssetService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Company $company, array $input): FixedAsset
    {
        return DB::transaction(function () use ($company, $input) {
            $category = null;
            if (! empty($input['category_id'])) {
                $category = FixedAssetCategory::query()
                    ->where('company_id', $company->id)
                    ->whereKey((int) $input['category_id'])
                    ->firstOrFail();
            }

            $gross = round((float) ($input['gross_value'] ?? 0), 2);
            if ($gross <= 0) {
                throw ValidationException::withMessages([
                    'gross_value' => 'Gross value must be greater than zero.',
                ]);
            }

            $residual = isset($input['residual_value']) && $input['residual_value'] !== ''
                ? round((float) $input['residual_value'], 2)
                : ($category
                    ? round($gross * ((float) $category->residual_percent / 100), 2)
                    : 0.0);

            if ($residual < 0 || $residual > $gross) {
                throw ValidationException::withMessages([
                    'residual_value' => 'Residual value must be between zero and gross value.',
                ]);
            }

            $accum = round((float) ($input['accumulated_depreciation'] ?? 0), 2);
            $book = $this->bookValue($gross, $accum);

            $code = trim((string) ($input['asset_code'] ?? ''));
            if ($code === '') {
                $seq = FixedAsset::query()->where('company_id', $company->id)->count() + 1;
                $code = 'FA-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            }

            return FixedAsset::query()->create([
                'company_id' => $company->id,
                'category_id' => $category?->id,
                'asset_code' => $code,
                'name' => trim((string) $input['name']),
                'acquisition_date' => (string) ($input['acquisition_date'] ?? now()->toDateString()),
                'capitalization_date' => ($input['capitalization_date'] ?? null) ?: null,
                'gross_value' => number_format($gross, 2, '.', ''),
                'residual_value' => number_format($residual, 2, '.', ''),
                'accumulated_depreciation' => number_format($accum, 2, '.', ''),
                'book_value' => number_format($book, 2, '.', ''),
                'location' => ($input['location'] ?? null) ?: null,
                'department' => ($input['department'] ?? null) ?: null,
                'custodian' => ($input['custodian'] ?? null) ?: null,
                'is_cip' => (bool) ($input['is_cip'] ?? false),
                'status' => ($input['status'] ?? null) ?: 'active',
                'parent_asset_id' => ($input['parent_asset_id'] ?? null) ?: null,
            ])->fresh(['category']);
        });
    }

    /**
     * Capitalize a CIP asset (moves it into the depreciable register).
     */
    public function capitalize(FixedAsset $asset, string $capitalizationDate): FixedAsset
    {
        if (! $asset->is_cip) {
            throw ValidationException::withMessages([
                'is_cip' => 'Only CIP assets can be capitalized.',
            ]);
        }

        if ($asset->isDisposed()) {
            throw ValidationException::withMessages([
                'status' => 'A disposed asset cannot be capitalized.',
            ]);
        }

        $asset->update([
            'is_cip' => false,
            'capitalization_date' => $capitalizationDate,
            'status' => 'active',
        ]);

        return $asset->fresh(['category', 'parent', 'children']);
    }

    public function bookValue(float|string $gross, float|string $accumulated): float
    {
        return max(0, round((float) $gross - (float) $accumulated, 2));
    }

    public function refreshBookValue(FixedAsset $asset): FixedAsset
    {
        $asset->update([
            'book_value' => number_format(
                $this->bookValue($asset->gross_value, $asset->accumulated_depreciation),
                2,
                '.',
                ''
            ),
        ]);

        return $asset->fresh();
    }

    /**
     * @param  array<string, mixed>  $input  Optional proceeds_ledger_id, gain_loss_ledger_id
     */
    public function dispose(
        FixedAsset $asset,
        string $disposalDate,
        float|string $proceeds = 0,
        array $input = [],
        ?User $user = null,
    ): FixedAsset {
        if ($asset->isDisposed()) {
            throw ValidationException::withMessages([
                'status' => 'Asset is already disposed.',
            ]);
        }

        $proceeds = round((float) $proceeds, 2);
        $gross = round((float) $asset->gross_value, 2);
        $accum = round((float) $asset->accumulated_depreciation, 2);
        $book = $this->bookValue($gross, $accum);
        $gainLoss = round($proceeds - $book, 2);

        $asset->update([
            'status' => 'disposed',
            'disposal_date' => $disposalDate,
            'disposal_proceeds' => number_format($proceeds, 2, '.', ''),
            'disposal_gain_loss' => number_format($gainLoss, 2, '.', ''),
            'book_value' => '0.00',
        ]);

        $asset = $asset->fresh(['category', 'company']);
        $this->tryPostDisposalJournal($asset, $disposalDate, $gross, $accum, $proceeds, $gainLoss, $input, $user);

        return $asset->fresh(['category']);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function tryPostDisposalJournal(
        FixedAsset $asset,
        string $disposalDate,
        float $gross,
        float $accum,
        float $proceeds,
        float $gainLoss,
        array $input,
        ?User $user,
    ): void {
        $category = $asset->category;
        $assetLedgerId = $category?->asset_ledger_id;
        $accumLedgerId = $category?->accum_depr_ledger_id;

        if (! $assetLedgerId || ! $accumLedgerId || $gross <= 0) {
            return;
        }

        $proceedsLedgerId = isset($input['proceeds_ledger_id']) && $input['proceeds_ledger_id']
            ? (int) $input['proceeds_ledger_id']
            : null;
        $gainLossLedgerId = isset($input['gain_loss_ledger_id']) && $input['gain_loss_ledger_id']
            ? (int) $input['gain_loss_ledger_id']
            : null;

        if ($proceeds > 0 && ! $proceedsLedgerId) {
            return;
        }

        if ($gainLoss > 0 && ! $gainLossLedgerId) {
            return;
        }

        $lossLedgerId = $gainLossLedgerId ?: $category?->depr_expense_ledger_id;
        if ($gainLoss < 0 && ! $lossLedgerId) {
            return;
        }

        $company = $asset->company;
        $user ??= User::query()->first();
        $year = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where('start_date', '<=', $disposalDate)
            ->where('end_date', '>=', $disposalDate)
            ->first();
        $branch = $company->branches()->where('is_active', true)->orderBy('name')->first();

        if (! $user || ! $year || ! $branch) {
            return;
        }

        $entries = [];

        if ($accum > 0) {
            $entries[] = [
                'ledger_id' => $accumLedgerId,
                'debit' => Money::format(Money::cents(number_format($accum, 2, '.', ''))),
                'credit' => '0.00',
            ];
        }

        if ($proceeds > 0 && $proceedsLedgerId) {
            $entries[] = [
                'ledger_id' => $proceedsLedgerId,
                'debit' => Money::format(Money::cents(number_format($proceeds, 2, '.', ''))),
                'credit' => '0.00',
            ];
        }

        if ($gainLoss < 0 && $lossLedgerId) {
            $entries[] = [
                'ledger_id' => $lossLedgerId,
                'debit' => Money::format(Money::cents(number_format(abs($gainLoss), 2, '.', ''))),
                'credit' => '0.00',
            ];
        }

        if ($gainLoss > 0 && $gainLossLedgerId) {
            $entries[] = [
                'ledger_id' => $gainLossLedgerId,
                'debit' => '0.00',
                'credit' => Money::format(Money::cents(number_format($gainLoss, 2, '.', ''))),
            ];
        }

        $entries[] = [
            'ledger_id' => $assetLedgerId,
            'debit' => '0.00',
            'credit' => Money::format(Money::cents(number_format($gross, 2, '.', ''))),
        ];

        $this->vouchers->save(
            $company,
            $branch,
            $year,
            $user,
            VoucherType::Journal,
            [
                'voucher_date' => $disposalDate,
                'narration' => 'Disposal '.$asset->asset_code,
                'entries' => $entries,
            ],
            true
        );
    }
}
