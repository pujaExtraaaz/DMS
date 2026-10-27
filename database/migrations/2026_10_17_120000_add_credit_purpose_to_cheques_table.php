<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cheques')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE cheques MODIFY purpose ENUM('security', 'pdc', 'credit') NOT NULL DEFAULT 'pdc'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cheques')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('cheques')->where('purpose', 'credit')->update(['purpose' => 'pdc']);
            DB::statement("ALTER TABLE cheques MODIFY purpose ENUM('security', 'pdc') NOT NULL DEFAULT 'pdc'");
        }
    }
};
