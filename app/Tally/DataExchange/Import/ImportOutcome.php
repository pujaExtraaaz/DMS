<?php

namespace Tally\DataExchange\Import;

class ImportOutcome
{
    /**
     * @param  list<array{row: int, message: string}>  $errors
     */
    public function __construct(
        public readonly bool $imported,
        public readonly int $count,
        public readonly array $errors,
    ) {}

    /**
     * @param  list<array{row: int, message: string}>  $errors
     */
    public static function rejected(array $errors): self
    {
        return new self(false, 0, $errors);
    }

    public static function imported(int $count): self
    {
        return new self(true, $count, []);
    }
}
