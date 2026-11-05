<?php

namespace Tests\Unit\Purchasing;

use App\Domains\Purchasing\Services\ComparisonMath;
use PHPUnit\Framework\TestCase;

class ComparisonMathTest extends TestCase
{
    public function test_landed_cost_adds_charges_before_gst_and_ignores_free_freight(): void
    {
        $math = (new ComparisonMath())->measure([
            'is_quoted' => true,
            'quoted_quantity' => 2,
            'basic_rate' => 100,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'gst_rate' => 18,
            'freight_type' => 'free',
            'packing_charges' => 10,
            'insurance_charges' => 0,
            'other_charges' => 0,
        ]);

        $this->assertSame(90.0, $math['net_rate']);
        $this->assertSame(180.0, $math['goods']);
        $this->assertSame(0.0, $math['freight']);
        $this->assertSame(190.0, $math['pre_gst']);
        $this->assertSame(34.2, $math['gst_amount']);
        $this->assertSame(224.2, $math['landed']);
        $this->assertSame(112.1, $math['per_unit']);
    }

    public function test_extra_freight_percent_is_added_before_gst(): void
    {
        $math = (new ComparisonMath())->measure([
            'is_quoted' => true,
            'quoted_quantity' => 1,
            'basic_rate' => 1000,
            'discount_type' => 'amount',
            'discount_value' => 100,
            'gst_rate' => 18,
            'freight_type' => 'extra',
            'freight_mode' => 'percent',
            'freight_value' => 10,
            'packing_charges' => 0,
            'insurance_charges' => 0,
            'other_charges' => 0,
        ]);

        $this->assertSame(900.0, $math['net_rate']);
        $this->assertSame(90.0, $math['freight']);
        $this->assertSame(990.0, $math['pre_gst']);
        $this->assertSame(178.2, $math['gst_amount']);
        $this->assertSame(1168.2, $math['landed']);
    }

    public function test_blank_quote_is_not_quoted(): void
    {
        $this->assertNull((new ComparisonMath())->measure(['is_quoted' => false, 'basic_rate' => 10, 'quoted_quantity' => 1]));
    }

    public function test_inclusive_gst_is_normalized_before_landed_cost(): void
    {
        $math = (new ComparisonMath())->measure([
            'is_quoted' => true,
            'quoted_quantity' => 1,
            'basic_rate' => 118,
            'tax_basis' => 'inclusive',
            'discount_type' => 'percent',
            'discount_value' => 0,
            'gst_rate' => 18,
            'freight_type' => 'free',
        ]);

        $this->assertSame(100.0, $math['exclusive_basic']);
        $this->assertSame(100.0, $math['net_rate']);
        $this->assertSame(100.0, $math['taxable_value']);
        $this->assertSame(18.0, $math['gst_amount']);
        $this->assertSame(118.0, $math['landed']);
    }

    public function test_vendor_bank_interest_adds_financing_cost(): void
    {
        $math = (new ComparisonMath())->measure([
            'is_quoted' => true,
            'quoted_quantity' => 1,
            'basic_rate' => 1000,
            'discount_type' => 'percent',
            'discount_value' => 0,
            'gst_rate' => 0,
            'freight_type' => 'free',
        ], [
            'bank_interest_percent' => 12,
            'bank_interest_days' => 365,
        ]);

        $this->assertSame(120.0, $math['financing_cost']);
        $this->assertSame(1120.0, $math['landed']);
    }
}
