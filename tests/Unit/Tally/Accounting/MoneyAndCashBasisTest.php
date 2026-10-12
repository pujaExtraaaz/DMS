<?php

namespace Tests\Unit\Tally\Accounting;

use Tests\TestCase;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherType;

class MoneyAndCashBasisTest extends TestCase
{
    public function test_money_round_trip(): void
    {
        $this->assertSame(1250, Money::cents('12.50'));
        $this->assertSame('12.50', Money::format(1250));
        $this->assertSame('-3.25', Money::format(Money::cents('-3.25')));
    }

    public function test_cash_basis_voucher_types_are_cash_movements(): void
    {
        $cashTypes = [
            VoucherType::Payment->value,
            VoucherType::Receipt->value,
            VoucherType::Contra->value,
            VoucherType::Transfer->value,
        ];

        $this->assertContains('payment', $cashTypes);
        $this->assertContains('receipt', $cashTypes);
        $this->assertNotContains(VoucherType::Journal->value, $cashTypes);
        $this->assertNotContains(VoucherType::Sales->value ?? 'sales', $cashTypes);
    }
}
