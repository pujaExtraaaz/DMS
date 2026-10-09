<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Tax\HsnKind;
use Database\Factories\HsnSacFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HsnSac extends AccountingModel
{
    /** @use HasFactory<HsnSacFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'tax_rate_id',
        'code',
        'kind',
        'description',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => HsnKind::class,
            'is_active' => 'boolean',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->code);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function label(): string
    {
        $kind = $this->kind instanceof HsnKind ? $this->kind->label() : strtoupper((string) $this->kind);

        return $kind.' '.$this->code.($this->description ? ' · '.$this->description : '');
    }

    public function canBeDeleted(): bool
    {
        return ! $this->products()->exists() && ! $this->invoiceLines()->exists();
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
