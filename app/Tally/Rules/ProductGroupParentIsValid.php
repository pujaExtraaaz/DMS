<?php

namespace Tally\Rules;

use Tally\Models\Company;
use Tally\Models\ProductGroup;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProductGroupParentIsValid implements ValidationRule
{
    public function __construct(
        private readonly Company $company,
        private readonly ?ProductGroup $group = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $parent = ProductGroup::query()
            ->where('company_id', $this->company->id)
            ->whereKey($value)
            ->first();

        if (! $parent) {
            $fail('Select a product group from the current company.');

            return;
        }

        if ($this->group && ($parent->is($this->group) || $parent->isDescendantOf($this->group))) {
            $fail('That parent would create a circular product group.');

            return;
        }

        if (! $parent->is_active && (int) $this->group?->parent_id !== $parent->id) {
            $fail('Select an active product group from the current company.');
        }
    }
}
