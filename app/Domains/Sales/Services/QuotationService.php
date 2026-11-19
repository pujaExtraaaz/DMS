<?php

namespace App\Domains\Sales\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\SalePricingService;
use App\Domains\Order\Services\OrderService;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Models\QuotationItem;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class QuotationService
{
    public function __construct(
        protected SalePricingService $salePricingService,
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
        protected RegionBrandPolicyService $regionBrandPolicyService,
        protected OrderService $orderService,
    ) {}

    public function create(array $data, User $actor): Quotation
    {
        return DB::transaction(function () use ($data, $actor) {
            $customer = Customer::query()
                ->with(['customerType', 'area'])
                ->whereKey($data['customer_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $company = $this->resolveCompany($data, $actor);
            $items = $this->normalizeItems($data['items']);
            $this->regionBrandPolicyService->assertItemsAllowed($customer, $items);

            $pricing = $this->salePricingService->price(
                $customer,
                $items,
                (float) ($data['discount_amount'] ?? 0)
            );

            $profitLines = $this->attachProfit($pricing['lines'], $items);
            $estimatedProfit = collect($profitLines)->sum('estimated_profit');

            $actorName = $this->auditLogService->actorName(
                $data['created_by_name'] ?? $actor->name
            );

            $companyId = $company?->id ?? $actor->company_id ?? $customer->company_id ?? null;
            $quotationNo = $this->resolveQuotationNumber($data, $companyId);

            $quotation = Quotation::create([
                'company_id' => $company?->id,
                'quotation_no' => $quotationNo,
                'customer_id' => $customer->id,
                'salesperson_id' => $actor->id,
                'quotation_date' => $data['quotation_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'title' => $data['title'] ?? null,
                'status' => 'draft',
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount'],
                'tax_amount' => $pricing['tax'],
                'grand_total' => $pricing['grand_total'] ?? $pricing['total'],
                'estimated_profit' => $estimatedProfit,
                'notes' => $data['notes'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? $this->defaultSpecialInstructions(),
                'terms_conditions' => $data['terms_conditions'] ?? $this->defaultTerms($company),
                'created_by_name' => $actorName,
            ]);

            $this->persistItems($quotation, $profitLines);

            $this->auditLogService->record($quotation, 'created', $actorName);

            return $quotation->load(['customer', 'company', 'items.product', 'items.uom']);
        });
    }

    public function resolveSeries(?int $companyId = null): ?\App\Domains\Organization\Models\DocumentNumberSeries
    {
        return $this->documentNumberService->resolveSeries('QTN', $companyId);
    }

    public function peekNumber(?int $companyId = null, ?\Illuminate\Support\Carbon $date = null): ?string
    {
        return $this->documentNumberService->peekFromSeries('QTN', $date, $companyId);
    }

    public function resolveQuotationNumber(array $data, ?int $companyId = null): string
    {
        $series = $this->resolveSeries($companyId);
        $date = ! empty($data['quotation_date']) ? \Illuminate\Support\Carbon::parse($data['quotation_date']) : now();
        $provided = strtoupper(trim((string) ($data['quotation_no'] ?? '')));

        if ($series) {
            if ($series->isManual()) {
                if ($provided === '') {
                    throw ValidationException::withMessages([
                        'quotation_no' => 'Quotation number is required when numbering is set to manual.',
                    ]);
                }

                $exists = Quotation::query()->where('quotation_no', $provided)->exists();
                if ($exists) {
                    throw ValidationException::withMessages([
                        'quotation_no' => 'This quotation number already exists.',
                    ]);
                }

                return $provided;
            }

            if ($series->isManualOverride()) {
                if ($provided !== '') {
                    $exists = Quotation::query()->where('quotation_no', $provided)->exists();
                    if ($exists) {
                        throw ValidationException::withMessages([
                            'quotation_no' => 'This quotation number already exists.',
                        ]);
                    }

                    return $provided;
                }

                return $this->documentNumberService->nextFromSeries(
                    $series,
                    $date,
                    $companyId,
                    fn (string $no) => Quotation::query()->where('quotation_no', $no)->exists()
                );
            }

            return $this->documentNumberService->nextFromSeries(
                $series,
                $date,
                $companyId,
                fn (string $no) => Quotation::query()->where('quotation_no', $no)->exists()
            );
        }

        if ($provided !== '') {
            $exists = Quotation::query()->where('quotation_no', $provided)->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'quotation_no' => 'This quotation number already exists.',
                ]);
            }

            return $provided;
        }

        $attempts = 0;
        do {
            $candidate = $this->documentNumberService->next('QTN', $date, 4, $companyId);
            $exists = Quotation::query()->where('quotation_no', $candidate)->exists();
            $attempts++;
        } while ($exists && $attempts < 50);

        return $candidate;
    }

    public function update(Quotation $quotation, array $data, User $actor): Quotation
    {
        return DB::transaction(function () use ($quotation, $data, $actor) {
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isEditable()) {
                throw new InvalidArgumentException('Only draft or sent quotations can be edited.');
            }

            $customer = Customer::query()
                ->with(['customerType', 'area'])
                ->whereKey($data['customer_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $company = $this->resolveCompany($data, $actor, $locked);
            $items = $this->normalizeItems($data['items']);
            $this->regionBrandPolicyService->assertItemsAllowed($customer, $items);

            $pricing = $this->salePricingService->price(
                $customer,
                $items,
                (float) ($data['discount_amount'] ?? 0)
            );

            $profitLines = $this->attachProfit($pricing['lines'], $items);
            $estimatedProfit = collect($profitLines)->sum('estimated_profit');
            $actorName = $this->auditLogService->actorName($data['updated_by_name'] ?? $actor->name);

            $locked->update([
                'company_id' => $company?->id ?? $locked->company_id,
                'customer_id' => $customer->id,
                'quotation_date' => $data['quotation_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'title' => $data['title'] ?? null,
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount'],
                'tax_amount' => $pricing['tax'],
                'grand_total' => $pricing['grand_total'] ?? $pricing['total'],
                'estimated_profit' => $estimatedProfit,
                'notes' => $data['notes'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'updated_by_name' => $actorName,
            ]);

            $locked->items()->delete();
            $this->persistItems($locked, $profitLines);

            $this->auditLogService->record($locked, 'updated', $actorName);

            return $locked->fresh(['customer', 'company', 'items.product', 'items.uom']);
        });
    }

    public function delete(Quotation $quotation, User $actor): void
    {
        DB::transaction(function () use ($quotation, $actor) {
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isDeletable()) {
                throw new InvalidArgumentException('Only draft quotations without a converted order can be deleted.');
            }

            $actorName = $this->auditLogService->actorName($actor->name);
            $this->auditLogService->record($locked, 'deleted', $actorName);

            $locked->items()->delete();
            $locked->delete();
        });
    }

    public function markSent(Quotation $quotation, User $actor): Quotation
    {
        if ($quotation->status !== 'draft') {
            throw new InvalidArgumentException('Only draft quotations can be marked sent.');
        }

        $quotation->update(['status' => 'sent']);
        $this->auditLogService->record($quotation, 'sent', $actor->name);

        return $quotation->fresh();
    }

    public function accept(Quotation $quotation, User $actor): Quotation
    {
        return DB::transaction(function () use ($quotation, $actor) {
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('items');

            if ($locked->converted_order_id) {
                throw new InvalidArgumentException('Quotation already converted to a sales order.');
            }

            if (! in_array($locked->status, ['draft', 'sent', 'accepted'], true)) {
                throw new InvalidArgumentException('Quotation cannot be accepted in its current status.');
            }

            if ($locked->items->isEmpty()) {
                throw new InvalidArgumentException('Cannot convert a quotation without line items.');
            }

            $this->ensureCustomerHasActiveType((int) $locked->customer_id);

            $orderItems = $this->buildOrderItemsFromQuotation($locked);
            $notes = trim(implode("\n", array_filter([
                $locked->notes,
                $locked->title ? 'Quotation title: '.$locked->title : null,
            ])));

            $order = $this->orderService->create([
                'customer_id' => $locked->customer_id,
                'order_date' => now()->toDateString(),
                'due_date' => $locked->valid_until?->toDateString(),
                'salesperson_id' => $locked->salesperson_id,
                'quotation_id' => $locked->id,
                'reference_no' => $locked->quotation_no,
                'discount_amount' => (float) $locked->discount_amount,
                'notes' => $notes !== '' ? $notes : null,
                'created_by_name' => $actor->name,
                'items' => $orderItems,
            ], $actor);

            $actorName = $this->auditLogService->actorName($actor->name);
            $locked->update([
                'status' => 'converted',
                'converted_order_id' => $order->id,
                'updated_by_name' => $actorName,
            ]);

            $this->auditLogService->record($locked, 'converted', $actorName);

            return $locked->fresh(['customer', 'company', 'items.product', 'items.uom', 'convertedOrder']);
        });
    }

    protected function ensureCustomerHasActiveType(int $customerId): void
    {
        $customer = Customer::query()
            ->with('customerType')
            ->whereKey($customerId)
            ->where('is_active', true)
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'customer_id' => 'The selected customer is inactive or does not exist.',
            ]);
        }

        if ($customer->customerType && $customer->customerType->is_active) {
            return;
        }

        $typeId = CustomerType::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if (! $typeId) {
            $typeId = CustomerType::create([
                'name' => 'General',
                'code' => 'GEN',
                'is_active' => true,
            ])->id;
        }

        $customer->forceFill(['customer_type_id' => $typeId])->save();
    }

    /**
     * @return array<int, array{product_id: int, uom_id: int, quantity: float, unit_price: float}>
     */
    protected function buildOrderItemsFromQuotation(Quotation $quotation): array
    {
        $grouped = [];

        foreach ($quotation->items as $line) {
            $key = ((int) $line->product_id).':'.((int) $line->uom_id);
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'product_id' => (int) $line->product_id,
                    'uom_id' => (int) $line->uom_id,
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                ];
                continue;
            }

            $existingQty = (float) $grouped[$key]['quantity'];
            $addQty = (float) $line->quantity;
            $totalQty = $existingQty + $addQty;
            if ($totalQty > 0) {
                $grouped[$key]['unit_price'] = round(
                    (((float) $grouped[$key]['unit_price'] * $existingQty) + ((float) $line->unit_price * $addQty)) / $totalQty,
                    2
                );
            }
            $grouped[$key]['quantity'] = round($totalQty, 4);
        }

        return array_values($grouped);
    }


    public function defaultSpecialInstructions(): string
    {
        return "Review this quotation carefully.\nAdjustments or clarifications can be made\nbefore approval.";
    }

    public function defaultTerms(?Company $company = null): string
    {
        $company ??= Company::defaultCompany();
        $companyName = $company?->name ?: 'the Company';
        $city = $company?->city ?: '';
        $billTo = trim($companyName.($city !== '' ? ', '.$city : ''));

        return implode("\n", [
            'Purchase order and Billing to be made in the name of "'.$billTo.'"',
            'Payment: - 100% advances along with Purchase Order.',
            'Installation: - Conducting & Cable Pulling to be provided by Client Only.',
            'Installation: - Any POP, Paint and PVC job not in our scope',
            'Installation: - All wooden Material & work to be provided by Client Only',
            'Validity: -Above Price is valid till 10 days from proposal date',
            'Warranty: As per OEM',
            'Any freight is in client scope as per actual.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array<string, mixed>>  $rawItems
     * @return array<int, array<string, mixed>>
     */
    protected function attachProfit(array $lines, array $rawItems = []): array
    {
        return array_map(function (array $line, int $index) use ($rawItems) {
            /** @var Product $product */
            $product = $line['product'];
            $qty = (float) $line['quantity'];
            $unitLanded = (float) ($product->purchase_price ?? 0);
            $landed = round($unitLanded * $qty, 2);
            $lineTotal = (float) $line['lineTotal'];

            $line['estimated_landed_cost'] = $unitLanded;
            $line['estimated_profit'] = round($lineTotal - $landed, 2);
            $line['sort_order'] = $index + 1;
            $line['snapshot'] = $this->buildLineSnapshot($product, $rawItems[$index] ?? []);

            return $line;
        }, $lines, array_keys($lines));
    }

    /**
     * @param  array<string, mixed>  $rawItem
     * @return array{description: ?string, hsn_code: ?string, mrp: ?float, tax_rate: ?float, image_path: ?string}
     */
    protected function buildLineSnapshot(Product $product, array $rawItem): array
    {
        $defaultDescription = trim((string) ($product->description ?: $product->specification ?: $product->name));
        $description = isset($rawItem['description']) && trim((string) $rawItem['description']) !== ''
            ? trim((string) $rawItem['description'])
            : ($defaultDescription !== '' ? $defaultDescription : $product->name);

        $mrp = null;
        if (isset($rawItem['mrp']) && $rawItem['mrp'] !== '' && $rawItem['mrp'] !== null) {
            $mrp = round((float) $rawItem['mrp'], 2);
        } else {
            $catalogMrp = (float) $product->mrp;
            $mrp = $catalogMrp > 0 ? round($catalogMrp, 2) : null;
        }

        return [
            'description' => $description,
            'hsn_code' => isset($rawItem['hsn_code']) && trim((string) $rawItem['hsn_code']) !== ''
                ? trim((string) $rawItem['hsn_code'])
                : ($product->hsn_code ?: null),
            'mrp' => $mrp,
            'tax_rate' => isset($rawItem['tax_rate']) && $rawItem['tax_rate'] !== '' && $rawItem['tax_rate'] !== null
                ? round((float) $rawItem['tax_rate'], 2)
                : (($product->tax_rate !== null) ? round((float) $product->tax_rate, 2) : null),
            'image_path' => ! empty($rawItem['uploaded_image_path'])
                ? (string) $rawItem['uploaded_image_path']
                : (! empty($rawItem['existing_image_path'])
                    ? (string) $rawItem['existing_image_path']
                    : ($product->image_path ?: null)),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $profitLines
     */
    protected function persistItems(Quotation $quotation, array $profitLines): void
    {
        foreach ($profitLines as $line) {
            $snapshot = $line['snapshot'] ?? [];
            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'sort_order' => (int) ($line['sort_order'] ?? 0),
                'product_id' => $line['product']->id,
                'uom_id' => $line['uom']->id,
                'description' => $snapshot['description'] ?? null,
                'hsn_code' => $snapshot['hsn_code'] ?? null,
                'mrp' => $snapshot['mrp'] ?? null,
                'tax_rate' => $snapshot['tax_rate'] ?? null,
                'image_path' => $snapshot['image_path'] ?? null,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unitPrice'],
                'discount_amount' => $line['discount'] ?? 0,
                'line_total' => $line['lineTotal'],
                'estimated_landed_cost' => $line['estimated_landed_cost'],
                'estimated_profit' => $line['estimated_profit'],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{product_id: int, uom_id: int, quantity: float, unit_price: ?float, description: ?string, hsn_code: ?string, mrp: ?float, tax_rate: ?float}>
     */
    protected function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'At least one quotation line is required.',
            ]);
        }

        $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
        $uomIds = collect($items)->pluck('uom_id')->map(fn ($id) => (int) $id)->unique();

        $products = Product::query()->whereIn('id', $productIds)->where('is_active', true)->get()->keyBy('id');
        $uoms = Uom::query()->whereIn('id', $uomIds)->where('is_active', true)->get()->keyBy('id');

        foreach ($items as $index => $item) {
            if (! isset($products[(int) $item['product_id']])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'Invalid product.',
                ]);
            }
            if (! isset($uoms[(int) $item['uom_id']])) {
                throw ValidationException::withMessages([
                    "items.{$index}.uom_id" => 'Invalid UOM.',
                ]);
            }
        }

        return array_values(array_map(static function (array $item) {
            return [
                'product_id' => (int) $item['product_id'],
                'uom_id' => (int) $item['uom_id'],
                'quantity' => round((float) $item['quantity'], 4),
                'unit_price' => isset($item['unit_price']) && $item['unit_price'] !== '' ? (float) $item['unit_price'] : null,
                'description' => isset($item['description']) ? trim((string) $item['description']) : null,
                'hsn_code' => isset($item['hsn_code']) ? trim((string) $item['hsn_code']) : null,
                'mrp' => isset($item['mrp']) && $item['mrp'] !== '' ? (float) $item['mrp'] : null,
                'tax_rate' => isset($item['tax_rate']) && $item['tax_rate'] !== '' ? (float) $item['tax_rate'] : null,
                'uploaded_image_path' => isset($item['uploaded_image_path']) ? (string) $item['uploaded_image_path'] : null,
                'existing_image_path' => isset($item['existing_image_path']) ? (string) $item['existing_image_path'] : null,
            ];
        }, $items));
    }

    protected function resolveCompany(array $data, User $actor, ?Quotation $existing = null): ?Company
    {
        if (! empty($data['company_id'])) {
            return Company::query()->whereKey($data['company_id'])->first();
        }

        if ($existing?->company_id) {
            return $existing->company ?: Company::query()->find($existing->company_id);
        }

        if ($actor->company_id) {
            return Company::query()->find($actor->company_id) ?: Company::defaultCompany();
        }

        return Company::defaultCompany();
    }
}
