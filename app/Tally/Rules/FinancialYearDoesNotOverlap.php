<?php

namespace Tally\Rules;

use Tally\Models\FinancialYear;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class FinancialYearDoesNotOverlap implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(
        private readonly int $companyId,
        private readonly ?int $ignoreId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $start = $this->data['start_date'] ?? null;
        $end = $this->data['end_date'] ?? null;

        if (! is_string($start) || ! is_string($end) || $start === '' || $end === '') {
            return;
        }

        $overlaps = FinancialYear::query()
            ->overlapping($this->companyId, $start, $end, $this->ignoreId)
            ->exists();

        if ($overlaps) {
            $fail('This financial year overlaps another year for the same company.');
        }
    }
}
