<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'bank_ledger_id')) {
                $table->unsignedBigInteger('bank_ledger_id')->nullable()->after('method');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'trade_name')) {
                $table->string('trade_name')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'bank_ledger_id')) {
                $table->dropColumn('bank_ledger_id');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'trade_name')) {
                $table->dropColumn('trade_name');
            }
        });
    }
};