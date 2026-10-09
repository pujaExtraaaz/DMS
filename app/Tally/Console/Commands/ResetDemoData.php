<?php

namespace Tally\Console\Commands;

use Tally\Demo\DemoData;
use Database\Seeders\DemoBooksSeeder;
use Illuminate\Console\Command;

class ResetDemoData extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Remove only the Harbour Supplies demo company and seed it again';

    public function handle(DemoData $demo): int
    {
        $demo->forget();
        $this->call('db:seed', ['--class' => DemoBooksSeeder::class, '--force' => true]);
        $this->info('Harbour Supplies demo books were reset.');

        return self::SUCCESS;
    }
}
