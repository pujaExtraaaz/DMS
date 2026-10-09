<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\AccountNature;
use Tally\Integration\Concerns\HasExternalReference;
use Database\Factories\AccountGroupFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountGroup extends AccountingModel
{
    /** @use HasFactory<AccountGroupFactory> */
    use HasExternalReference, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'code',
        'nature',
        'sort_order',
        'is_system',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nature' => AccountNature::class,
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }

    public function isDescendantOf(self $ancestor): bool
    {
        $parentId = $this->parent_id;
        $guard = 0;

        while ($parentId && $guard < 50) {
            if ($parentId === $ancestor->id) {
                return true;
            }

            $parentId = static::query()->whereKey($parentId)->value('parent_id');
            $guard++;
        }

        return false;
    }

    public function canBeDeleted(): bool
    {
        if ($this->is_system) {
            return false;
        }

        $noChildren = array_key_exists('children_count', $this->getAttributes())
            ? (int) $this->children_count === 0
            : ! $this->children()->exists();
        $noLedgers = array_key_exists('ledgers_count', $this->getAttributes())
            ? (int) $this->ledgers_count === 0
            : ! $this->ledgers()->exists();

        return $noChildren && $noLedgers;
    }

    public function applyNatureToDescendants(): void
    {
        foreach ($this->children()->get() as $child) {
            if ($child->nature !== $this->nature) {
                $child->nature = $this->nature;
                $child->save();
            }

            $child->applyNatureToDescendants();
        }
    }

    /**
     * @param  Collection<int, self>  $groups
     * @return Collection<int, array{group: self, depth: int}>
     */
    public static function flatten(Collection $groups): Collection
    {
        $grouped = $groups->groupBy(fn (self $group) => $group->parent_id ?? 0);
        $rows = new Collection;
        $walk = function (int $parentId, int $depth) use (&$walk, $grouped, $rows): void {
            $children = ($grouped->get($parentId) ?? new Collection)
                ->sortBy([['sort_order', 'asc'], ['name', 'asc']])
                ->values();

            foreach ($children as $group) {
                $rows->push([
                    'group' => $group,
                    'depth' => $depth,
                ]);
                $walk($group->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $rows;
    }
}
