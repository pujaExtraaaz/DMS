<?php

namespace App\Domains\Organization\Models;

use App\Models\User;
use App\Support\Traits\HasUppercaseNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialYear extends Model
{
    use HasUppercaseNames;

    protected $fillable = [
        'company_id',
        'name',
        'starts_on',
        'ends_on',
        'is_closed',
        'is_current',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_closed' => 'boolean',
            'is_current' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function containsDate($date): bool
    {
        $d = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return $d >= $this->starts_on->toDateString() && $d <= $this->ends_on->toDateString();
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeCurrent($query, ?int $companyId = null)
    {
        return $query->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_current', true);
    }

    public static function current(?int $companyId = null): ?self
    {
        return static::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_current', true)
            ->first()
            ?? static::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('starts_on', '<=', now()->toDateString())
                ->whereDate('ends_on', '>=', now()->toDateString())
                ->first()
            ?? static::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderByDesc('starts_on')
                ->first();
    }

    public function scopeOverlapping($query, int $companyId, string $startsOn, string $endsOn, ?int $ignoreId = null)
    {
        return $query->where('company_id', $companyId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('starts_on', '<=', $endsOn)
            ->whereDate('ends_on', '>=', $startsOn);
    }

    public function nextYear(): ?self
    {
        return static::query()
            ->where('company_id', $this->company_id)
            ->whereDate('starts_on', '>', $this->ends_on->toDateString())
            ->orderBy('starts_on')
            ->first();
    }

    public function getCloseBlockReason(): ?string
    {
        if ($this->is_closed) {
            return 'Financial year is already closed.';
        }

        if (! $this->nextYear()) {
            return 'Create the next financial year for this company before closing.';
        }

        // Soft pending checks — drafts that would still need attention.
        if (
            Schema::hasTable('purchase_orders')
            && Schema::hasColumn('purchase_orders', 'status')
            && Schema::hasColumn('purchase_orders', 'company_id')
            && Schema::hasColumn('purchase_orders', 'po_date')
        ) {
            $pendingPo = DB::table('purchase_orders')
                ->where('company_id', $this->company_id)
                ->whereBetween('po_date', [$this->starts_on->toDateString(), $this->ends_on->toDateString()])
                ->whereIn('status', ['draft', 'pending_approval'])
                ->exists();
            if ($pendingPo) {
                return 'Cannot close financial year while draft/pending purchase orders exist in this period.';
            }
        }

        if (
            Schema::hasTable('purchase_invoices')
            && Schema::hasColumn('purchase_invoices', 'status')
            && Schema::hasColumn('purchase_invoices', 'company_id')
            && Schema::hasColumn('purchase_invoices', 'invoice_date')
        ) {
            $pendingPi = DB::table('purchase_invoices')
                ->where('company_id', $this->company_id)
                ->whereBetween('invoice_date', [$this->starts_on->toDateString(), $this->ends_on->toDateString()])
                ->whereIn('status', ['draft', 'pending_approval'])
                ->exists();
            if ($pendingPi) {
                return 'Cannot close financial year while draft/pending purchase invoices exist in this period.';
            }
        }

        if (
            Schema::hasTable('invoices')
            && Schema::hasColumn('invoices', 'status')
            && Schema::hasColumn('invoices', 'company_id')
            && Schema::hasColumn('invoices', 'invoice_date')
        ) {
            $pendingSi = DB::table('invoices')
                ->where('company_id', $this->company_id)
                ->whereBetween('invoice_date', [$this->starts_on->toDateString(), $this->ends_on->toDateString()])
                ->whereIn('status', ['draft', 'pending'])
                ->exists();
            if ($pendingSi) {
                return 'Cannot close financial year while draft/pending sales invoices exist in this period.';
            }
        }

        return null;
    }

    public function getDeleteBlockReason(): ?string
    {
        // 1. Check if document sequences have been used in this financial year
        if (Schema::hasTable('document_sequences')) {
            $usedSequences = DB::table('document_sequences')
                ->where('financial_year_id', $this->id)
                ->where('last_number', '>', 0)
                ->count();

            if ($usedSequences > 0) {
                return 'Cannot delete financial year because document sequences have already issued numbered documents.';
            }
        }

        // 2. Check if accounting vouchers or financial transactions exist within this financial year period for the same company
        if (Schema::hasTable('acct_vouchers')) {
            $hasVouchers = DB::table('acct_vouchers')
                ->where('company_id', $this->company_id)
                ->where(function ($q) {
                    $q->whereBetween('voucher_date', [$this->starts_on->toDateString(), $this->ends_on->toDateString()])
                      ->orWhere('financial_year_id', $this->id);
                })
                ->exists();

            if ($hasVouchers) {
                return 'Cannot delete financial year because accounting vouchers or financial transactions exist for this period.';
            }
        }

        // 3. Check if accounting invoices exist within this period
        if (Schema::hasTable('acct_invoices')) {
            $hasInvoices = DB::table('acct_invoices')
                ->where('company_id', $this->company_id)
                ->where(function ($q) {
                    $q->whereBetween('invoice_date', [$this->starts_on->toDateString(), $this->ends_on->toDateString()])
                      ->orWhere('financial_year_id', $this->id);
                })
                ->exists();

            if ($hasInvoices) {
                return 'Cannot delete financial year because posted invoices exist for this period.';
            }
        }

        return null;
    }

    public function canBeDeleted(): bool
    {
        return $this->getDeleteBlockReason() === null;
    }
}
