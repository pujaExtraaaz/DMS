<?php

namespace Tests\Unit\Master;

use App\Domains\Master\Models\PriceMaster;
use PHPUnit\Framework\TestCase;

class PriceMasterRateTest extends TestCase
{
    public function test_exclusive_rate_adds_product_gst(): void
    {
        $split = PriceMaster::splitRate(100, false, 18);

        $this->assertSame(100.0, $split['exclusive']);
        $this->assertSame(18.0, $split['gst']);
        $this->assertSame(118.0, $split['final']);
    }

    public function test_inclusive_rate_removes_product_gst(): void
    {
        $split = PriceMaster::splitRate(118, true, 18);

        $this->assertSame(100.0, $split['exclusive']);
        $this->assertSame(18.0, $split['gst']);
        $this->assertSame(118.0, $split['final']);
    }

    public function test_zero_gst_keeps_the_entered_rate(): void
    {
        $split = PriceMaster::splitRate(250, true, 0);

        $this->assertSame(250.0, $split['exclusive']);
        $this->assertSame(0.0, $split['gst']);
        $this->assertSame(250.0, $split['final']);
    }
}
