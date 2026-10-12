<?php

namespace Tally\Accounting;

use Tally\Models\Company;
use Tally\Models\Currency;
use Tally\Models\ExchangeRate;

class FxRateBook
{
    public function rateOn(Company $company, Currency $currency, string $date): float
    {
        if ($currency->is_base) {
            return 1.0;
        }

        $historical = ExchangeRate::query()
            ->where('company_id', $company->id)
            ->where('currency_id', $currency->id)
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->value('rate');

        if ($historical !== null) {
            return (float) $historical;
        }

        return (float) ($currency->exchange_rate ?: 1);
    }

    public function upsert(Company $company, Currency $currency, string $date, float $rate): ExchangeRate
    {
        return ExchangeRate::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'currency_id' => $currency->id,
                'rate_date' => $date,
            ],
            ['rate' => $rate],
        );
    }
}
