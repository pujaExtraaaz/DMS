<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Database\Factories\TaxRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRate extends AccountingModel
{
    /** @use HasFactory<TaxRateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'tax_category_id',
        'name',
        'code',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'cess_rate',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cgst_rate' => 'decimal:4',
            'sgst_rate' => 'decimal:4',
            'igst_rate' => 'decimal:4',
            'cess_rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class, 'tax_category_id');
    }

    public function hsnSacs(): HasMany
    {
        return $this->hasMany(HsnSac::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function rateLabel(): string
    {
        return 'CGST '.$this->trimmed('cgst_rate')
            .' · SGST '.$this->trimmed('sgst_rate')
            .' · IGST '.$this->trimmed('igst_rate')
            .' · Cess '.$this->trimmed('cess_rate');
    }

    public function trimmed(string $attribute): string
    {
        $value = (string) $this->getAttribute($attribute);

        if (! str_contains($value, '.')) {
            return $value;
        }

        $trimmed = rtrim(rtrim($value, '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }

    public function canBeDeleted(): bool
    {
        return ! $this->hsnSacs()->exists()
            && ! $this->products()->exists()
            && ! $this->invoiceLines()->exists();
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
