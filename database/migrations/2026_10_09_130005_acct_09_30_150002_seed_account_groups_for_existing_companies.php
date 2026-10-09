<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Chart seeding runs from accounting:provision-company, not during migrate,
 * so this step cannot touch the DMS Company model.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
