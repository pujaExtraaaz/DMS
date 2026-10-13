<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tally\Banking\BankFeedService;
use Tally\Models\Company;

class PullBankFeedsCommand extends Command
{
    protected $signature = 'accounting:pull-bank-feeds {--company= : Limit to one acct company id}';

    protected $description = 'Auto-import bank statements from active bank_feed integration endpoints';

    public function handle(BankFeedService $feeds): int
    {
        $company = null;
        if ($this->option('company')) {
            $company = Company::query()->findOrFail((int) $this->option('company'));
        }

        $results = $feeds->pullAll($company);
        if ($results === []) {
            $this->info('No active bank feeds.');

            return self::SUCCESS;
        }

        foreach ($results as $result) {
            $this->line(sprintf(
                'Endpoint #%d: imported %d, duplicates %d, errors %d',
                $result['endpoint_id'],
                $result['imported'],
                $result['duplicates'],
                count($result['errors']),
            ));
            foreach (array_slice($result['errors'], 0, 5) as $error) {
                $this->warn('  '.$error);
            }
        }

        return self::SUCCESS;
    }
}
