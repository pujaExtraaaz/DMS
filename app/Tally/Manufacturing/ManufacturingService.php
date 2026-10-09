<?php

namespace Tally\Manufacturing;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Inventory\InventoryAccounts;
use Tally\Inventory\InventoryValuation;
use Tally\Inventory\Quantity;
use Tally\Inventory\StockMovementService;
use Tally\Inventory\StockMovementType;
use Tally\Inventory\UnitConversion;
use Tally\Models\BillOfMaterial;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Godown;
use Tally\Models\Ledger;
use Tally\Models\ManufacturingOrder;
use Tally\Models\Product;
use Tally\Models\StockBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManufacturingService
{
    public function __construct(
        private readonly StockMovementService $stock,
        private readonly VoucherEngine $vouchers,
        private readonly UnitConversion $units,
        private readonly InventoryValuation $valuation,
        private readonly InventoryAccounts $accounts,
    ) {}

    /**
     * Consume components from the source godown and receive finished goods
     * in the destination godown. Stock is written only through StockMovementService.
     *
     * @param  array<string, mixed>  $input
     */
    public function produce(
        Company $company,
        Branch $branch,
        FinancialYear $year,
        User $user,
        array $input,
    ): ManufacturingOrder {
        return DB::transaction(function () use ($company, $branch, $year, $user, $input) {
            $bom = BillOfMaterial::query()
                ->with(['lines.product.primaryUnit', 'byproducts.product', 'finishedProduct.primaryUnit'])
                ->where('company_id', $company->id)
                ->whereKey((int) ($input['bill_of_material_id'] ?? 0))
                ->first();

            if (! $bom || ! $bom->is_active) {
                throw ValidationException::withMessages([
                    'bill_of_material_id' => 'Select an active bill of materials.',
                ]);
            }

            $finished = $bom->finishedProduct;

            if (! $finished || ! $finished->is_active) {
                throw ValidationException::withMessages([
                    'bill_of_material_id' => 'The finished product is inactive.',
                ]);
            }

            $source = $this->godown($company, $input['source_godown_id'] ?? null, 'source_godown_id');
            $destination = $this->godown($company, $input['destination_godown_id'] ?? null, 'destination_godown_id');
            $production = Quantity::scale(trim((string) ($input['quantity'] ?? '')), $finished->primaryUnit->decimal_places);

            if ($production <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Enter a production quantity greater than zero.',
                ]);
            }

            if ($bom->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'bill_of_material_id' => 'This bill of materials has no components.',
                ]);
            }

            $order = ManufacturingOrder::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'financial_year_id' => $year->id,
                'bill_of_material_id' => $bom->id,
                'number' => $this->number($company, $branch, $year),
                'manufactured_on' => $input['manufactured_on'] ?? '',
                'quantity' => Quantity::format($production),
                'source_godown_id' => $source->id,
                'destination_godown_id' => $destination->id,
                'material_cost' => '0.00',
                'narration' => trim((string) ($input['narration'] ?? '')) ?: null,
                'status' => VoucherStatus::Posted,
                'created_by' => $user->id,
            ]);

            $costCents = 0;
            $scrapByLedger = [];
            $batches = is_array($input['batches'] ?? null) ? $input['batches'] : [];

            foreach ($bom->lines as $line) {
                $component = $line->product;

                if (! $component || ! $component->is_active) {
                    throw ValidationException::withMessages([
                        'bill_of_material_id' => 'A component on this bill of materials is inactive.',
                    ]);
                }

                $perUnit = $this->units->toPrimaryScaled($component, (string) $line->quantity, $line->unit_id);
                $needed = $this->units->times($perUnit, $production);
                $wastage = (string) ((float) $bom->wastage_percent + (float) $line->wastage_percent);
                $needed = $this->units->withWastage($needed, $wastage);
                $batch = $this->batchFor($company, $component, $batches[$component->id] ?? $batches[(string) $component->id] ?? null);
                $rate = $this->valuation->averageRate($component);
                $movement = $this->stock->move(
                    $company,
                    $branch,
                    $year,
                    $component,
                    $source,
                    (string) $input['manufactured_on'],
                    -$needed,
                    $rate === '' ? '0' : $rate,
                    StockMovementType::Out,
                    $order,
                    false,
                    $batch,
                );
                $costCents += abs(Money::cents((string) $movement->value));
            }

            foreach ($bom->byproducts as $byproduct) {
                $item = $byproduct->product;

                if (! $item || ! $item->is_active) {
                    throw ValidationException::withMessages([
                        'bill_of_material_id' => 'A by-product on this bill of materials is inactive.',
                    ]);
                }

                $qty = $this->units->times(Quantity::scale((string) $byproduct->quantity, 4), $production);

                if ($qty <= 0) {
                    continue;
                }

                $scrapRate = $this->scrapRate($item, $qty, $costCents, $scrapByLedger);
                $movement = $this->stock->move(
                    $company,
                    $branch,
                    $year,
                    $item,
                    $destination,
                    (string) $input['manufactured_on'],
                    $qty,
                    $scrapRate,
                    StockMovementType::In,
                    $order,
                );
                $scrapCents = abs(Money::cents((string) $movement->value));

                if ($scrapCents > 0) {
                    $ledgerId = $this->accounts->ledgerFor($item)->id;
                    $scrapByLedger[$ledgerId] = ($scrapByLedger[$ledgerId] ?? 0) + $scrapCents;
                }
            }

            $scrapTotal = array_sum($scrapByLedger);
            $fgCents = max(0, $costCents - $scrapTotal);
            $rateCents = $production > 0 ? intdiv($fgCents * 10000, $production) : 0;
            $this->stock->move(
                $company,
                $branch,
                $year,
                $finished,
                $destination,
                (string) $input['manufactured_on'],
                $production,
                Money::format($rateCents),
                StockMovementType::In,
                $order,
            );

            $cost = Money::format($costCents);
            $voucherId = $this->journal($company, $branch, $year, $user, $order, $input, $costCents, $scrapByLedger);
            $order->update([
                'material_cost' => $cost,
                'voucher_id' => $voucherId,
            ]);

            return $order->fresh(['bill.finishedProduct', 'sourceGodown', 'destinationGodown', 'voucher']);
        });
    }

    public function cancel(ManufacturingOrder $order): ManufacturingOrder
    {
        return DB::transaction(function () use ($order) {
            $order = ManufacturingOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === VoucherStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => 'This manufacturing order is already cancelled.',
                ]);
            }

            if ($order->voucher) {
                $this->vouchers->cancel($order->voucher, true);
            }

            $movements = $order->movements()->where('is_reversal', false)->orderByDesc('id')->get();

            foreach ($movements as $movement) {
                $this->stock->reverse($movement);
            }

            $order->update(['status' => VoucherStatus::Cancelled]);

            return $order->fresh(['bill.finishedProduct', 'voucher']);
        });
    }

    /**
     * Scrap keeps a realisable value when the product has a purchase rate,
     * and that value is taken back out of the finished-goods cost.
     *
     * @param  array<int, int>  $already
     */
    private function scrapRate(Product $item, int $scaled, int $costCents, array $already): string
    {
        $rateCents = max(0, Money::cents((string) $item->purchase_rate));

        if ($rateCents === 0 || $scaled <= 0 || $costCents <= 0) {
            return '0.00';
        }

        $used = array_sum($already);
        $room = max(0, $costCents - $used);
        $proposed = Money::cents(Quantity::valueFromScaled($scaled, Money::format($rateCents)));

        if ($proposed > $room) {
            return Money::format(intdiv($room * 10000, $scaled));
        }

        return Money::format($rateCents);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, int>  $scrapByLedger
     */
    private function journal(Company $company, Branch $branch, FinancialYear $year, User $user, ManufacturingOrder $order, array $input, int $costCents, array $scrapByLedger): ?int
    {
        if ($costCents <= 0) {
            return null;
        }

        $finishedLedger = $this->ledger($company, $input['finished_ledger_id'] ?? null) ?? $this->accounts->finished($company);
        $rawLedger = $this->ledger($company, $input['raw_ledger_id'] ?? null) ?? $this->accounts->raw($company);

        if ($finishedLedger->id === $rawLedger->id) {
            $finishedLedger = $this->accounts->finished($company);
            $rawLedger = $this->accounts->raw($company);
        }

        if ($finishedLedger->id === $rawLedger->id) {
            return null;
        }

        $remaining = $costCents;
        $entries = [];

        foreach ($scrapByLedger as $ledgerId => $cents) {
            $take = min($cents, $remaining);
            $remaining -= $take;

            if ($take <= 0) {
                continue;
            }

            $entries[] = ['ledger_id' => (int) $ledgerId, 'debit' => Money::format($take), 'credit' => '0'];
        }

        if ($remaining > 0) {
            array_unshift($entries, ['ledger_id' => $finishedLedger->id, 'debit' => Money::format($remaining), 'credit' => '0']);
        }

        $entries[] = ['ledger_id' => $rawLedger->id, 'debit' => '0', 'credit' => Money::format($costCents)];

        $voucher = $this->vouchers->save($company, $branch, $year, $user, VoucherType::Journal, [
            'voucher_date' => $order->manufactured_on->toDateString(),
            'narration' => 'Manufacturing '.$order->number.($order->narration ? ' — '.$order->narration : ''),
            'entries' => $entries,
        ], true);

        return $voucher->id;
    }

    private function ledger(Company $company, mixed $id): ?Ledger
    {
        if (! $id) {
            return null;
        }

        return Ledger::query()->where('company_id', $company->id)->where('is_active', true)->whereKey((int) $id)->first();
    }

    private function godown(Company $company, mixed $id, string $field): Godown
    {
        $godown = Godown::query()->where('company_id', $company->id)->whereKey((int) $id)->first();

        if (! $godown || ! $godown->is_active) {
            throw ValidationException::withMessages([
                $field => 'Select an active godown from the current company.',
            ]);
        }

        return $godown;
    }

    /**
     * @param  array<string, mixed>|string|null  $given
     */
    private function batchFor(Company $company, Product $product, mixed $given): ?StockBatch
    {
        if (! $product->track_batch) {
            return null;
        }

        $number = is_array($given) ? trim((string) ($given['batch_number'] ?? '')) : trim((string) $given);

        if ($number === '') {
            throw ValidationException::withMessages([
                'batches' => 'Enter a batch for '.$product->name.'.',
            ]);
        }

        return StockBatch::query()->firstOrCreate(
            ['company_id' => $company->id, 'product_id' => $product->id, 'batch_number' => $number],
            [
                'manufactured_on' => is_array($given) ? ($given['manufactured_on'] ?? null) : null,
                'expires_on' => is_array($given) ? ($given['expires_on'] ?? null) : null,
            ],
        );
    }

    private function number(Company $company, Branch $branch, FinancialYear $year): string
    {
        $count = ManufacturingOrder::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->where('financial_year_id', $year->id)
            ->count();

        return 'MFG-'.str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }
}
