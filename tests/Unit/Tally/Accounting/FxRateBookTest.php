<?php

namespace Tests\Unit\Tally\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Accounting\FxRateBook;
use Tally\Models\Currency;
use Tally\Models\ExchangeRate;

class FxRateBookTest extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_uses_latest_rate_on_or_before_date(): void
    {
        $company = $this->makeCompany(['name' => 'FX Co']);
        $currency = Currency::query()->create([
            'company_id' => $company->id,
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'decimal_places' => 2,
            'exchange_rate' => 80,
            'is_base' => false,
        ]);

        ExchangeRate::query()->create([
            'company_id' => $company->id,
            'currency_id' => $currency->id,
            'rate_date' => '2025-01-01',
            'rate' => 82.5,
        ]);
        ExchangeRate::query()->create([
            'company_id' => $company->id,
            'currency_id' => $currency->id,
            'rate_date' => '2025-06-01',
            'rate' => 84.0,
        ]);

        $book = app(FxRateBook::class);

        $this->assertSame(82.5, $book->rateOn($company, $currency, '2025-03-01'));
        $this->assertSame(84.0, $book->rateOn($company, $currency, '2025-07-01'));
    }

    public function test_falls_back_to_currency_master_rate(): void
    {
        $company = $this->makeCompany(['name' => 'FX Co 2']);
        $currency = Currency::query()->create([
            'company_id' => $company->id,
            'name' => 'Euro',
            'code' => 'EUR',
            'symbol' => '€',
            'decimal_places' => 2,
            'exchange_rate' => 90.25,
            'is_base' => false,
        ]);

        $this->assertSame(90.25, app(FxRateBook::class)->rateOn($company, $currency, '2025-01-01'));
    }
}
