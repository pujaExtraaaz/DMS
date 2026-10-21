<?php

namespace App\Domains\Purchasing\Models;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseComparison extends Model
{
    public const DRAFT = 'draft';

    public const PENDING = 'pending_approval';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    protected $fillable = [
        'company_id',
        'branch_id',
        'number',
        'comparison_date',
        'prepared_by',
        'updated_by',
        'indent_no',
        'customer_project',
        'required_delivery_date',
        'remarks',
        'status',
        'executive_remarks',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'comparison_date' => 'date',
        'required_delivery_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(PurchaseComparisonVendor::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseComparisonItem::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PurchaseComparisonEvent::class)->latest();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PurchaseComparisonOrder::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::DRAFT, self::REJECTED], true);
    }
}
