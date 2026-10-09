<?php

namespace Tally\Rules;

use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AccountGroupParentIsValid implements ValidationRule
{
    public function __construct(
        private readonly Company $company,
        private readonly ?AccountGroup $group = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $parent = AccountGroup::query()
            ->where('company_id', $this->company->id)
            ->whereKey($value)
            ->first();

        if (! $parent) {
            $fail('Select a group from the current company.');

            return;
        }

        if ($this->group && ($parent->is($this->group) || $parent->isDescendantOf($this->group))) {
            $fail('That parent would create a circular group hierarchy.');

            return;
        }

        if ($this->group?->is_system) {
            $fail('The parent of a system group cannot be changed.');
        }
    }
}
