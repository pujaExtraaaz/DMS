<?php

namespace Tests\Unit\Tally;

use PHPUnit\Framework\TestCase;
use Tally\Accounting\VoucherNumberFormat;

class VoucherNumberFormatTest extends TestCase
{
    public function test_prefix_padding_and_suffix(): void
    {
        $this->assertSame('REC-000001/26', VoucherNumberFormat::make('rec', '/26', 6, 1));
    }

    public function test_number_without_prefix(): void
    {
        $this->assertSame('0007', VoucherNumberFormat::make('', null, 4, 7));
    }
}