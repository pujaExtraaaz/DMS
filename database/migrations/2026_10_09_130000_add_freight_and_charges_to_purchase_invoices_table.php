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
        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_invoices', 'freight_charge')) {
                    $table->decimal('freight_charge', 14, 2)->default(0)->after('tax_amount');
                }
                if (! Schema::hasColumn('purchase_invoices', 'other_charges')) {
                    $table->decimal('other_charges', 14, 2)->default(0)->after('freight_charge');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_invoices', 'other_charges')) {
                    $table->dropColumn('other_charges');
                }
                if (Schema::hasColumn('purchase_invoices', 'freight_charge')) {
                    $table->dropColumn('freight_charge');
                }
            });
        }
    }
};

