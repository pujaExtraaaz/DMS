<?php

namespace Tests\Unit\Tally\Reporting;

use Tests\TestCase;
use Tally\Reporting\BreakEvenReport;

class BreakEvenReportTest extends TestCase
{
    public function test_unit_mode_break_even(): void
    {
        $result = app(BreakEvenReport::class)->calculate([
            'fixed_costs' => '100000',
            'price_per_unit' => '500',
            'variable_cost_per_unit' => '300',
        ]);

        $this->assertSame('unit', $result['mode']);
        $this->assertSame('500.0000', $result['break_even_units']);
        $this->assertSame('250000.00', $result['break_even_revenue']);
    }

    public function test_margin_mode_break_even(): void
    {
        $result = app(BreakEvenReport::class)->calculate([
            'fixed_costs' => '100000',
            'contribution_margin_percent' => '40',
        ]);

        $this->assertSame('margin', $result['mode']);
        $this->assertSame('250000.00', $result['break_even_revenue']);
    }
}
