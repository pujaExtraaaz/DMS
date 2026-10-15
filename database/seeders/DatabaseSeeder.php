<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            // Prefer Avit tenant seed over legacy DemoDataSeeder (which creates cross-tenant demo users).
            CleanBusinessDataSeeder::class,
            AvitDigitalCompanySeeder::class,
            AvitBooksCompanySeeder::class,
            UnlinkExternalUsersSeeder::class,
            AvitDigitalDemoSeeder::class,
            AvitBooksDemoSeeder::class,
        ]);

        // Push all DMS operational data into the linked Books company.
        $this->command?->call('accounting:link-all');
    }
}
