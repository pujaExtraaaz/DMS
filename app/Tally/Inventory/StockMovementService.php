<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Godown;
use Tally\Models\Product;
use Tally\Models\StockBatch;
use Tally\Models\StockMovement;
use Tally\Models\StockSerial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * The only writer of stock movements. Quantity on hand is the signed sum of
 * movements, plus the product opening quantity when no godown is requested.
 * Opening quantity is not placed in a godown. Batch quantity is the same sum
 * filtered by batch. Value is quantity times rate, stored on the movement.
 */
class StockMovementService
{
    public function move(
        Company $company,
        Branch $branch,
        FinancialYear $financialYear,
        Product $product,
        Godown $godown,
        string $date,
        int $signedQuantity,
        string $rate,
        StockMovementType $type,
        Model $reference,
        bool $reversal = false,
        ?StockBatch $batch = null,
        ?StockSerial $serial = null,
    ): StockMovement {
        $this->assertContext($company, $branch, $financialYear, $product, $godown);
        $date = $this->date($financialYear, $date);
        $this->assertSign($type, $signedQuantity);
        $this->assertTracking($product, $signedQuantity, $batch, $serial, $company, $reversal);

        $rateCents = Money::cents($rate);

        if ($rateCents < 0) {
            throw ValidationException::withMessages([
                'rate' => 'Enter a rate that is zero or more.',
            ]);
        }

        Product::query()->whereKey($product->id)->lockForUpdate()->first();
        $onHand = $this->scaledOnHand($company->id, $product->id, $godown->id, null, null, true);

        if (! $company->allow_negative_stock && $onHand + $signedQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Not enough stock of '.$product->name.' in '.$godown->name.'.',
            ]);
        }

        if ($batch && ! $company->allow_negative_stock) {
            $batchOnHand = $this->scaledOnHand($company->id, $product->id, $godown->id, $batch->id, null, true);

            if ($batchOnHand + $signedQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough of batch '.$batch->batch_number.' for '.$product->name.' in '.$godown->name.'.',
                ]);
            }
        }

        if ($serial) {
            $inGodown = $this->scaledOnHand($company->id, $product->id, $godown->id, null, $serial->id, true);
            $inCompany = $this->scaledOnHand($company->id, $product->id, null, null, $serial->id, true);

            if ($signedQuantity < 0 && $inGodown + $signedQuantity < 0) {
                throw ValidationException::withMessages([
                    'serial_number' => 'Serial '.$serial->serial_number.' is not in '.$godown->name.'.',
                ]);
            }

            if ($signedQuantity > 0 && $inCompany + $signedQuantity > 10000) {
                throw ValidationException::withMessages([
                    'serial_number' => 'Serial '.$serial->serial_number.' is already in stock.',
                ]);
            }
        }

        $movement = StockMovement::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'financial_year_id' => $financialYear->id,
            'product_id' => $product->id,
            'godown_id' => $godown->id,
            'batch_id' => $batch?->id,
            'serial_id' => $serial?->id,
            'quantity' => Quantity::format($signedQuantity),
            'rate' => Money::format($rateCents),
            'value' => Quantity::valueFromScaled($signedQuantity, Money::format($rateCents)),
            'movement_type' => $type,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'movement_date' => $date,
            'is_reversal' => $reversal,
        ]);

        if ($serial) {
            $serial->update(['status' => $signedQuantity < 0 ? 'issued' : 'available']);
        }

        return $movement;
    }

    public function reverse(StockMovement $movement): StockMovement
    {
        $movement->loadMissing(['company', 'branch', 'financialYear', 'product', 'godown', 'reference', 'batch', 'serial']);
        $scaled = -Quantity::signedScale((string) $movement->quantity, 4);

        return $this->move(
            $movement->company,
            $movement->branch,
            $movement->financialYear,
            $movement->product,
            $movement->godown,
            $movement->movement_date->toDateString(),
            $scaled,
            (string) $movement->rate,
            $movement->movement_type->opposite(),
            $movement->reference,
            true,
            $movement->batch,
            $movement->serial,
        );
    }

    /**
     * Godown quantity is movements only. Company quantity also includes opening stock.
     */
    public function quantity(Product $product, ?Godown $godown = null): string
    {
        $scaled = $this->scaledOnHand($product->company_id, $product->id, $godown?->id);

        if ($godown === null) {
            $scaled += Quantity::scale((string) $product->opening_quantity, 4);
        }

        return Quantity::format($scaled);
    }

    public function batchQuantity(Product $product, StockBatch $batch, ?Godown $godown = null): string
    {
        return Quantity::format($this->scaledOnHand($product->company_id, $product->id, $godown?->id, $batch->id));
    }

    /**
     * @return array{quantity: array<int, int>, value: array<int, int>}
     */
    public function totalsForCompany(int $companyId, ?int $financialYearId = null): array
    {
        $quantity = [];
        $value = [];

        StockMovement::query()
            ->where('company_id', $companyId)
            ->select(['id', 'product_id', 'financial_year_id', 'quantity', 'value'])
            ->chunkById(500, function ($rows) use (&$quantity, &$value, $financialYearId): void {
                foreach ($rows as $row) {
                    $quantity[$row->product_id] = ($quantity[$row->product_id] ?? 0) + Quantity::signedScale((string) $row->quantity, 4);

                    if ($financialYearId === null || (int) $row->financial_year_id === $financialYearId) {
                        $value[$row->product_id] = ($value[$row->product_id] ?? 0) + Money::cents((string) $row->value);
                    }
                }
            });

        return ['quantity' => $quantity, 'value' => $value];
    }

    /**
     * @return array<int, int>
     */
    public function batchTotals(int $companyId): array
    {
        $totals = [];

        StockMovement::query()
            ->where('company_id', $companyId)
            ->whereNotNull('batch_id')
            ->select(['id', 'batch_id', 'quantity'])
            ->chunkById(500, function ($rows) use (&$totals): void {
                foreach ($rows as $row) {
                    $totals[$row->batch_id] = ($totals[$row->batch_id] ?? 0) + Quantity::signedScale((string) $row->quantity, 4);
                }
            });

        return $totals;
    }

    private function scaledOnHand(int $companyId, int $productId, ?int $godownId, ?int $batchId = null, ?int $serialId = null, bool $lock = false): int
    {
        $query = StockMovement::query()
            ->where('company_id', $companyId)
            ->where('product_id', $productId);

        if ($godownId !== null) {
            $query->where('godown_id', $godownId);
        }

        if ($batchId !== null) {
            $query->where('batch_id', $batchId);
        }

        if ($serialId !== null) {
            $query->where('serial_id', $serialId);
        }

        $total = 0;

        if ($lock) {
            foreach ($query->lockForUpdate()->pluck('quantity') as $quantity) {
                $total += Quantity::signedScale((string) $quantity, 4);
            }

            return $total;
        }

        $query->select(['id', 'quantity'])->chunkById(500, function ($rows) use (&$total): void {
            foreach ($rows as $row) {
                $total += Quantity::signedScale((string) $row->quantity, 4);
            }
        });

        return $total;
    }

    private function assertSign(StockMovementType $type, int $signedQuantity): void
    {
        if ($signedQuantity === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Enter a quantity other than zero.',
            ]);
        }

        $positive = match ($type) {
            StockMovementType::In, StockMovementType::TransferIn => true,
            StockMovementType::Out, StockMovementType::TransferOut => false,
            StockMovementType::Adjustment => null,
        };

        if ($positive === true && $signedQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock in quantity must be greater than zero.',
            ]);
        }

        if ($positive === false && $signedQuantity > 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock out quantity must be greater than zero.',
            ]);
        }
    }

    private function assertContext(
        Company $company,
        Branch $branch,
        FinancialYear $financialYear,
        Product $product,
        Godown $godown,
    ): void {
        if (! $company->is_active || $branch->company_id !== $company->id || ! $branch->is_active) {
            throw ValidationException::withMessages([
                'branch_id' => 'Select an active branch of the current company.',
            ]);
        }

        if ($financialYear->company_id !== $company->id || ! $financialYear->is_active) {
            throw ValidationException::withMessages([
                'financial_year_id' => 'Select an active financial year of the current company.',
            ]);
        }

        if ($product->company_id !== $company->id || ! $product->is_active) {
            throw ValidationException::withMessages([
                'product_id' => 'Select an active product from the current company.',
            ]);
        }

        if ($godown->company_id !== $company->id || ! $godown->is_active) {
            throw ValidationException::withMessages([
                'godown_id' => 'Select an active godown from the current company.',
            ]);
        }
    }

    private function assertTracking(Product $product, int $signedQuantity, ?StockBatch $batch, ?StockSerial $serial, Company $company, bool $reversal): void
    {
        if ($product->track_batch && ! $batch) {
            throw ValidationException::withMessages([
                'batch_number' => 'Enter a batch for '.$product->name.'.',
            ]);
        }

        if ($batch && ($batch->company_id !== $company->id || $batch->product_id !== $product->id)) {
            throw ValidationException::withMessages([
                'batch_number' => 'That batch does not belong to '.$product->name.'.',
            ]);
        }

        if ($product->track_serial && ! $serial) {
            throw ValidationException::withMessages([
                'serial_number' => 'Enter a serial number for '.$product->name.'.',
            ]);
        }

        if (! $serial) {
            return;
        }

        if ($serial->company_id !== $company->id || $serial->product_id !== $product->id) {
            throw ValidationException::withMessages([
                'serial_number' => 'That serial number does not belong to '.$product->name.'.',
            ]);
        }

        if (abs($signedQuantity) !== 10000) {
            throw ValidationException::withMessages([
                'quantity' => 'A serialised item moves one at a time.',
            ]);
        }

        if (! $reversal && $signedQuantity < 0 && ! $serial->isAvailable()) {
            throw ValidationException::withMessages([
                'serial_number' => 'Serial '.$serial->serial_number.' is already issued.',
            ]);
        }
    }

    private function date(FinancialYear $financialYear, string $value): string
    {
        if ($value === '' || ! strtotime($value)) {
            throw ValidationException::withMessages([
                'transaction_date' => 'Enter a date.',
            ]);
        }

        $date = date('Y-m-d', strtotime($value));
        $start = $financialYear->start_date->toDateString();
        $end = $financialYear->end_date->toDateString();

        if ($date < $start || $date > $end) {
            throw ValidationException::withMessages([
                'transaction_date' => 'The date must fall in '.$financialYear->name.' ('.$financialYear->rangeLabel().').',
            ]);
        }

        return $date;
    }
}
