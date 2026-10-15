<?php

namespace App\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialYear extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'starts_on',
        'ends_on',
        'is_closed',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_closed' => 'boolean',
            'is_current' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function containsDate($date): bool
    {
        $d = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return $d >= $this->starts_on->toDateString() && $d <= $this->ends_on->toDateString();
    }

    public function getDeleteBlockReason(): ?string
    {
        // 1. Check if document sequences have been used in this financial year
        if (\Illuminate\Support\Facades\Schema::hasTable('document_sequences')) {
            $usedSequences = \Illuminate\Support\Facades\DB::table('document_sequences')
                ->where('financial_year_id', $this->id)
                ->where('last_number', '>', 0)
                ->count();

            if ($usedSequences > 0) {
                return 'Cannot delete financial year because document sequences have already issued numbered documents.';
            }
        }

        // 2. Check if accounting vouchers or financial transactions exist within this financial year period for the same company
        if (\Illuminate\Support\Facades\Schema::hasTable('acct_vouchers')) {
            $hasVouchers = \Illuminate\Support\Facades\DB::table('acct_vouchers')
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
        if (\Illuminate\Support\Facades\Schema::hasTable('acct_invoices')) {
            $hasInvoices = \Illuminate\Support\Facades\DB::table('acct_invoices')
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
