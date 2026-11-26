<?php

namespace App\Domains\Purchasing\Imports;

use App\Domains\Master\Imports\BulkImportHelpers;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Services\UomLookupService;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Services\PurchaseOrderService;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import Purchase Orders (one CSV row per line item).
 * Canonical columns: see BulkImportCatalog::get('purchase-orders').
 */
class PurchaseOrdersImport implements ToCollection, WithHeadingRow
{
    use BulkImportHelpers, Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function __construct(protected ?int $companyId = null) {}

    public function collection(Collection $rows): void
    {
        /** @var User|null $actor */
        $actor = auth()->user();
        if (! $actor instanceof User) {
            $this->errors[] = ['row' => 0, 'field' => 'auth', 'message' => 'Authenticated user is required for purchase order import'];
            $this->skipped++;

            return;
        }

        $service = app(PurchaseOrderService::class);
        $fyService = app(FinancialYearService::class);

        $companies = Company::query()->get()->keyBy(fn ($c) => strtolower((string) $c->code));
        $warehouses = Warehouse::query()->get();
        $productsBySku = Product::query()->get()->keyBy(fn ($p) => strtolower((string) $p->sku));
        $suppliers = Customer::query()->get();

        $groups = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->rowData($row);

            if ($this->isBlankRow($data)) {
                continue;
            }

            $poNo = $this->str($data, 'po_no') ?: $this->str($data, 'po_number');
            $supplierKey = $this->str($data, 'supplier_code')
                ?: $this->str($data, 'supplier_gstin')
                ?: $this->str($data, 'supplier_name');
            $poDate = $this->str($data, 'po_date');
            $groupKey = $poNo !== ''
                ? 'po:'.strtolower($poNo)
                : ($supplierKey !== '' && $poDate !== ''
                    ? 'tmp:'.strtolower($supplierKey).'|'.$poDate
                    : 'row:'.$rowNo);

            $groups[$groupKey] ??= [
                'first_row' => $rowNo,
                'header' => null,
                'lines' => [],
            ];

            if ($groups[$groupKey]['header'] === null) {
                $groups[$groupKey]['header'] = $data;
            }

            $groups[$groupKey]['lines'][] = [
                'row' => $rowNo,
                'data' => $data,
            ];
        }

        foreach ($groups as $group) {
            $header = $group['header'] ?? [];
            $firstRow = (int) $group['first_row'];

            try {
                $poDate = $this->str($header, 'po_date');
                if ($poDate === '') {
                    $this->skip($firstRow, 'po_date', 'PO date is required (YYYY-MM-DD)');
                    continue;
                }

                try {
                    $fyService->assertOpen($poDate);
                } catch (\Throwable $e) {
                    $this->skip($firstRow, 'po_date', $e->getMessage());
                    continue;
                }

                $supplier = $this->resolveSupplier($suppliers, $header);
                if (! $supplier) {
                    $this->skip($firstRow, 'supplier_code', 'Supplier not found (use supplier_code, supplier_gstin, or supplier_name)');
                    continue;
                }

                $companyCode = $this->str($header, 'company_code');
                $company = $companyCode !== ''
                    ? ($companies[strtolower($companyCode)] ?? null)
                    : null;
                $resolvedCompanyId = $company?->id
                    ?? $this->companyId
                    ?? $actor->company_id
                    ?? Company::defaultCompanyId();

                $warehouseId = null;
                $warehouseCode = $this->str($header, 'warehouse_code');
                if ($warehouseCode !== '') {
                    $warehouse = $warehouses->first(function ($w) use ($warehouseCode, $resolvedCompanyId) {
                        return strcasecmp((string) $w->code, $warehouseCode) === 0
                            && (int) $w->company_id === (int) $resolvedCompanyId;
                    });
                    if (! $warehouse) {
                        $this->skip($firstRow, 'warehouse_code', "Warehouse '{$warehouseCode}' not found for company");
                        continue;
                    }
                    $warehouseId = $warehouse->id;
                }

                $items = [];
                $lineFailed = false;
                foreach ($group['lines'] as $line) {
                    $lineData = $line['data'];
                    $rowNo = (int) $line['row'];
                    $sku = $this->str($lineData, 'part_number')
                        ?: $this->str($lineData, 'part_no')
                        ?: $this->str($lineData, 'partnumber')
                        ?: $this->str($lineData, 'sku');

                    if ($sku === '') {
                        $this->skip($rowNo, 'part_number', 'Part number is required');
                        $lineFailed = true;
                        break;
                    }

                    $product = $productsBySku->get(strtolower($sku));
                    if (! $product) {
                        $this->skip($rowNo, 'part_number', "Product with Part Number '{$sku}' not found");
                        $lineFailed = true;
                        break;
                    }

                    $qty = (float) ($lineData['quantity'] ?? 0);
                    if ($qty <= 0) {
                        $this->skip($rowNo, 'quantity', 'Quantity must be greater than 0');
                        $lineFailed = true;
                        break;
                    }

                    $unitCost = (float) ($lineData['unit_cost'] ?? $lineData['rate'] ?? 0);
                    if ($unitCost < 0) {
                        $this->skip($rowNo, 'unit_cost', 'Unit cost cannot be negative');
                        $lineFailed = true;
                        break;
                    }

                    $uomId = null;
                    $uomCode = $this->str($lineData, 'uom_code') ?: $this->str($lineData, 'uom');
                    if ($uomCode !== '') {
                        $uom = UomLookupService::resolve($uomCode);
                        if (! $uom) {
                            $this->skip($rowNo, 'uom_code', "Unknown UOM '{$uomCode}'");
                            $lineFailed = true;
                            break;
                        }
                        $uomId = $uom->id;
                    }

                    $taxPercent = $lineData['tax_percent'] ?? null;
                    if ($taxPercent === null || $taxPercent === '') {
                        $taxPercent = (float) ($lineData['cgst_percent'] ?? 0) + (float) ($lineData['sgst_percent'] ?? 0);
                        if ((float) $taxPercent <= 0 && isset($lineData['igst_percent'])) {
                            $taxPercent = (float) $lineData['igst_percent'];
                        }
                        if ((float) $taxPercent <= 0) {
                            $taxPercent = (float) ($product->tax_rate ?? 0);
                        }
                    }

                    $items[] = [
                        'product_id' => $product->id,
                        'uom_id' => $uomId,
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                        'tax_percent' => (float) $taxPercent,
                        'cgst_percent' => filled($lineData['cgst_percent'] ?? null) ? (float) $lineData['cgst_percent'] : null,
                        'sgst_percent' => filled($lineData['sgst_percent'] ?? null) ? (float) $lineData['sgst_percent'] : null,
                        'igst_percent' => filled($lineData['igst_percent'] ?? null) ? (float) $lineData['igst_percent'] : null,
                        'mrp' => filled($lineData['mrp'] ?? null) ? (float) $lineData['mrp'] : null,
                        'purchase_price_inclusive' => filled($lineData['purchase_price_inclusive'] ?? null)
                            ? (float) $lineData['purchase_price_inclusive']
                            : (filled($lineData['price_inclusive_tax'] ?? null) ? (float) $lineData['price_inclusive_tax'] : null),
                        'purchase_discount_percent' => filled($lineData['purchase_discount_percent'] ?? null)
                            ? (float) $lineData['purchase_discount_percent']
                            : null,
                        'batch_no' => $this->nullableStr($lineData, 'batch_no') ?: $this->nullableStr($lineData, 'batch_name'),
                        'serial_no' => $this->nullableStr($lineData, 'serial_no'),
                        'weight' => filled($lineData['weight'] ?? null) ? (float) $lineData['weight'] : null,
                    ];
                }

                if ($lineFailed || $items === []) {
                    if (! $lineFailed) {
                        $this->skip($firstRow, 'items', 'At least one valid line item is required');
                    }
                    continue;
                }

                $payload = [
                    'company_id' => $resolvedCompanyId,
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouseId,
                    'po_date' => $poDate,
                    'expected_date' => $this->nullableStr($header, 'expected_date'),
                    'credit_days' => filled($header['credit_days'] ?? null) ? (int) $header['credit_days'] : null,
                    'notes' => $this->nullableStr($header, 'notes'),
                    'submit_for_approval' => $this->isTruthy($header['submit_for_approval'] ?? false),
                    'is_freight_applicable' => $this->isTruthy($header['is_freight_applicable'] ?? false),
                    'freight_mode' => $this->nullableStr($header, 'freight_mode') ?: 'amount',
                    'freight_percent' => filled($header['freight_percent'] ?? null) ? (float) $header['freight_percent'] : null,
                    'freight_amount' => filled($header['freight_amount'] ?? null) ? (float) $header['freight_amount'] : null,
                    'freight_tax_rate' => filled($header['freight_tax_rate'] ?? null) ? (float) $header['freight_tax_rate'] : null,
                    'freight_notes' => $this->nullableStr($header, 'freight_notes'),
                    'items' => $items,
                ];

                $poNo = $this->str($header, 'po_no') ?: $this->str($header, 'po_number');
                if ($poNo !== '') {
                    $payload['po_no'] = $poNo;
                }

                $existing = $this->findExistingOrder($resolvedCompanyId, $poNo, $supplier->id, $poDate, $items);

                if ($existing) {
                    if (! $existing->isEditable()) {
                        $label = $existing->po_no ?: ('#'.$existing->id);
                        $this->skip($firstRow, 'po_no', "Purchase order '{$label}' exists and is not editable");
                        continue;
                    }

                    if (! $this->purchaseOrderChanged($existing, $payload, $items)) {
                        $this->skipped++;
                        continue;
                    }

                    $service->update($existing, $payload, $actor);
                    $this->updated++;
                } else {
                    $service->create($payload, $actor);
                    $this->created++;
                }
            } catch (ValidationException $e) {
                $messages = collect($e->errors())->flatten()->implode(' | ');
                $this->skip($firstRow, 'purchase_order', $messages !== '' ? $messages : $e->getMessage());
            } catch (\Throwable $e) {
                $this->skip($firstRow, 'purchase_order', $e->getMessage());
            }
        }
    }

    protected function isBlankRow(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function resolveSupplier(Collection $suppliers, array $header): ?Customer
    {
        $code = $this->str($header, 'supplier_code');
        if ($code !== '') {
            $match = $suppliers->first(fn (Customer $c) => strcasecmp((string) $c->code, $code) === 0);
            if ($match) {
                return $match;
            }
        }

        $gstin = $this->str($header, 'supplier_gstin');
        if ($gstin !== '') {
            $match = $suppliers->first(fn (Customer $c) => strcasecmp((string) $c->gstin, $gstin) === 0);
            if ($match) {
                return $match;
            }
        }

        $name = $this->str($header, 'supplier_name');
        if ($name !== '') {
            return $suppliers->first(fn (Customer $c) => strcasecmp((string) $c->name, $name) === 0);
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected function findExistingOrder(
        int $companyId,
        string $poNo,
        int $supplierId,
        string $poDate,
        array $items,
    ): ?PurchaseOrder {
        if ($poNo !== '') {
            return PurchaseOrder::query()
                ->with('items')
                ->where('company_id', $companyId)
                ->whereRaw('LOWER(po_no) = ?', [strtolower($poNo)])
                ->first();
        }

        $candidates = PurchaseOrder::query()
            ->with('items')
            ->where('company_id', $companyId)
            ->where('supplier_id', $supplierId)
            ->whereDate('po_date', $poDate)
            ->whereIn('status', ['draft', 'pending_approval', 'approved'])
            ->whereDoesntHave('inwards')
            ->whereDoesntHave('invoices')
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $fingerprint = $this->itemsFingerprint($items);
        $matched = $candidates->first(fn (PurchaseOrder $po) => $this->itemsFingerprint(
            $po->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'uom_id' => $item->uom_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'tax_percent' => $item->tax_percent,
                'batch_no' => $item->batch_no,
                'serial_no' => $item->serial_no,
            ])->all()
        ) === $fingerprint);

        if ($matched) {
            return $matched;
        }

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array<string, mixed>>  $items
     */
    protected function purchaseOrderChanged(PurchaseOrder $existing, array $payload, array $items): bool
    {
        $headerKeys = [
            'supplier_id', 'warehouse_id', 'po_date', 'expected_date', 'credit_days', 'notes',
            'is_freight_applicable', 'freight_mode', 'freight_percent', 'freight_amount',
            'freight_tax_rate', 'freight_notes',
        ];

        $current = [
            'supplier_id' => $existing->supplier_id,
            'warehouse_id' => $existing->warehouse_id,
            'po_date' => optional($existing->po_date)?->toDateString(),
            'expected_date' => optional($existing->expected_date)?->toDateString(),
            'credit_days' => $existing->credit_days,
            'notes' => $existing->notes,
            'is_freight_applicable' => (bool) $existing->is_freight_applicable,
            'freight_mode' => $existing->freight_mode,
            'freight_percent' => $existing->freight_percent,
            'freight_amount' => $existing->freight_amount,
            'freight_tax_rate' => $existing->freight_tax_rate,
            'freight_notes' => $existing->freight_notes,
        ];

        if ($this->payloadChanged($current, $payload, $headerKeys)) {
            return true;
        }

        $desiredStatus = ! empty($payload['submit_for_approval']) ? 'pending_approval' : $existing->status;
        if ($this->normalizeCompareValue($existing->status) !== $this->normalizeCompareValue($desiredStatus)
            && ! empty($payload['submit_for_approval'])
            && $existing->status === 'draft') {
            return true;
        }

        $existingFingerprint = $this->itemsFingerprint(
            $existing->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'uom_id' => $item->uom_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'tax_percent' => $item->tax_percent,
                'mrp' => $item->mrp,
                'purchase_price_inclusive' => $item->purchase_price_inclusive,
                'purchase_discount_percent' => $item->purchase_discount_percent,
                'batch_no' => $item->batch_no,
                'serial_no' => $item->serial_no,
                'weight' => $item->weight,
            ])->all()
        );

        return $existingFingerprint !== $this->itemsFingerprint($items);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected function itemsFingerprint(array $items): string
    {
        $normalized = collect($items)->map(function (array $item) {
            return [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'uom_id' => (int) ($item['uom_id'] ?? 0),
                'quantity' => $this->normalizeCompareValue($item['quantity'] ?? 0),
                'unit_cost' => $this->normalizeCompareValue($item['unit_cost'] ?? 0),
                'tax_percent' => $this->normalizeCompareValue($item['tax_percent'] ?? 0),
                'mrp' => $this->normalizeCompareValue($item['mrp'] ?? null),
                'purchase_price_inclusive' => $this->normalizeCompareValue($item['purchase_price_inclusive'] ?? null),
                'purchase_discount_percent' => $this->normalizeCompareValue($item['purchase_discount_percent'] ?? null),
                'batch_no' => $this->normalizeCompareValue($item['batch_no'] ?? null),
                'serial_no' => $this->normalizeCompareValue($item['serial_no'] ?? null),
                'weight' => $this->normalizeCompareValue($item['weight'] ?? null),
            ];
        })->sortBy(fn (array $item) => $item['product_id'].'|'.$item['uom_id'].'|'.$item['quantity'])
            ->values()
            ->all();

        return md5(json_encode($normalized) ?: '');
    }
}
