<?php

namespace App\Domains\Master\Models;

use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'party_type',
        'customer_type_id',
        'area_id',
        'route_id',
        'salesperson_id',
        'phone',
        'email',
        'address',
        'state',
        'pincode',
        'shipping_name',
        'shipping_address',
        'shipping_state',
        'shipping_pincode',
        'shipping_gstin',
        'gstin',
        'credit_limit',
        'credit_days',
        'interest_rate',
        'credit_period_basis',
        'payment_terms',
        'credit_status',
        'risk_status',
        'cheque_bounce_count',
        'credit_notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
            'interest_rate' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PartyContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartyAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function outstandingLedgerEntries(): HasMany
    {
        return $this->hasMany(OutstandingLedger::class);
    }

    public function isFrozen(): bool
    {
        return $this->credit_status === 'frozen';
    }

    public function isSupplier(): bool
    {
        return in_array($this->party_type, ['supplier', 'both'], true);
    }

    public function isCustomerParty(): bool
    {
        return in_array($this->party_type, ['customer', 'dealer', 'both'], true);
    }
}
