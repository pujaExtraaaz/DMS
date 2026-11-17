<?php

namespace Database\Seeders;

use App\Domains\Master\Models\TaxRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tax_rates')) {
            return;
        }

        foreach ([
            ['name' => 'GST 0%', 'rate' => 0],
            ['name' => 'GST 5%', 'rate' => 5],
            ['name' => 'GST 12%', 'rate' => 12],
            ['name' => 'GST 18%', 'rate' => 18],
            ['name' => 'GST 28%', 'rate' => 28],
        ] as $row) {
            TaxRate::query()->firstOrCreate(
                ['rate' => $row['rate']],
                ['name' => $row['name'], 'is_active' => true]
            );
        }
    }
}
