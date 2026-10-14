<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Services\LeadSegmentService;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use App\Support\Concerns\ScopeByBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSegment extends Model
{
    use ScopeByBranch;

    protected $table = 'lead_segments';

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'description',
        'criteria',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function matchingLeadsQuery(): Builder
    {
        return app(LeadSegmentService::class)->query(
            $this->criteria ?? [],
            $this->company_id,
            $this->branch_id
        );
    }

    public function matchingLeadsCount(): int
    {
        return $this->matchingLeadsQuery()->count();
    }

    public function getCriteriaSummaryAttribute(): array
    {
        return app(LeadSegmentService::class)->summarizeCriteria($this->criteria ?? []);
    }
}

