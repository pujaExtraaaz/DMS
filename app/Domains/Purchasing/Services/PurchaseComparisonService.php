<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Purchasing\Models\PurchaseComparison;
use App\Domains\Purchasing\Models\PurchaseComparisonEvent;
use App\Domains\Purchasing\Models\PurchaseComparisonFile;
use App\Domains\Purchasing\Models\PurchaseComparisonItem;
use App\Domains\Purchasing\Models\PurchaseComparisonOrder;
use App\Domains\Purchasing\Models\PurchaseComparisonQuote;
use App\Domains\Purchasing\Models\PurchaseComparisonVendor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PurchaseComparisonService
{
    public function __construct(
        private readonly ComparisonMath $math,
        private readonly PurchaseOrderService $purchaseOrders,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<int, UploadedFile>>  $files
     */
    public function save(?PurchaseComparison $sheet, array $data, User $actor, array $files = [], array $removeFileIds = []): PurchaseComparison
    {
        return DB::transaction(function () use ($sheet, $data, $actor, $files, $removeFileIds) {
            $creating = $sheet === null;
            $sheet ??= new PurchaseComparison([
                'number' => $this->nextNumber(),
                'prepared_by' => $actor->id,
                'company_id' => $actor->company_id,
                'branch_id' => $actor->branch_id,
                'status' => PurchaseComparison::DRAFT,
            ]);

            $sheet->fill([
                'comparison_date' => $data['comparison_date'],
                'updated_by' => $actor->id,
                'indent_no' => $data['indent_no'] ?? null,
                'customer_project' => $data['customer_project'] ?? null,
                'required_delivery_date' => $data['required_delivery_date'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'executive_remarks' => $data['executive_remarks'] ?? null,
                'status' => PurchaseComparison::DRAFT,
                'rejection_reason' => null,
                'approved_by' => null,
                'approved_at' => null,
            ]);
            $sheet->save();

            $this->replaceChildren($sheet, $data, $files, $removeFileIds);
            $this->event($sheet, $actor, $creating ? 'created' : 'updated', null);

            return $sheet->fresh(['vendors.files', 'items.quotes']);
        });
    }

    public function submit(PurchaseComparison $sheet, User $actor): PurchaseComparison
    {
        if (! $sheet->isEditable()) {
            throw ValidationException::withMessages(['status' => 'Only a draft or rejected sheet can be sent for approval.']);
        }

        $sheet->update([
            'status' => PurchaseComparison::PENDING,
            'updated_by' => $actor->id,
        ]);
        $this->event($sheet, $actor, 'submitted', null);

        return $sheet;
    }

    public function approve(PurchaseComparison $sheet, User $actor, ?string $remarks = null): PurchaseComparison
    {
        if ($sheet->status !== PurchaseComparison::PENDING) {
            throw ValidationException::withMessages(['status' => 'Only a sheet waiting for approval can be approved.']);
        }

        $this->assertReadyForApproval($sheet);

        $sheet->update([
            'status' => PurchaseComparison::APPROVED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'executive_remarks' => $remarks ?: $sheet->executive_remarks,
            'rejection_reason' => null,
            'updated_by' => $actor->id,
        ]);
        $this->event($sheet, $actor, 'approved', $remarks);

        return $sheet;
    }

    public function reject(PurchaseComparison $sheet, User $actor, string $reason): PurchaseComparison
    {
        if ($sheet->status !== PurchaseComparison::PENDING) {
            throw ValidationException::withMessages(['status' => 'Only a sheet waiting for approval can be rejected.']);
        }

        $sheet->update([
            'status' => PurchaseComparison::REJECTED,
            'rejection_reason' => $reason,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'updated_by' => $actor->id,
        ]);
        $this->event($sheet, $actor, 'rejected', $reason);

        return $sheet;
    }

    /**
     * @return list<PurchaseComparisonOrder>
     */
    public function convertToPurchaseOrders(PurchaseComparison $sheet, User $actor): array
    {
        if ($sheet->status !== PurchaseComparison::APPROVED) {
            throw ValidationException::withMessages(['status' => 'Approve the comparison before creating purchase orders.']);
        }

        if ($sheet->orders()->exists()) {
            throw ValidationException::withMessages(['orders' => 'Purchase orders have already been created from this sheet.']);
        }

        $sheet->load(['vendors', 'items.quotes', 'items.product']);
        $this->assertReadyForApproval($sheet);

        return DB::transaction(function () use ($sheet, $actor) {
            $created = [];
            $numbers = [];
            $grouped = $sheet->items->groupBy('selected_vendor_id');

            foreach ($grouped as $vendorId => $items) {
                $vendor = $sheet->vendors->firstWhere('id', (int) $vendorId);
                if (! $vendor) {
                    throw ValidationException::withMessages(['items' => 'One of the selected vendors is missing.']);
                }

                $lines = [];
                $freight = 0.0;
                foreach ($items as $item) {
                    if (! $item->product_id) {
                        throw ValidationException::withMessages([
                            'items' => "Link {$item->item_name} to a product before creating the purchase order.",
                        ]);
                    }

                    $product = $item->product ?: Product::query()->find($item->product_id);
                    $uomId = $item->uom_id ?: $product?->base_uom_id;
                    if (! $uomId) {
                        throw ValidationException::withMessages([
                            'items' => "{$item->item_name} needs a unit of measure before it can become a purchase order line.",
                        ]);
                    }

                    $quote = $item->quotes->firstWhere('purchase_comparison_vendor_id', $vendor->id);
                    $math = $quote ? $this->math->measure($quote->toArray()) : null;
                    $rate = $item->final_rate !== null ? (float) $item->final_rate : (float) ($math['net_rate'] ?? 0);
                    $lineFreight = $item->final_freight !== null ? (float) $item->final_freight : (float) ($math['freight'] ?? 0);
                    $freight += $lineFreight;

                    $lines[] = [
                        'product_id' => $item->product_id,
                        'uom_id' => $uomId,
                        'quantity' => (float) $item->required_quantity,
                        'unit_cost' => $rate,
                        'tax_percent' => (float) ($quote->gst_rate ?? 0),
                    ];
                }

                $order = $this->purchaseOrders->create([
                    'supplier_id' => $vendor->supplier_id,
                    'company_id' => $sheet->company_id,
                    'branch_id' => $sheet->branch_id,
                    'po_date' => now()->toDateString(),
                    'expected_date' => $sheet->required_delivery_date?->toDateString(),
                    'billing_address' => $vendor->billing_address,
                    'shipping_address' => $vendor->dispatch_address,
                    'notes' => 'Created from purchase comparison '.$sheet->number,
                    'is_freight_applicable' => $freight > 0,
                    'freight_amount' => $freight,
                    'items' => $lines,
                ], $actor);

                $created[] = PurchaseComparisonOrder::create([
                    'purchase_comparison_id' => $sheet->id,
                    'purchase_comparison_vendor_id' => $vendor->id,
                    'purchase_order_id' => $order->id,
                ]);
                $numbers[] = $order->po_no;
            }

            $this->event($sheet, $actor, 'po_created', implode(', ', $numbers));

            return $created;
        });
    }

    /**
     * @return array{vendors: mixed, rows: list<array<string, mixed>>}
     */
    public function present(PurchaseComparison $sheet): array
    {
        $sheet->loadMissing(['vendors.supplier', 'vendors.files', 'items.quotes', 'items.product', 'items.selectedVendor.supplier']);

        $rows = [];
        foreach ($sheet->items as $item) {
            $cells = [];
            $landed = [];
            $basic = [];
            $nets = [];
            $gst = [];
            $delivery = [];

            foreach ($sheet->vendors as $vendor) {
                $quote = $item->quotes->firstWhere('purchase_comparison_vendor_id', $vendor->id);
                $math = $quote ? $this->math->measure($quote->toArray()) : null;
                if ($math) {
                    $landed[$vendor->id] = $math['landed'];
                    $basic[$vendor->id] = $math['basic_rate'];
                    $nets[$vendor->id] = $math['net_rate'];
                    $gst[] = number_format($math['gst_rate'], 2, '.', '');
                    $delivery[] = trim(($quote->delivery_time ?? '').'|'.($quote->delivery_date?->toDateString() ?? ''));
                }
                $cells[$vendor->id] = ['quote' => $quote, 'math' => $math];
            }

            $minLanded = $landed === [] ? null : min($landed);
            $maxLanded = $landed === [] ? null : max($landed);
            $minBasic = $basic === [] ? null : min($basic);
            $maxBasic = $basic === [] ? null : max($basic);
            $minNet = $nets === [] ? null : min($nets);
            $gstDiffers = count(array_unique($gst)) > 1;
            $deliveryDiffers = count(array_unique($delivery)) > 1;
            $lowestVendor = null;

            foreach ($cells as $vendorId => $cell) {
                $math = $cell['math'];
                $quote = $cell['quote'];
                $flags = [];
                if ($math && $quote && abs((float) $quote->quoted_quantity - (float) $item->required_quantity) > 0.0001) {
                    $flags[] = 'quantity';
                }
                if ($math && $gstDiffers) {
                    $flags[] = 'gst';
                }
                if ($math && $deliveryDiffers) {
                    $flags[] = 'delivery';
                }
                $isLowest = $math && $minLanded !== null && abs($math['landed'] - $minLanded) < 0.009;
                $cells[$vendorId]['flags'] = $flags;
                $cells[$vendorId]['is_lowest'] = $isLowest;
                $cells[$vendorId]['is_lowest_basic'] = $math && $minBasic !== null && abs($math['basic_rate'] - $minBasic) < 0.0001;
                $cells[$vendorId]['is_highest_basic'] = $math && $maxBasic !== null && abs($math['basic_rate'] - $maxBasic) < 0.0001;
                $cells[$vendorId]['is_lowest_net'] = $math && $minNet !== null && abs($math['net_rate'] - $minNet) < 0.0001;
                $cells[$vendorId]['gap'] = $math && $minLanded !== null ? round($math['landed'] - $minLanded, 2) : null;
                $cells[$vendorId]['saving'] = ($math && $maxLanded) ? round((($maxLanded - $math['landed']) / $maxLanded) * 100, 2) : null;
                if ($isLowest && $lowestVendor === null) {
                    $lowestVendor = $sheet->vendors->firstWhere('id', $vendorId)?->supplier?->name;
                }
            }

            $rows[] = [
                'item' => $item,
                'cells' => $cells,
                'min_landed' => $minLanded,
                'max_landed' => $maxLanded,
                'min_basic' => $minBasic,
                'max_basic' => $maxBasic,
                'min_net' => $minNet,
                'lowest_vendor' => $lowestVendor,
            ];
        }

        return ['vendors' => $sheet->vendors, 'rows' => $rows];
    }

    public function supplierSnapshot(Customer $supplier): array
    {
        $billing = $supplier->defaultBillingAddress();
        $dispatch = $supplier->activeAddresses()->where('is_default_delivery', true)->first() ?: $billing;
        $contact = $supplier->contacts()->where('is_active', true)->orderByDesc('is_primary')->first();

        return [
            'supplier_id' => $supplier->id,
            'name' => $supplier->name,
            'billing_address' => $billing?->formatSnapshot() ?: ($supplier->address ?? ''),
            'dispatch_address' => $dispatch?->formatSnapshot() ?: ($supplier->address ?? ''),
            'contact_person' => $contact?->name ?? '',
            'mobile' => $contact?->phone ?: ($supplier->phone ?? ''),
            'email' => $contact?->email ?: ($supplier->email ?? ''),
            'gstin' => $supplier->gstin ?? '',
        ];
    }

    private function assertReadyForApproval(PurchaseComparison $sheet): void
    {
        $presented = $this->present($sheet);
        if ($sheet->vendors()->count() < 2) {
            throw ValidationException::withMessages(['vendors' => 'A comparison needs at least two vendors.']);
        }

        foreach ($presented['rows'] as $row) {
            /** @var PurchaseComparisonItem $item */
            $item = $row['item'];
            if (! $item->selected_vendor_id) {
                throw ValidationException::withMessages([
                    'items' => "Choose a vendor for {$item->item_name}.",
                ]);
            }

            $cell = $row['cells'][$item->selected_vendor_id] ?? null;
            if (! $cell || ! $cell['math']) {
                throw ValidationException::withMessages([
                    'items' => "{$item->item_name} is awarded to a vendor who did not quote it.",
                ]);
            }

            if (! $cell['is_lowest'] && blank($item->selection_reason)) {
                throw ValidationException::withMessages([
                    'items' => "Give a reason for choosing a higher landed cost on {$item->item_name}.",
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<int, UploadedFile>>  $files
     * @param  list<int>  $removeFileIds
     */
    private function replaceChildren(PurchaseComparison $sheet, array $data, array $files, array $removeFileIds): void
    {
        $keptOrders = $sheet->orders()->with('vendor')->get()->map(fn ($order) => [
            'supplier_id' => $order->vendor?->supplier_id,
            'purchase_order_id' => $order->purchase_order_id,
        ]);

        $vendorIds = $sheet->vendors()->pluck('id');
        $keptFiles = PurchaseComparisonFile::query()
            ->whereIn('purchase_comparison_vendor_id', $vendorIds)
            ->when($removeFileIds !== [], fn ($query) => $query->whereNotIn('id', $removeFileIds))
            ->get()
            ->groupBy('purchase_comparison_vendor_id');

        if ($removeFileIds !== []) {
            PurchaseComparisonFile::query()
                ->whereIn('id', $removeFileIds)
                ->whereIn('purchase_comparison_vendor_id', $vendorIds)
                ->get()
                ->each(function (PurchaseComparisonFile $file) {
                    Storage::disk('local')->delete($file->path);
                    $file->delete();
                });
        }

        PurchaseComparisonItem::query()->where('purchase_comparison_id', $sheet->id)->update(['selected_vendor_id' => null]);
        PurchaseComparisonQuote::query()->whereIn('purchase_comparison_item_id', $sheet->items()->pluck('id'))->delete();
        PurchaseComparisonItem::query()->where('purchase_comparison_id', $sheet->id)->delete();
        $sheet->vendors()->delete();

        $vendorModels = [];
        foreach (array_values($data['vendors']) as $index => $vendor) {
            $supplier = Customer::query()->findOrFail($vendor['supplier_id']);
            $snapshot = $this->supplierSnapshot($supplier);
            $vendorModels[$index] = PurchaseComparisonVendor::create([
                'purchase_comparison_id' => $sheet->id,
                'supplier_id' => $supplier->id,
                'sort_order' => $index + 1,
                'billing_address' => $vendor['billing_address'] ?: $snapshot['billing_address'],
                'dispatch_address' => $vendor['dispatch_address'] ?: $snapshot['dispatch_address'],
                'contact_person' => $vendor['contact_person'] ?: $snapshot['contact_person'],
                'mobile' => $vendor['mobile'] ?: $snapshot['mobile'],
                'email' => $vendor['email'] ?: $snapshot['email'],
                'gstin' => $vendor['gstin'] ?: $snapshot['gstin'],
                'quotation_number' => $vendor['quotation_number'] ?? null,
                'quotation_date' => $vendor['quotation_date'] ?? null,
                'valid_until' => $vendor['valid_until'] ?? null,
                'credit_period' => $vendor['credit_period'] ?? null,
                'advance_payment' => $vendor['advance_payment'] ?? null,
                'credit_limit_note' => $vendor['credit_limit_note'] ?? null,
                'replacement_policy' => $vendor['replacement_policy'] ?? null,
                'return_policy' => $vendor['return_policy'] ?? null,
                'installation_support' => $vendor['installation_support'] ?? null,
                'transporter' => $vendor['transporter'] ?? null,
                'freight_paid_by' => $vendor['freight_paid_by'] ?? 'vendor',
                'delivery_location' => $vendor['delivery_location'] ?? null,
                'partial_delivery' => ! empty($vendor['partial_delivery']),
            ]);

            foreach ($keptFiles->get($vendor['id'] ?? 0, []) as $file) {
                PurchaseComparisonFile::create([
                    'purchase_comparison_vendor_id' => $vendorModels[$index]->id,
                    'path' => $file->path,
                    'original_name' => $file->original_name,
                    'mime' => $file->mime,
                ]);
            }

            foreach ($files[$index] ?? [] as $upload) {
                if (! $upload instanceof UploadedFile) {
                    continue;
                }
                $path = $upload->store('purchase-comparisons/'.$sheet->id, 'local');
                PurchaseComparisonFile::create([
                    'purchase_comparison_vendor_id' => $vendorModels[$index]->id,
                    'path' => $path,
                    'original_name' => $upload->getClientOriginalName(),
                    'mime' => $upload->getClientMimeType(),
                ]);
            }
        }

        foreach ($keptOrders as $order) {
            $vendor = collect($vendorModels)->first(fn ($row) => (int) $row->supplier_id === (int) $order['supplier_id']);
            if (! $vendor || ! $order['purchase_order_id']) {
                continue;
            }
            PurchaseComparisonOrder::create([
                'purchase_comparison_id' => $sheet->id,
                'purchase_comparison_vendor_id' => $vendor->id,
                'purchase_order_id' => $order['purchase_order_id'],
            ]);
        }

        foreach (array_values($data['items']) as $index => $item) {
            $selectedIndex = $item['selected_vendor_index'] ?? null;
            $selected = ($selectedIndex !== null && $selectedIndex !== '' && isset($vendorModels[(int) $selectedIndex]))
                ? $vendorModels[(int) $selectedIndex]
                : null;

            $record = PurchaseComparisonItem::create([
                'purchase_comparison_id' => $sheet->id,
                'sort_order' => $index + 1,
                'category_name' => $item['category_name'] ?? null,
                'subcategory_name' => $item['subcategory_name'] ?? null,
                'brand_name' => $item['brand_name'] ?? null,
                'product_id' => $item['product_id'] ?: null,
                'uom_id' => $item['uom_id'] ?: null,
                'item_name' => $item['item_name'],
                'model_sku' => $item['model_sku'] ?? null,
                'description' => $item['description'] ?? null,
                'required_quantity' => $item['required_quantity'],
                'selected_vendor_id' => $selected?->id,
                'selection_reason' => $item['selection_reason'] ?? null,
                'final_rate' => $item['final_rate'] !== '' && $item['final_rate'] !== null ? $item['final_rate'] : null,
                'final_freight' => $item['final_freight'] !== '' && $item['final_freight'] !== null ? $item['final_freight'] : null,
                'final_amount' => $item['final_amount'] !== '' && $item['final_amount'] !== null ? $item['final_amount'] : null,
            ]);

            foreach (array_values($item['quotes'] ?? []) as $quoteIndex => $quote) {
                $vendor = $vendorModels[$quoteIndex] ?? null;
                if (! $vendor) {
                    continue;
                }
                PurchaseComparisonQuote::create([
                    'purchase_comparison_item_id' => $record->id,
                    'purchase_comparison_vendor_id' => $vendor->id,
                    'is_quoted' => ! empty($quote['is_quoted']),
                    'quoted_quantity' => $quote['quoted_quantity'] !== '' && $quote['quoted_quantity'] !== null ? $quote['quoted_quantity'] : null,
                    'basic_rate' => $quote['basic_rate'] !== '' && $quote['basic_rate'] !== null ? $quote['basic_rate'] : null,
                    'discount_type' => $quote['discount_type'] ?? 'percent',
                    'discount_value' => $quote['discount_value'] ?? 0,
                    'gst_rate' => $quote['gst_rate'] ?? 0,
                    'freight_type' => $quote['freight_type'] ?? 'free',
                    'freight_mode' => $quote['freight_mode'] ?? 'amount',
                    'freight_value' => $quote['freight_value'] ?? 0,
                    'packing_charges' => $quote['packing_charges'] ?? 0,
                    'insurance_charges' => $quote['insurance_charges'] ?? 0,
                    'other_charges' => $quote['other_charges'] ?? 0,
                    'warranty_period' => $quote['warranty_period'] ?? null,
                    'warranty_type' => $quote['warranty_type'] ?? null,
                    'stock_availability' => $quote['stock_availability'] ?? null,
                    'delivery_time' => $quote['delivery_time'] ?? null,
                    'delivery_date' => $quote['delivery_date'] ?: null,
                    'payment_terms' => $quote['payment_terms'] ?? null,
                    'price_validity' => $quote['price_validity'] ?? null,
                    'country_of_origin' => $quote['country_of_origin'] ?? null,
                    'remarks' => $quote['remarks'] ?? null,
                ]);
            }
        }
    }

    private function nextNumber(): string
    {
        $latest = PurchaseComparison::query()->lockForUpdate()->orderByDesc('id')->value('number');
        $next = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $match)) {
            $next = ((int) $match[1]) + 1;
        }

        return 'PCS-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function event(PurchaseComparison $sheet, User $actor, string $action, ?string $note): void
    {
        PurchaseComparisonEvent::create([
            'purchase_comparison_id' => $sheet->id,
            'user_id' => $actor->id,
            'action' => $action,
            'note' => $note,
        ]);
    }
}
