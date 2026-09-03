<?php

namespace App\Domains\Order\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\SalePricingService;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        protected SalePricingService $salePricingService,
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
    ) {}

    public function create(array $data, User $actor): Order
    {
        return DB::transaction(function () use ($data, $actor) {
            $customer = $this->loadCustomer($data['customer_id']);
            $items = $this->validateItems($data['items']);

            $pricing = $this->salePricingService->price(
                $customer,
                $items,
                (float) ($data['discount_amount'] ?? 0)
            );

            if ((float) ($data['discount_amount'] ?? 0) > $pricing['subtotal']) {
                throw ValidationException::withMessages([
                    'discount_amount' =>
                        'Discount cannot be greater than the order subtotal.',
                ]);
            }

            $actorName = $this->auditLogService->actorName(
                $data['created_by_name'] ?? $actor->name
            );

            $order = Order::create([
                'order_no' => $this->documentNumberService->next(
                    'ORD',
                    now(),
                    4
                ),
                'customer_id' => $customer->id,
                'salesperson_id' => $actor->id,
                'created_by_name' => $actorName,
                'order_date' => $data['order_date'],
                'status' => 'pending',
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount'],
                'tax_amount' => $pricing['tax'],
                'grand_total' => $pricing['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($pricing['lines'] as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'uom_id' => $line['uom']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                    'line_total' => $line['lineTotal'],
                ]);
            }

            $this->auditLogService->record(
                $order,
                'created',
                $actorName
            );

            return $order->load([
                'customer.customerType',
                'salesperson',
                'items.product',
                'items.uom',
            ]);
        });
    }

    public function update(
        Order $order,
        array $data,
        User $actor
    ): Order {
        return DB::transaction(function () use ($order, $data, $actor) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(
                $lockedOrder->status,
                ['draft', 'pending'],
                true
            )) {
                throw new InvalidArgumentException(
                    'Only draft or pending orders can be edited.'
                );
            }

            $customer = $this->loadCustomer($data['customer_id']);
            $items = $this->validateItems($data['items']);

            $pricing = $this->salePricingService->price(
                $customer,
                $items,
                (float) ($data['discount_amount'] ?? 0)
            );

            if (
                (float) ($data['discount_amount'] ?? 0)
                > $pricing['subtotal']
            ) {
                throw ValidationException::withMessages([
                    'discount_amount' =>
                        'Discount cannot be greater than the order subtotal.',
                ]);
            }

            $actorName = $this->auditLogService->actorName(
                $data['updated_by_name'] ?? $actor->name
            );

            $lockedOrder->update([
                'customer_id' => $customer->id,
                'updated_by_name' => $actorName,
                'order_date' => $data['order_date'],
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount'],
                'tax_amount' => $pricing['tax'],
                'grand_total' => $pricing['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedOrder->items()->delete();

            foreach ($pricing['lines'] as $line) {
                OrderItem::create([
                    'order_id' => $lockedOrder->id,
                    'product_id' => $line['product']->id,
                    'uom_id' => $line['uom']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'],
                    'line_total' => $line['lineTotal'],
                ]);
            }

            $this->auditLogService->record(
                $lockedOrder,
                'updated',
                $actorName
            );

            return $lockedOrder->fresh([
                'customer.customerType',
                'salesperson',
                'items.product',
                'items.uom',
            ]);
        });
    }

    public function approve(
        Order $order,
        User $actor
    ): Order {
        return DB::transaction(function () use ($order, $actor) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(
                $lockedOrder->status,
                ['draft', 'pending'],
                true
            )) {
                throw new InvalidArgumentException(
                    'Only pending orders can be approved.'
                );
            }

            $actorName = $this->auditLogService->actorName(
                $actor->name
            );

            $lockedOrder->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_by_name' => $actorName,
                'approved_at' => now(),
            ]);

            $this->auditLogService->record(
                $lockedOrder,
                'approved',
                $actorName
            );

            return $lockedOrder->fresh();
        });
    }

    public function cancel(
        Order $order,
        User $actor
    ): Order {
        return DB::transaction(function () use ($order, $actor) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(
                $lockedOrder->status,
                ['draft', 'pending', 'approved'],
                true
            )) {
                throw new InvalidArgumentException(
                    'This order can no longer be cancelled.'
                );
            }

            $actorName = $this->auditLogService->actorName(
                $actor->name
            );

            $lockedOrder->update([
                'status' => 'cancelled',
                'cancelled_by_name' => $actorName,
            ]);

            $this->auditLogService->record(
                $lockedOrder,
                'cancelled',
                $actorName
            );

            return $lockedOrder->fresh();
        });
    }

    /**
     * @param array<int, int> $orderIds
     */
    public function bulkApprove(
        array $orderIds,
        User $actor
    ): int {
        return DB::transaction(function () use ($orderIds, $actor) {
            $orderIds = array_values(
                array_unique(
                    array_map('intval', $orderIds)
                )
            );

            $orders = Order::query()
                ->whereIn('id', $orderIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== count($orderIds)) {
                throw new InvalidArgumentException(
                    'One or more selected orders no longer exist.'
                );
            }

            $actorName = $this->auditLogService->actorName(
                $actor->name
            );

            $count = 0;

            foreach ($orders as $order) {
                if (! in_array(
                    $order->status,
                    ['draft', 'pending'],
                    true
                )) {
                    continue;
                }

                $order->update([
                    'status' => 'approved',
                    'approved_by' => $actor->id,
                    'approved_by_name' => $actorName,
                    'approved_at' => now(),
                ]);

                $this->auditLogService->record(
                    $order,
                    'approved',
                    $actorName
                );

                $count++;
            }

            return $count;
        });
    }

    protected function loadCustomer(int $customerId): Customer
    {
        $customer = Customer::query()
            ->with('customerType')
            ->whereKey($customerId)
            ->where('is_active', true)
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'customer_id' =>
                    'The selected customer is inactive or does not exist.',
            ]);
        }

        if (
            ! $customer->customerType
            || ! $customer->customerType->is_active
        ) {
            throw ValidationException::withMessages([
                'customer_id' =>
                    'The customer does not have an active customer type.',
            ]);
        }

        return $customer;
    }

    protected function validateItems(array $items): array
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $uomIds = collect($items)
            ->pluck('uom_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->with(['baseUom', 'productUoms'])
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $uoms = Uom::query()
            ->whereIn('id', $uomIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $seen = [];

        foreach ($items as $index => $item) {
            $productId = (int) $item['product_id'];
            $uomId = (int) $item['uom_id'];

            if (! isset($products[$productId])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" =>
                        'The selected product is inactive or does not exist.',
                ]);
            }

            if (! isset($uoms[$uomId])) {
                throw ValidationException::withMessages([
                    "items.{$index}.uom_id" =>
                        'The selected UOM is inactive or does not exist.',
                ]);
            }

            $duplicateKey = "{$productId}:{$uomId}";

            if (isset($seen[$duplicateKey])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" =>
                        'The same product and UOM cannot appear twice in one order.',
                ]);
            }

            $seen[$duplicateKey] = true;

            $product = $products[$productId];
            $uom = $uoms[$uomId];

            $allowedUom =
                $product->base_uom_id === $uomId
                || $product->productUoms->contains(
                    fn ($productUom) =>
                        (int) $productUom->uom_id === $uomId
                );

            if (! $allowedUom) {
                throw ValidationException::withMessages([
                    "items.{$index}.uom_id" =>
                        "The selected UOM ({$uom->name}) is not configured for {$product->name}.",
                ]);
            }

            /*
            * Quantity validation
            *
            * Piece, Box and Case are count-based UOMs,
            * therefore they must use whole numbers.
            *
            * Weight/volume UOMs can use decimal quantities,
            * but only up to 4 decimal places.
            */
            $quantity = (string) ($item['quantity'] ?? '');

            if ($quantity === '' || ! is_numeric($quantity)) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" =>
                        "Please enter a valid quantity for {$product->name}.",
                ]);
            }

            $quantity = trim($quantity);

            if ((float) $quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" =>
                        "Quantity for {$product->name} ({$uom->name}) must be greater than zero.",
                ]);
            }

            $isWholeUnit = in_array(
                strtoupper((string) $uom->code),
                ['PCS', 'BOX', 'CASE'],
                true
            );

            if ($isWholeUnit && floor((float) $quantity) !== (float) $quantity) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" =>
                        "{$product->name} uses {$uom->name}, so the quantity must be a whole number.",
                ]);
            }

            /*
            * Database quantities are stored to 4 decimal places.
            * Prevent values with more than 4 decimal places.
            */
            if (
                str_contains($quantity, '.')
                && strlen(rtrim(substr(strrchr($quantity, '.'), 1), '0')) > 4
            ) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" =>
                        "Quantity for {$product->name} can have a maximum of 4 decimal places.",
                ]);
            }
        }

        return array_map(
            static function (array $item): array {
                $quantity = round((float) $item['quantity'], 4);

                return [
                    'product_id' => (int) $item['product_id'],
                    'uom_id' => (int) $item['uom_id'],
                    'quantity' => $quantity,
                    'unit_price' => isset($item['unit_price'])
                        ? (float) $item['unit_price']
                        : null,
                ];
            },
            $items
        );
    }}