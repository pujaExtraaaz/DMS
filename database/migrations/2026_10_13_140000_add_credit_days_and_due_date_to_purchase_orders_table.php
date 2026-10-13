<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'credit_days')) {
                $table->unsignedInteger('credit_days')->default(0)->after('expected_date');
            }
            if (! Schema::hasColumn('purchase_orders', 'due_date')) {
                $table->date('due_date')->nullable()->after('credit_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('purchase_orders', 'due_date')) {
                $dropColumns[] = 'due_date';
            }
            if (Schema::hasColumn('purchase_orders', 'credit_days')) {
                $dropColumns[] = 'credit_days';
            }
            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

