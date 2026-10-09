<?php

namespace Tally\Tax;

final class DeductionResult
{
    public function __construct(
        public readonly string $base,
        public readonly string $deduction,
        public readonly bool $thresholdCrossed,
        public readonly string $rate,
    ) {}
}
