<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tally\Receivables\CollectionReminderService;

class RunCollectionRemindersCommand extends Command
{
    protected $signature = 'books:run-collection-reminders {--date=}';

    protected $description = 'Queue collection reminders for overdue / approaching bills';

    public function handle(CollectionReminderService $service): int
    {
        $queued = $service->queueDue($this->option('date') ?: null);
        $this->info(count($queued).' reminder(s) queued.');

        return self::SUCCESS;
    }
}
