<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Integration\Concerns\HasExternalReference;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends AccountingModel
{
    /** @use HasFactory<BranchFactory> */
    use HasExternalReference, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'phone',
        'email',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function canBeDeleted(): bool
    {
        return ! Voucher::query()->where('branch_id', $this->id)->exists()
            && ! Invoice::query()->where('branch_id', $this->id)->exists()
            && ! StockTransaction::query()->where('branch_id', $this->id)->exists()
            && ! ManufacturingOrder::query()->where('branch_id', $this->id)->exists();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function locationLabel(): string
    {
        return collect([$this->city, $this->state, $this->country])
            ->filter(fn (?string $part) => filled($part))
            ->implode(', ');
    }
}
