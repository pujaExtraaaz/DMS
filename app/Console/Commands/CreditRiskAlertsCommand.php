<?php

namespace App\Console\Commands;

use App\Domains\Master\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreditRiskAlertsCommand extends Command
{
    protected $signature = 'dms:credit-risk-alerts';

    protected $description = 'Stub: emit credit-risk alerts for frozen / high-risk parties';

    public function handle(): int
    {
        $frozen = Customer::query()->where('credit_status', 'frozen')->count();
        $highRisk = Customer::query()->where('risk_status', 'high')->count();

        Log::info('dms:credit-risk-alerts', [
            'frozen' => $frozen,
            'high_risk' => $highRisk,
        ]);

        $this->info("Credit-risk alert stub: frozen={$frozen}, high_risk={$highRisk}");

        return self::SUCCESS;
    }
}
