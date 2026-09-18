<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->decimal('cgst_percent', 5, 2)->default(0)->after('tax_percent');
            $table->decimal('sgst_percent', 5, 2)->default(0)->after('cgst_percent');
            $table->decimal('cgst_amount', 15, 2)->default(0)->after('sgst_percent');
            $table->decimal('sgst_amount', 15, 2)->default(0)->after('cgst_amount');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'cgst_percent',
                'sgst_percent',
                'cgst_amount',
                'sgst_amount',
            ]);
        });
    }
};