<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE e_way_bills
            MODIFY status ENUM('pending', 'generated', 'manual', 'stub')
            NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE e_way_bills
            MODIFY status ENUM('pending', 'generated', 'manual')
            NOT NULL DEFAULT 'pending'
        ");
    }
};