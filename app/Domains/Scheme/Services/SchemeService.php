<?php

namespace App\Domains\Scheme\Services;

use App\Domains\Master\Models\Product;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Scheme\Models\Scheme;
use App\Domains\Scheme\Models\SchemeAchievement;
use App\Domains\Scheme\Models\SchemeProduct;
use App\Domains\Scheme\Models\SchemeSettlement;
use App\Domains\Scheme\Models\SchemeSlab;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SchemeService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected AuditLogService $auditLogService,
    ) {}

    public function create(array $data, User $actor): Scheme
    {
        return DB::transaction(function () use ($data, $actor) {
            $scheme = Scheme::create([
                'code' => $data['code'] ?? $this->documentNumberService->next('SCH', now(), 4),
                'name' => $data['name'],
                'scheme_type' => $data['scheme_type'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'status' => $data['status'] ?? 'draft',
                'basis' => $data['basis'] ?? 'value',
                'net_credit_notes' => (bool) ($data['net_credit_notes'] ?? true),
                'notes' => $data['notes'] ?? null,
                'created_by_name' => $this->auditLogService->actorName($actor->name),
            ]);

            foreach ($data['slabs'] ?? [] as $i => $slab) {
                SchemeSlab::create([
                    'scheme_id' => $scheme->id,
                    'from_value' => $slab['from_value'] ?? 0,
                    'to_value' => $slab['to_value'] ?? null,
                    'benefit_percent' => $slab['benefit_percent'] ?? 0,
                    'benefit_amount' => $slab['benefit_amount'] ?? 0,
                    'sort_order' => $slab['sort_order'] ?? $i,
                ]);
            }

            foreach ($data['product_ids'] ?? [] as $productId) {
                SchemeProduct::create([
                    'scheme_id' => $scheme->id,
                    'product_id' => $productId,
                ]);
            }

            $this->auditLogService->record($scheme, 'created', $actor->name);

            return $scheme->load(['slabs', 'products.product', 'brand']);
        });
    }

    /**
     * Provisional achievement on invoice — snapshot slab rates so later scheme edits do not rewrite history.
     */
    public function provisionalForInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['items.product', 'customer']);

        if (in_array($invoice->status, ['cancelled', 'draft'], true) || empty($invoice->scheme_id)) {
            return [];
        }

        $schemes = Scheme::query()
            ->with(['slabs', 'products'])
            ->where('id', $invoice->scheme_id)
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', $invoice->invoice_date)
            ->whereDate('ends_on', '>=', $invoice->invoice_date)
            ->get();

        $created = [];

        foreach ($schemes as $scheme) {
            $productIds = $scheme->products->pluck('product_id')->filter()->all();
            $items = $invoice->items;

            if ($productIds !== []) {
                $items = $items->whereIn('product_id', $productIds);
            } elseif ($scheme->brand_id) {
                $items = $items->filter(fn ($item) => (int) $item->product?->brand_id === (int) $scheme->brand_id);
            }

            $qualified = $scheme->basis === 'quantity'
                ? (float) $items->sum('quantity')
                : (float) $items->sum('line_total');

            if ($scheme->net_credit_notes) {
                $cn = (float) CreditNote::query()
                    ->where('customer_id', $invoice->customer_id)
                    ->where('invoice_id', $invoice->id)
                    ->where('status', 'posted')
                    ->sum('grand_total');
                $qualified = max(0, $qualified - $cn);
            }

            if ($qualified <= 0) {
                continue;
            }

            $slab = $this->matchSlab($scheme, $qualified);
            $benefit = $this->benefitFor($slab, $qualified);

            $achievement = SchemeAchievement::updateOrCreate(
                [
                    'scheme_id' => $scheme->id,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'status' => 'provisional',
                ],
                [
                    'qualified_value' => round($qualified, 2),
                    'benefit_amount' => round($benefit, 2),
                    'snapshot' => [
                        'scheme_code' => $scheme->code,
                        'slab' => $slab?->only(['from_value', 'to_value', 'benefit_percent', 'benefit_amount']),
                        'basis' => $scheme->basis,
                    ],
                    'calculated_at' => now(),
                ]
            );

            $created[] = $achievement;
        }

        return $created;
    }

    public function finalizeScheme(Scheme $scheme): Scheme
    {
        return DB::transaction(function () use ($scheme) {
            if ($scheme->status === 'closed') {
                throw new InvalidArgumentException('Scheme is already closed.');
            }

            $provisional = SchemeAchievement::query()
                ->where('scheme_id', $scheme->id)
                ->where('status', 'provisional')
                ->get();

            foreach ($provisional as $row) {
                // Finalize from snapshot — do not recalculate from current slabs.
                $row->update(['status' => 'final']);

                SchemeSettlement::create([
                    'scheme_id' => $scheme->id,
                    'scheme_achievement_id' => $row->id,
                    'customer_id' => $row->customer_id,
                    'amount' => $row->benefit_amount,
                    'status' => 'pending',
                ]);
            }

            $scheme->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            return $scheme->fresh(['achievements', 'settlements']);
        });
    }

    public function evaluateScheme(Scheme $scheme, string|\DateTimeInterface $invoiceDate, array $items): array
    {
        $scheme->loadMissing(['slabs', 'products']);

        if ($scheme->status !== 'active') {
            return [
                'eligible' => false,
                'reason' => 'Scheme is not active.',
                'benefit' => 0,
                'qualified' => 0,
                'slab' => null,
            ];
        }

        $dateStr = $invoiceDate instanceof \DateTimeInterface ? $invoiceDate->format('Y-m-d') : substr((string) $invoiceDate, 0, 10);
        $starts = $scheme->starts_on ? $scheme->starts_on->format('Y-m-d') : null;
        $ends = $scheme->ends_on ? $scheme->ends_on->format('Y-m-d') : null;

        if (($starts && $dateStr < $starts) || ($ends && $dateStr > $ends)) {
            return [
                'eligible' => false,
                'reason' => "Invoice date ({$dateStr}) is outside the scheme validity window ({$starts} to {$ends}).",
                'benefit' => 0,
                'qualified' => 0,
                'slab' => null,
            ];
        }

        $productIds = $scheme->products->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->all();
        $qualifyingItems = collect($items);

        if ($productIds !== []) {
            $qualifyingItems = $qualifyingItems->filter(fn ($item) => in_array((int) ($item['product_id'] ?? 0), $productIds, true));
        } elseif ($scheme->brand_id) {
            $qualifyingItems = $qualifyingItems->filter(function ($item) use ($scheme) {
                $brandId = $item['brand_id'] ?? null;
                if ($brandId === null && !empty($item['product_id'])) {
                    $brandId = Product::where('id', $item['product_id'])->value('brand_id');
                }
                return (int) $brandId === (int) $scheme->brand_id;
            });
        }

        if ($qualifyingItems->isEmpty()) {
            return [
                'eligible' => false,
                'reason' => 'None of the invoice items belong to the scheme\'s eligible ' . ($productIds !== [] ? 'products' : ($scheme->brand_id ? 'brand' : 'items')) . '.',
                'benefit' => 0,
                'qualified' => 0,
                'slab' => null,
            ];
        }

        $qualified = $scheme->basis === 'quantity'
            ? (float) $qualifyingItems->sum(fn ($i) => (float) ($i['quantity'] ?? 0))
            : (float) $qualifyingItems->sum(function ($i) {
                if (isset($i['line_total'])) {
                    return (float) $i['line_total'];
                }
                $qty = (float) ($i['quantity'] ?? 0);
                $price = (float) ($i['unit_price'] ?? 0);
                $disc = (float) ($i['discount_amount'] ?? 0);
                return max(0, ($qty * $price) - $disc);
            });

        if ($qualified <= 0) {
            return [
                'eligible' => false,
                'reason' => 'Qualified volume must be greater than zero.',
                'benefit' => 0,
                'qualified' => 0,
                'slab' => null,
            ];
        }

        $slab = $this->matchSlab($scheme, $qualified);
        if (! $slab) {
            $minRequired = $scheme->slabs->min('from_value') ?? 0;
            $reason = $qualified < $minRequired
                ? "Minimum qualification slab not reached. Qualified: {$qualified}, required minimum: {$minRequired}."
                : "Qualified volume ({$qualified}) does not fall within any defined slab range.";

            return [
                'eligible' => false,
                'reason' => $reason,
                'benefit' => 0,
                'qualified' => round($qualified, 2),
                'slab' => null,
            ];
        }

        $benefit = $this->benefitFor($slab, $qualified);

        return [
            'eligible' => true,
            'reason' => null,
            'qualified' => round($qualified, 2),
            'benefit' => round($benefit, 2),
            'slab' => $slab,
        ];
    }

    public function applySchemeToInvoice(Invoice $invoice, Scheme $scheme, float $qualified, ?SchemeSlab $slab, float $benefit): SchemeAchievement
    {
        return SchemeAchievement::updateOrCreate(
            [
                'scheme_id' => $scheme->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
            ],
            [
                'qualified_value' => round($qualified, 2),
                'benefit_amount' => round($benefit, 2),
                'snapshot' => [
                    'scheme_code' => $scheme->code,
                    'scheme_name' => $scheme->name,
                    'slab' => $slab?->only(['from_value', 'to_value', 'benefit_percent', 'benefit_amount']),
                    'basis' => $scheme->basis,
                ],
                'status' => 'provisional',
                'calculated_at' => now(),
            ]
        );
    }

    public function matchSlab(Scheme $scheme, float $qualified): ?SchemeSlab
    {
        return $scheme->slabs
            ->sortByDesc('from_value')
            ->first(function (SchemeSlab $slab) use ($qualified) {
                $from = (float) $slab->from_value;
                $to = $slab->to_value !== null ? (float) $slab->to_value : null;

                return $qualified >= $from && ($to === null || $qualified <= $to);
            });
    }

    public function benefitFor(?SchemeSlab $slab, float $qualified): float
    {
        if (! $slab) {
            return 0;
        }

        if ((float) $slab->benefit_amount > 0) {
            return (float) $slab->benefit_amount;
        }

        return round($qualified * ((float) $slab->benefit_percent / 100), 2);
    }
}
