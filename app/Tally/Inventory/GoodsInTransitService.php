<?php

namespace Tally\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Models\Company;
use Tally\Models\Godown;
use Tally\Models\GoodsInTransit;
use Tally\Models\Product;

class GoodsInTransitService
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function save(Company $company, array $input, ?GoodsInTransit $record = null): GoodsInTransit
    {
        return DB::transaction(function () use ($company, $input, $record) {
            if ($record && ! $record->isOpen()) {
                throw ValidationException::withMessages([
                    'status' => 'Received or closed transit cannot be changed.',
                ]);
            }

            $product = Product::query()
                ->where('company_id', $company->id)
                ->whereKey((int) ($input['product_id'] ?? 0))
                ->firstOrFail();

            $fromId = isset($input['from_godown_id']) && $input['from_godown_id'] !== '' ? (int) $input['from_godown_id'] : null;
            $toId = isset($input['to_godown_id']) && $input['to_godown_id'] !== '' ? (int) $input['to_godown_id'] : null;

            if ($fromId !== null) {
                Godown::query()->where('company_id', $company->id)->whereKey($fromId)->firstOrFail();
            }
            if ($toId !== null) {
                Godown::query()->where('company_id', $company->id)->whereKey($toId)->firstOrFail();
            }

            $payload = [
                'from_godown_id' => $fromId,
                'to_godown_id' => $toId,
                'product_id' => $product->id,
                'quantity' => number_format((float) ($input['quantity'] ?? 0), 4, '.', ''),
                'dispatch_date' => (string) ($input['dispatch_date'] ?? now()->toDateString()),
                'expected_receipt_date' => ($input['expected_receipt_date'] ?? null) ?: null,
                'reference' => ($input['reference'] ?? null) ?: null,
                'is_consignment' => (bool) ($input['is_consignment'] ?? false),
                'status' => ($input['status'] ?? null) ?: ($record?->status ?? 'in_transit'),
            ];

            if ((float) $payload['quantity'] <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity must be greater than zero.',
                ]);
            }

            if ($record) {
                $record->update($payload);

                return $record->fresh(['product', 'fromGodown', 'toGodown']);
            }

            return GoodsInTransit::query()->create($payload + [
                'company_id' => $company->id,
            ])->fresh(['product', 'fromGodown', 'toGodown']);
        });
    }

    public function receive(GoodsInTransit $record, ?string $receivedDate = null): GoodsInTransit
    {
        if (! $record->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'This transit is already closed.',
            ]);
        }

        $record->update([
            'status' => 'received',
            'received_date' => $receivedDate ?: now()->toDateString(),
        ]);

        return $record->fresh(['product', 'fromGodown', 'toGodown']);
    }
}
