<?php

namespace App\Console\Commands;

use App\Domains\Interest\Services\InterestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PreviewInterestCommand extends Command
{
    protected $signature = 'dms:preview-interest';

    protected $description = 'Generate daily interest previews for overdue balances';

    public function handle(InterestService $interestService): int
    {
        $rows = $interestService->previewOverdue();
        Log::info('dms:preview-interest', ['rows' => $rows->count()]);
        $this->info('Interest preview rows: '.$rows->count());

        return self::SUCCESS;
    }
}
