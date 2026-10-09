<?php

namespace Tally\Console\Commands;

use Database\Seeders\DemoBooksSeeder;
use Illuminate\Console\Command;

class SeedDemoData extends Command
{
    protected $signature = 'demo:seed';

    protected $description = 'Seed the Harbour Supplies demo company when it is not already present';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => DemoBooksSeeder::class, '--force' => true]);
        $this->info('Demo books are ready. Switch the working company to Harbour Supplies Pvt Ltd.');

        return self::SUCCESS;
    }
}
