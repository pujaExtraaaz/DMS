<?php

namespace Tests\Unit\Tally\Tax;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBooksCompany;
use Tests\TestCase;
use Tally\Banking\CashForecastService;
use Tally\Tax\StatutoryCalendarService;

class SectionDEWave1Test extends TestCase
{
    use CreatesBooksCompany;
    use RefreshDatabase;

    public function test_cash_forecast_closing_math(): void
    {
        $company = $this->makeCompany();
        $service = app(CashForecastService::class);

        $this->assertSame(8500.0, $service->closingCash(10000.0, 500.0, 2000.0));

        $forecast = $service->save($company, [
            'name' => 'Base 30d',
            'as_of_date' => '2025-10-01',
            'horizon_days' => 30,
            'opening_cash' => '10000.00',
            'projected_inflows' => '2500.50',
            'projected_outflows' => '1200.25',
            'scenario' => 'base',
            'lines_json' => [
                ['label' => 'Collections', 'amount' => 2500.50, 'side' => 'in'],
                ['label' => 'Payroll', 'amount' => 1200.25, 'side' => 'out'],
            ],
        ]);

        $this->assertSame('11300.25', (string) $forecast->closing_cash);
        $this->assertIsArray($forecast->lines_json);
        $this->assertCount(2, $forecast->lines_json);
    }

    public function test_statutory_due_date_create(): void
    {
        $company = $this->makeCompany();
        $service = app(StatutoryCalendarService::class);

        $due = $service->create($company, [
            'obligation' => 'GSTR-3B',
            'category' => 'gst',
            'due_date' => '2025-11-20',
            'period_label' => 'Oct 2025',
            'status' => 'pending',
        ]);

        $this->assertSame($company->id, $due->company_id);
        $this->assertSame('GSTR-3B', $due->obligation);
        $this->assertSame('2025-11-20', $due->due_date->toDateString());

        $seeded = $service->seedMonth($company, '2025-10');
        $this->assertNotEmpty($seeded);
        $this->assertSame('gst', $seeded[0]->category);
    }
}
