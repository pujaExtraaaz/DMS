<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'po_no',
        'po_date',
        'expected_date',
        'credit_days',
        'due_date',
        'status',
        'is_freight_applicable',
        'freight_mode',
        'freight_percent',
        'freight_amount',
        'freight_tax_rate',
        'freight_tax_amount',
        'freight_total',
        'freight_notes',
        'subtotal',
        'tax_amount',
        'grand_total',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'billing_address_id',
        'shipping_address_id',
        'billing_address',
        'shipping_address',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'expected_date' => 'date',
            'due_date' => 'date',
            'credit_days' => 'integer',
            'approved_at' => 'datetime',
            'is_freight_applicable' => 'boolean',
            'freight_percent' => 'decimal:4',
            'freight_amount' => 'decimal:2',
            'freight_tax_rate' => 'decimal:2',
            'freight_tax_amount' => 'decimal:2',
            'freight_total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    protected $appends = [
        'po_number',
        'order_number',
    ];

    public function getPoNumberAttribute(): ?string
    {
        return $this->po_no;
    }

    public function getOrderNumberAttribute(): ?string
    {
        return $this->po_no;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function inwards(): HasMany
    {
        return $this->hasMany(PurchaseInward::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function freightAllocations(): MorphMany
    {
        return $this->morphMany(FreightBillAllocation::class, 'allocatable');
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, ['approved', 'partially_received'], true);
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Master\Models\PartyAddress::class, 'shipping_address_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval', 'approved'], true)
            && ! $this->inwards()->exists()
            && ! $this->invoices()->exists();
    }

    public function canBeDeleted(): bool
    {
        return $this->getDeleteBlockReason() === null;
    }

    public function getDeleteBlockReason(): ?string
    {
        if (in_array($this->status, ['partially_received', 'closed'], true)) {
            return 'Cannot delete purchase order with status "' . str_replace('_', ' ', $this->status) . '".';
        }

        if ($this->inwards()->exists()) {
            return 'Cannot delete purchase order because goods receipts (GRN) exist for it.';
        }

        if ($this->invoices()->exists()) {
            return 'Cannot delete purchase order because purchase invoices exist for it.';
        }

        if ((float) $this->items()->sum('received_qty') > 0) {
            return 'Cannot delete purchase order because goods have already been received.';
        }

        return null;
    }

    /**
     * Resolve consignee (delivery destination / purchasing company) details.
     *
     * @return array<string, mixed>
     */
    public function resolveConsigneeDetails(?Company $defaultCompany = null): array
    {
        $company = $this->company ?? $defaultCompany ?? Company::resolveCurrent() ?? Company::query()->find(auth()->user()?->company_id) ?? Company::query()->first();

        if (! $company || empty($company->gstin)) {
            $avit = Company::where(function ($q) {
                $q->where('name', 'like', '%Avit%')->orWhere('code', 'AD');
            })->whereNotNull('gstin')->first();
            if ($avit) {
                $company = $avit;
            }
        }

        $companyName = $company?->name ?: 'Avit Digital';
        $companyLegalName = $company?->legal_name ?: $companyName;
        $companyGstin = $company?->gstin ?: '03ABGPG1954N1ZN';
        $companyPan = $company?->pan ?: ($companyGstin && strlen(trim($companyGstin)) >= 12 ? substr(trim($companyGstin), 2, 10) : 'ABGPG1954N');
        $companyPhone = $company?->phone ?: '9876120738';
        $companyEmail = $company?->email ?: 'info@avitdigital.in';
        $companyState = $company?->state ?: 'Punjab';
        $companyAddress = $company?->address;

        $warehouse = $this->warehouse;
        $branch = $warehouse?->branch ?? $this->branch;

        $supplierAddress = trim((string) ($this->supplier?->address ?? ''));
        $billingAddress = trim((string) ($this->billing_address ?? ''));
        $rawShippingAddress = trim((string) ($this->shipping_address ?? ''));

        // Check if shipping_address was accidentally copied from supplier billing address or supplier address
        $isCopiedSupplierAddress = false;
        if ($rawShippingAddress !== '') {
            if ($billingAddress !== '' && strcasecmp($rawShippingAddress, $billingAddress) === 0) {
                $isCopiedSupplierAddress = true;
            } elseif ($supplierAddress !== '' && strcasecmp($rawShippingAddress, $supplierAddress) === 0) {
                $isCopiedSupplierAddress = true;
            }
        }

        $specificDeliveryAddress = ($rawShippingAddress !== '' && ! $isCopiedSupplierAddress)
            ? $rawShippingAddress
            : null;

        $warehouseAddress = trim((string) ($warehouse?->address ?? ''));
        $branchAddress = trim((string) ($branch?->address ?? ''));
        $companyAddressClean = trim((string) ($companyAddress ?? ''));

        $deliveryAddress = $specificDeliveryAddress
            ?: ($warehouseAddress !== '' ? $warehouseAddress
                : ($branchAddress !== '' ? $branchAddress
                    : ($companyAddressClean !== '' ? $companyAddressClean
                        : 'Address not provided')));

        $consigneeGstin = $branch?->gstin ?: ($companyGstin ?: '—');
        $consigneePan = $companyPan ?: ($consigneeGstin !== '—' && strlen($consigneeGstin) >= 12 ? substr($consigneeGstin, 2, 10) : '—');

        $deliveryState = $branch?->state ?: $companyState;
        $deliveryStateCode = ($consigneeGstin !== '—' && strlen(trim($consigneeGstin)) >= 2 && ctype_digit(substr(trim($consigneeGstin), 0, 2)))
            ? substr(trim($consigneeGstin), 0, 2)
            : ($deliveryState ? \App\Support\IndianStates::codeFromName($deliveryState) : null);

        $dispatchPhone = $warehouse?->phone ?? $branch?->phone ?? $companyPhone;

        return [
            'company' => $company,
            'company_name' => $companyName,
            'company_legal_name' => $companyLegalName,
            'company_address' => $companyAddressClean !== '' ? $companyAddressClean : 'Address not provided',
            'warehouse' => $warehouse,
            'warehouse_name' => $warehouse?->name,
            'branch' => $branch,
            'branch_name' => $branch?->name,
            'delivery_address' => $deliveryAddress,
            'gstin' => $consigneeGstin,
            'pan' => $consigneePan,
            'state' => $deliveryState ?: '—',
            'state_code' => $deliveryStateCode,
            'phone' => $dispatchPhone ?: '—',
            'email' => $companyEmail ?: '—',
        ];
    }

    /**
     * Resolve supplier / vendor details.
     *
     * @return array<string, mixed>
     */
    public function resolveSupplierDetails(): array
    {
        $supplier = $this->supplier;
        $supplierContact = $supplier?->contacts?->firstWhere('is_primary', true) ?? $supplier?->contacts?->first();

        $billingAddress = trim((string) ($this->billing_address ?: ($supplier?->address ?: '')));
        $supplierState = $supplier?->state;
        $supplierGstin = $supplier?->gstin;
        $supplierStateCode = ($supplierGstin && strlen(trim($supplierGstin)) >= 2 && ctype_digit(substr(trim($supplierGstin), 0, 2)))
            ? substr(trim($supplierGstin), 0, 2)
            : ($supplierState ? \App\Support\IndianStates::codeFromName($supplierState) : null);

        $supplierPan = $supplier?->pan
            ?: ($supplierGstin && strlen($supplierGstin) >= 12 ? substr($supplierGstin, 2, 10) : '—');

        $contactPerson = $supplierContact?->name ?: '—';
        $phone = $supplierContact?->phone ?: ($supplier?->phone ?: '—');
        $email = $supplierContact?->email ?: ($supplier?->email ?: '—');

        return [
            'supplier' => $supplier,
            'name' => $supplier?->name ?: '—',
            'code' => $supplier?->code,
            'billing_address' => $billingAddress !== '' ? $billingAddress : 'Address not provided',
            'gstin' => $supplierGstin ?: '—',
            'pan' => $supplierPan,
            'state' => $supplierState ?: ($supplierStateCode ? (\App\Support\IndianStates::all()[$supplierStateCode] ?? '—') : '—'),
            'state_code' => $supplierStateCode,
            'contact_person' => $contactPerson,
            'phone' => $phone,
            'email' => $email,
        ];
    }
}
