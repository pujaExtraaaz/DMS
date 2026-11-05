<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            $table->string('warranty_label', 100)->nullable()->after('notes');
            $table->unsignedInteger('warranty_months')->nullable()->after('warranty_label');
            $table->date('warranty_start_on')->nullable()->after('warranty_months');
            $table->date('warranty_expires_on')->nullable()->after('warranty_start_on');
            $table->foreignId('customer_id')->nullable()->after('warranty_expires_on')->constrained('customers')->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->after('customer_id')->constrained('invoices')->nullOnDelete();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->json('serial_numbers')->nullable()->after('batch_no');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('serial_numbers')->nullable()->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_invoice_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn([
                'warranty_label',
                'warranty_months',
                'warranty_start_on',
                'warranty_expires_on',
            ]);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });
    }
};
