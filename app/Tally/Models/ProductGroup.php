<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Database\Factories\ProductGroupFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductGroup extends AccountingModel
{
    /** @use HasFactory<ProductGroupFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'code',
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isDescendantOf(self $ancestor): bool
    {
        $parentId = $this->parent_id;
        $guard = 0;

        while ($parentId && $guard < 50) {
            if ((int) $parentId === $ancestor->id) {
                return true;
            }

            $parentId = static::query()->whereKey($parentId)->value('parent_id');
            $guard++;
        }

        return false;
    }

    public static function suggestCode(Company $company, ?self $except = null): string
    {
        $used = $company->productGroups()
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->pluck('code');
        $max = 0;

        foreach ($used as $code) {
            if (preg_match('/^PG(\d+)$/', (string) $code, $match) === 1) {
                $max = max($max, (int) $match[1]);
            }
        }

        $next = $max + 1;

        do {
            $code = 'PG'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            $next++;
        } while ($used->contains($code));

        return $code;
    }

    public function canBeDeleted(): bool
    {
        $noChildren = array_key_exists('children_count', $this->getAttributes())
            ? (int) $this->children_count === 0
            : ! $this->children()->exists();
        $noProducts = array_key_exists('products_count', $this->getAttributes())
            ? (int) $this->products_count === 0
            : ! $this->products()->exists();

        return $noChildren && $noProducts;
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
                ->sortBy('name')
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
