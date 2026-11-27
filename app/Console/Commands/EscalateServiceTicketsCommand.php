<?php

namespace App\Console\Commands;

use App\Domains\Service\Services\ServiceTicketService;
use Illuminate\Console\Command;

class EscalateServiceTicketsCommand extends Command
{
    protected $signature = 'service:escalate-overdue {--limit=100 : Max tickets to escalate}';

    protected $description = 'Mark SLA-overdue service tickets as escalated';

    public function handle(ServiceTicketService $service): int
    {
        $n = $service->escalateOverdue((int) $this->option('limit'));
        $this->info("Escalated {$n} overdue service ticket(s).");

        return self::SUCCESS;
    }
}
