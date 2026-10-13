<?php

namespace Tally\Purchasing;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Accounting\Money;
use Tally\Accounting\OpeningBalanceType;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Inventory\InventoryAccounts;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\LandedCostVoucher;
use Tally\Models\Ledger;
use Tally\Models\Product;

class LandedCostService
{
    public function __construct(
        private readonly VoucherEngine $vouchers,
        private readonly InventoryAccounts $inventoryAccounts,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, Branch $branch, FinancialYear $year, array $input, ?LandedCostVoucher $voucher = null): LandedCostVoucher
    {
        return DB::transaction(function () use ($company, $branch, $year, $input, $voucher) {
            $freight = (float) ($input['freight'] ?? 0);
            $duty = (float) ($input['duty'] ?? 0);
            $other = (float) ($input['other_charges'] ?? 0);

            $payload = [
                'voucher_date' => (string) ($input['voucher_date'] ?? now()->toDateString()),
                'purchase_invoice_id' => ($input['purchase_invoice_id'] ?? null) ?: null,
                'goods_receipt_id' => ($input['goods_receipt_id'] ?? null) ?: null,
                'freight' => round($freight, 2),
                'duty' => round($duty, 2),
                'other_charges' => round($other, 2),
                'total_charges' => round($freight + $duty + $other, 2),
                'allocation_basis' => ($input['allocation_basis'] ?? null) ?: 'value',
                'status' => ($input['status'] ?? null) ?: ($voucher?->status ?? 'draft'),
                'narration' => ($input['narration'] ?? null) ?: null,
            ];

            if ($voucher) {
                abort_unless($voucher->company_id === $company->id, 404);
                if ($voucher->status !== 'draft') {
                    throw ValidationException::withMessages([
                        'status' => 'Only draft landed cost vouchers can be edited.',
                    ]);
                }
                $voucher->update($payload);

                return $voucher->fresh(['purchaseInvoice', 'goodsReceipt']);
            }

            $count = LandedCostVoucher::query()
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->count() + 1;

            return LandedCostVoucher::query()->create($payload + [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'financial_year_id' => $year->id,
                'number' => 'LC-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT),
            ])->fresh(['purchaseInvoice', 'goodsReceipt']);
        });
    }

    public function post(LandedCostVoucher $lc, User $user): LandedCostVoucher
    {
        return DB::transaction(function () use ($lc, $user) {
            $lc = LandedCostVoucher::query()->whereKey($lc->id)->lockForUpdate()->firstOrFail();
            $lc->loadMissing([
                'company',
                'branch',
                'financialYear',
                'goodsReceipt.lines.product',
                'purchaseInvoice.lines.product',
                'allocations',
            ]);

            if ($lc->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Only draft landed cost vouchers can be posted.',
                ]);
            }

            $totalCharges = Money::cents((string) $lc->total_charges);
            if ($totalCharges <= 0) {
                throw ValidationException::withMessages([
                    'total_charges' => 'Enter freight, duty, or other charges before posting.',
                ]);
            }

            $sourceLines = $this->resolveSourceLines($lc);
            if ($sourceLines === []) {
                throw ValidationException::withMessages([
                    'goods_receipt_id' => 'Link a goods receipt or purchase invoice with lines before posting.',
                ]);
            }

            $allocations = $this->computeAllocations(
                $sourceLines,
                (string) ($lc->allocation_basis ?: 'value'),
                (float) $lc->total_charges,
            );

            $lc->allocations()->delete();
            foreach ($allocations as $row) {
                $lc->allocations()->create([
                    'product_id' => $row['product_id'],
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'base_value' => $row['base_value'],
                    'allocated_charges' => $row['allocated_charges'],
                    'landed_unit_cost' => $row['landed_unit_cost'],
                ]);
            }

            $company = $lc->company;
            $branch = $lc->branch;
            $year = $lc->financialYear;
            $chargesLedger = $this->purchaseChargesLedger($company);

            $entries = [];
            foreach ($allocations as $row) {
                $allocatedCents = Money::cents((string) $row['allocated_charges']);
                if ($allocatedCents <= 0) {
                    continue;
                }

                $inventoryLedger = $this->inventoryLedgerForAllocation($company, $row['product_id'] ?? null);
                $entries[] = [
                    'ledger_id' => $inventoryLedger->id,
                    'debit' => Money::format($allocatedCents),
                    'credit' => '0.00',
                ];
            }

            foreach ([
                'Freight' => (float) $lc->freight,
                'Duty' => (float) $lc->duty,
                'Other charges' => (float) $lc->other_charges,
            ] as $label => $amount) {
                $cents = Money::cents(number_format($amount, 2, '.', ''));
                if ($cents <= 0) {
                    continue;
                }
                $entries[] = [
                    'ledger_id' => $chargesLedger->id,
                    'debit' => '0.00',
                    'credit' => Money::format($cents),
                    'narration' => $label,
                ];
            }

            if ($entries === []) {
                throw ValidationException::withMessages([
                    'total_charges' => 'Nothing to post for this landed cost voucher.',
                ]);
            }

            // StockMovementService stores value as qty × rate, so a quantity=0
            // value-only adjustment is not supported. Inventory cost is increased
            // via the GL debit to stock/raw/FG ledgers above.
            $voucher = $this->vouchers->save(
                $company,
                $branch,
                $year,
                $user,
                VoucherType::Journal,
                [
                    'voucher_date' => $lc->voucher_date->toDateString(),
                    'reference_number' => $lc->number,
                    'narration' => trim('Landed cost '.$lc->number.($lc->narration ? ' — '.$lc->narration : '')),
                    'entries' => $entries,
                ],
                true
            );

            $lc->update([
                'status' => 'posted',
                'voucher_id' => $voucher->id,
                'posted_at' => now(),
            ]);

            return $lc->fresh(['allocations.product', 'voucher', 'purchaseInvoice', 'goodsReceipt']);
        });
    }

    /**
     * @return list<array{product_id:?int, item_name:?string, quantity:float, base_value:float, unit_cost:float}>
     */
    private function resolveSourceLines(LandedCostVoucher $lc): array
    {
        if ($lc->goods_receipt_id) {
            $lc->loadMissing('goodsReceipt.lines');
            $lines = $lc->goodsReceipt?->lines ?? collect();

            return $lines
                ->filter(fn ($line) => (float) $line->received_qty > 0 || (float) $line->line_total > 0)
                ->map(fn ($line) => [
                    'product_id' => $line->product_id ? (int) $line->product_id : null,
                    'item_name' => $line->item_name,
                    'quantity' => (float) $line->received_qty,
                    'base_value' => (float) $line->line_total,
                    'unit_cost' => (float) $line->rate,
                ])
                ->values()
                ->all();
        }

        if ($lc->purchase_invoice_id) {
            $lc->loadMissing('purchaseInvoice.lines');
            $lines = $lc->purchaseInvoice?->lines ?? collect();

            return $lines
                ->filter(fn ($line) => (float) $line->quantity > 0 || (float) $line->line_total > 0)
                ->map(fn ($line) => [
                    'product_id' => $line->product_id ? (int) $line->product_id : null,
                    'item_name' => $line->item_name,
                    'quantity' => (float) $line->quantity,
                    'base_value' => (float) ($line->taxable_amount ?: $line->line_total),
                    'unit_cost' => (float) $line->rate,
                ])
                ->values()
                ->all();
        }

        return [];
    }

    /**
     * Allocate total charges across source lines by value or quantity (Domains LandedCostService pattern).
     *
     * @param  list<array{product_id:?int, item_name:?string, quantity:float, base_value:float, unit_cost:float}>  $lines
     * @return list<array{product_id:?int, item_name:?string, quantity:float, base_value:float, allocated_charges:float, landed_unit_cost:float}>
     */
    public function computeAllocations(array $lines, string $basis, float $additional): array
    {
        $count = count($lines);
        if ($count === 0 || $additional <= 0) {
            return [];
        }

        $basisKey = $basis === 'qty' ? 'quantity' : 'base_value';
        $totalBasis = array_sum(array_map(fn ($line) => (float) ($line[$basisKey] ?? 0), $lines));

        if ($totalBasis <= 0) {
            $share = round($additional / $count, 2);
            $allocatedSum = 0.0;
            $result = [];
            foreach ($lines as $index => $line) {
                $allocated = $index === $count - 1
                    ? round($additional - $allocatedSum, 2)
                    : $share;
                $allocatedSum += $allocated;
                $qty = max(0.0001, (float) $line['quantity']);
                $result[] = [
                    'product_id' => $line['product_id'],
                    'item_name' => $line['item_name'],
                    'quantity' => round((float) $line['quantity'], 4),
                    'base_value' => round((float) $line['base_value'], 2),
                    'allocated_charges' => $allocated,
                    'landed_unit_cost' => round(((float) $line['unit_cost']) + ($allocated / $qty), 4),
                ];
            }

            return $result;
        }

        $allocatedSum = 0.0;
        $result = [];
        foreach ($lines as $index => $line) {
            $lineBasis = (float) ($line[$basisKey] ?? 0);
            $share = $index === $count - 1
                ? round($additional - $allocatedSum, 2)
                : round($additional * ($lineBasis / $totalBasis), 2);
            $allocatedSum += $share;
            $qty = max(0.0001, (float) $line['quantity']);

            $result[] = [
                'product_id' => $line['product_id'],
                'item_name' => $line['item_name'],
                'quantity' => round((float) $line['quantity'], 4),
                'base_value' => round((float) $line['base_value'], 2),
                'allocated_charges' => $share,
                'landed_unit_cost' => round(((float) $line['unit_cost']) + ($share / $qty), 4),
            ];
        }

        return $result;
    }

    private function inventoryLedgerForAllocation(Company $company, ?int $productId): Ledger
    {
        if ($productId) {
            $product = Product::query()
                ->where('company_id', $company->id)
                ->whereKey($productId)
                ->first();
            if ($product) {
                return $this->inventoryAccounts->ledgerFor($product);
            }
        }

        return $this->inventoryAccounts->stock($company);
    }

    private function purchaseChargesLedger(Company $company): Ledger
    {
        $existing = $company->ledgers()
            ->where(function ($q) {
                $q->where('code', 'PURCHASE_CHARGES')
                    ->orWhere('name', 'Purchase charges');
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        $group = $company->accountGroups()->where('code', 'DIRECT_EXPENSES')->first();
        if (! $group) {
            // Fallback credit side when DIRECT_EXPENSES is missing.
            return $this->inventoryAccounts->adjustment($company);
        }

        return $company->ledgers()->create([
            'account_group_id' => $group->id,
            'name' => 'Purchase charges',
            'code' => 'PURCHASE_CHARGES',
            'opening_balance' => '0.00',
            'opening_balance_type' => OpeningBalanceType::Debit,
            'is_active' => true,
            'is_system' => true,
        ]);
    }
}
