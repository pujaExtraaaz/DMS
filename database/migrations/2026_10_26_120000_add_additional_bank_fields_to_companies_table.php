<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (! Schema::hasColumn('companies', 'swift_code')) {
                    $table->string('swift_code', 50)->nullable()->after('upi_id');
                }
                if (! Schema::hasColumn('companies', 'bank_branch')) {
                    $table->string('bank_branch', 255)->nullable()->after('swift_code');
                }
                if (! Schema::hasColumn('companies', 'bsr_code')) {
                    $table->string('bsr_code', 50)->nullable()->after('bank_branch');
                }
                if (! Schema::hasColumn('companies', 'client_code')) {
                    $table->string('client_code', 50)->nullable()->after('bsr_code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                foreach (['client_code', 'bsr_code', 'bank_branch', 'swift_code'] as $col) {
                    if (Schema::hasColumn('companies', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

