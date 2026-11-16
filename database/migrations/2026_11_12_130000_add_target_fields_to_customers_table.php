<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'target_amount')) {
                $table->decimal('target_amount', 12, 2)->nullable()->after('credit_period_basis');
            }
            if (! Schema::hasColumn('customers', 'target_start_date')) {
                $table->date('target_start_date')->nullable()->after('target_amount');
            }
            if (! Schema::hasColumn('customers', 'target_end_date')) {
                $table->date('target_end_date')->nullable()->after('target_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'target_end_date')) {
                $table->dropColumn('target_end_date');
            }
            if (Schema::hasColumn('customers', 'target_start_date')) {
                $table->dropColumn('target_start_date');
            }
            if (Schema::hasColumn('customers', 'target_amount')) {
                $table->dropColumn('target_amount');
            }
        });
    }
};

