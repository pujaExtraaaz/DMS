<?php

namespace Tally\Inventory;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Godown;
use Tally\Models\Product;
use Tally\Models\StockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockTransactionService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly InventoryPosting $inventory,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(
        Company $company,
        Branch $branch,
        FinancialYear $financialYear,
        User $user,
        StockTransactionType $type,
        array $input,
    ): StockTransaction {
        return DB::transaction(function () use ($company, $branch, $financialYear, $user, $type, $input) {
            $date = $this->date($financialYear, (string) ($input['transaction_date'] ?? ''));
            $prepared = $this->lines($company, $type, $input);
            $source = $prepared['source'];
            $destination = $prepared['destination'];

            $transaction = StockTransaction::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'financial_year_id' => $financialYear->id,
                'type' => $type,
                'number' => $this->allocate($company, $branch, $financialYear, $type),
                'transaction_date' => $date,
                'source_godown_id' => $source?->id,
                'destination_godown_id' => $destination?->id,
                'narration' => $this->blank($input['narration'] ?? null),
                'status' => VoucherStatus::Posted,
                'created_by' => $user->id,
            ]);

            $transaction->lines()->createMany($prepared['lines']);
            $transaction->load(['lines.product', 'lines.godown']);

            foreach ($transaction->lines as $line) {
                $this->postLine($transaction, $line->product, $line->godown, $source, $destination, $line->quantity, $line->rate, $prepared['signs'][$line->line_number], $line);
            }

            $this->inventory->syncStockTransaction($transaction->fresh(['company', 'branch', 'financialYear']), $user);

            return $transaction->fresh(['lines.product', 'lines.godown', 'sourceGodown', 'destinationGodown', 'creator']);
        });
    }

    public function cancel(StockTransaction $transaction): StockTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = StockTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($transaction->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'This stock transaction is already cancelled.',
                ]);
            }

            $this->inventory->cancelStockTransaction($transaction);

            $movements = $transaction->movements()->where('is_reversal', false)->orderBy('id')->get();

            foreach ($movements as $movement) {
                $this->movements->reverse($movement);
            }

            $transaction->update([
                'status' => VoucherStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $transaction->fresh(['lines.product', 'lines.godown', 'sourceGodown', 'destinationGodown']);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{lines: list<array<string, mixed>>, signs: array<int, int>, source: ?Godown, destination: ?Godown}
     */
    private function lines(Company $company, StockTransactionType $type, array $input): array
    {
        $source = null;
        $destination = null;

        if ($type === StockTransactionType::Transfer) {
            $source = $this->godown($company, $input['source_godown_id'] ?? null, 'source_godown_id');
            $destination = $this->godown($company, $input['destination_godown_id'] ?? null, 'destination_godown_id');

            if ($source->id === $destination->id) {
                throw ValidationException::withMessages([
                    'destination_godown_id' => 'Choose a different destination godown.',
                ]);
            }
        }

        $kept = [];
        $signs = [];
        $errors = [];

        foreach (array_values($input['lines'] ?? []) as $index => $line) {
            if (! is_array($line) || $this->blankLine($line)) {
                continue;
            }

            $product = Product::query()
                ->with('primaryUnit')
                ->where('company_id', $company->id)
                ->whereKey((int) ($line['product_id'] ?? 0))
                ->first();

            if (! $product || ! $product->is_active) {
                $errors["lines.$index.product_id"] = 'Select an active product from the current company.';

                continue;
            }

            $godown = null;

            if ($type !== StockTransactionType::Transfer) {
                try {
                    $godown = $this->godown($company, $line['godown_id'] ?? null, "lines.$index.godown_id");
                } catch (ValidationException $exception) {
                    $errors["lines.$index.godown_id"] = $exception->errors()["lines.$index.godown_id"][0] ?? 'Select an active godown from the current company.';

                    continue;
                }
            }

            try {
                $scaled = Quantity::scale(trim((string) ($line['quantity'] ?? '')), $product->primaryUnit->decimal_places);
            } catch (InvalidArgumentException $exception) {
                $errors["lines.$index.quantity"] = $exception->getMessage();

                continue;
            }

            if ($scaled <= 0) {
                $errors["lines.$index.quantity"] = 'Enter a quantity greater than zero.';

                continue;
            }

            try {
                $rate = Money::cents(trim((string) ($line['rate'] ?? '0')) === '' ? '0' : trim((string) $line['rate']));
            } catch (InvalidArgumentException) {
                $errors["lines.$index.rate"] = 'Enter a rate with up to 2 decimal places.';

                continue;
            }

            if ($rate < 0) {
                $errors["lines.$index.rate"] = 'Enter a rate that is zero or more.';

                continue;
            }

            $sign = 1;

            if ($type === StockTransactionType::Out) {
                $sign = -1;
            }

            if ($type === StockTransactionType::Adjustment) {
                $sign = ($line['direction'] ?? 'increase') === 'decrease' ? -1 : 1;
            }

            $number = count($kept) + 1;
            $signs[$number] = $sign;
            $rateText = Money::format($rate);

            $kept[] = [
                'line_number' => $number,
                'product_id' => $product->id,
                'godown_id' => $godown?->id,
                'quantity' => Quantity::format($scaled),
                'rate' => $rateText,
                'value' => Quantity::valueFromScaled($scaled, $rateText),
                'batch_number' => $this->blank($line['batch_number'] ?? null),
                'manufactured_on' => $this->blank($line['manufactured_on'] ?? null),
                'expires_on' => $this->blank($line['expires_on'] ?? null),
                'serial_number' => $this->blank($line['serial_number'] ?? null),
            ];
        }

        if ($kept === []) {
            $errors['lines'] = 'Add at least one product.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'lines' => $kept,
            'signs' => $signs,
            'source' => $source,
            'destination' => $destination,
        ];
    }

    private function postLine(
        StockTransaction $transaction,
        Product $product,
        ?Godown $lineGodown,
        ?Godown $source,
        ?Godown $destination,
        string $quantity,
        string $rate,
        int $sign,
        ?\Tally\Models\StockTransactionLine $line = null,
    ): void {
        $scaled = Quantity::signedScale((string) $quantity, 4) * $sign;
        $transaction->loadMissing(['company', 'branch', 'financialYear']);
        [$batch, $serial] = $this->tracking($transaction, $product, $line, $sign);

        if ($transaction->type === StockTransactionType::Transfer) {
            $this->movements->move(
                $transaction->company,
                $transaction->branch,
                $transaction->financialYear,
                $product,
                $source,
                $transaction->transaction_date->toDateString(),
                -abs($scaled),
                (string) $rate,
                StockMovementType::TransferOut,
                $transaction,
                false,
                $batch,
                $serial,
            );
            $this->movements->move(
                $transaction->company,
                $transaction->branch,
                $transaction->financialYear,
                $product,
                $destination,
                $transaction->transaction_date->toDateString(),
                abs($scaled),
                (string) $rate,
                StockMovementType::TransferIn,
                $transaction,
                false,
                $batch,
                $serial,
            );

            return;
        }

        $type = match ($transaction->type) {
            StockTransactionType::In => StockMovementType::In,
            StockTransactionType::Out => StockMovementType::Out,
            StockTransactionType::Adjustment => StockMovementType::Adjustment,
            StockTransactionType::Transfer => StockMovementType::TransferOut,
        };

        $this->movements->move(
            $transaction->company,
            $transaction->branch,
            $transaction->financialYear,
            $product,
            $lineGodown,
            $transaction->transaction_date->toDateString(),
            $scaled,
            (string) $rate,
            $type,
            $transaction,
            false,
            $batch,
            $serial,
        );
    }

    /**
     * @return array{0: ?\Tally\Models\StockBatch, 1: ?\Tally\Models\StockSerial}
     */
    private function tracking(StockTransaction $transaction, Product $product, ?\Tally\Models\StockTransactionLine $line, int $sign): array
    {
        $batch = null;
        $serial = null;
        $number = trim((string) ($line?->batch_number ?? ''));

        if ($number !== '') {
            $batch = \Tally\Models\StockBatch::query()->firstOrCreate(
                ['company_id' => $transaction->company_id, 'product_id' => $product->id, 'batch_number' => $number],
                [
                    'manufactured_on' => $line->manufactured_on,
                    'expires_on' => $line->expires_on,
                ],
            );
        }

        $serialNumber = trim((string) ($line?->serial_number ?? ''));

        if ($serialNumber !== '') {
            $serial = \Tally\Models\StockSerial::query()->firstOrCreate(
                ['company_id' => $transaction->company_id, 'serial_number' => $serialNumber],
                ['product_id' => $product->id, 'status' => 'available'],
            );
        }

        return [$batch, $serial];
    }

    private function godown(Company $company, mixed $id, string $field): Godown
    {
        $godown = Godown::query()
            ->where('company_id', $company->id)
            ->whereKey((int) $id)
            ->first();

        if (! $godown || ! $godown->is_active) {
            throw ValidationException::withMessages([
                $field => 'Select an active godown from the current company.',
            ]);
        }

        return $godown;
    }

    private function allocate(Company $company, Branch $branch, FinancialYear $financialYear, StockTransactionType $type): string
    {
        $count = StockTransaction::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->where('financial_year_id', $financialYear->id)
            ->where('type', $type)
            ->lockForUpdate()
            ->count();

        return sprintf('%s-%06d', $type->prefix(), $count + 1);
    }

    private function date(FinancialYear $financialYear, string $value): string
    {
        if ($value === '' || ! strtotime($value)) {
            throw ValidationException::withMessages([
                'transaction_date' => 'Enter a date.',
            ]);
        }

        $date = date('Y-m-d', strtotime($value));

        if ($date < $financialYear->start_date->toDateString() || $date > $financialYear->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'transaction_date' => 'The date must fall in '.$financialYear->name.' ('.$financialYear->rangeLabel().').',
            ]);
        }

        return $date;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function blankLine(array $line): bool
    {
        $product = trim((string) ($line['product_id'] ?? ''));
        $quantity = trim((string) ($line['quantity'] ?? ''));
        $rate = trim((string) ($line['rate'] ?? ''));

        return $product === '' && ($quantity === '' || $quantity === '0') && ($rate === '' || $rate === '0' || $rate === '0.00');
    }

    private function blank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
