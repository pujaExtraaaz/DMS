<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // e-Way Bill additional header fields
            if (! Schema::hasColumn('invoices', 'eway_bill_no')) {
                $table->string('eway_bill_no', 50)->nullable()->after('eway_bill_required');
            }
            if (! Schema::hasColumn('invoices', 'eway_bill_date')) {
                $table->date('eway_bill_date')->nullable()->after('eway_bill_no');
            }
            if (! Schema::hasColumn('invoices', 'eway_sub_type')) {
                $table->string('eway_sub_type', 50)->default('Supply')->after('eway_bill_date');
            }
            if (! Schema::hasColumn('invoices', 'eway_doc_type')) {
                $table->string('eway_doc_type', 50)->default('Tax Invoice')->after('eway_sub_type');
            }
            if (! Schema::hasColumn('invoices', 'eway_supply_type')) {
                $table->string('eway_supply_type', 50)->default('Outward')->after('eway_doc_type');
            }

            // Consignor Details (From)
            if (! Schema::hasColumn('invoices', 'consignor_name')) {
                $table->string('consignor_name', 255)->nullable()->after('eway_supply_type');
            }
            if (! Schema::hasColumn('invoices', 'consignor_gstin')) {
                $table->string('consignor_gstin', 25)->nullable()->after('consignor_name');
            }
            if (! Schema::hasColumn('invoices', 'consignor_state')) {
                $table->string('consignor_state', 100)->nullable()->after('consignor_gstin');
            }
            if (! Schema::hasColumn('invoices', 'consignor_address_type')) {
                $table->string('consignor_address_type', 100)->nullable()->after('consignor_state');
            }
            if (! Schema::hasColumn('invoices', 'consignor_address_line_1')) {
                $table->string('consignor_address_line_1', 255)->nullable()->after('consignor_address_type');
            }
            if (! Schema::hasColumn('invoices', 'consignor_address_line_2')) {
                $table->string('consignor_address_line_2', 255)->nullable()->after('consignor_address_line_1');
            }
            if (! Schema::hasColumn('invoices', 'consignor_pincode')) {
                $table->string('consignor_pincode', 20)->nullable()->after('consignor_address_line_2');
            }
            if (! Schema::hasColumn('invoices', 'consignor_city')) {
                $table->string('consignor_city', 100)->nullable()->after('consignor_pincode');
            }
            if (! Schema::hasColumn('invoices', 'consignor_actual_state')) {
                $table->string('consignor_actual_state', 100)->nullable()->after('consignor_city');
            }

            // Consignee Details (To)
            if (! Schema::hasColumn('invoices', 'consignee_name')) {
                $table->string('consignee_name', 255)->nullable()->after('consignor_actual_state');
            }
            if (! Schema::hasColumn('invoices', 'consignee_gstin')) {
                $table->string('consignee_gstin', 25)->nullable()->after('consignee_name');
            }
            if (! Schema::hasColumn('invoices', 'consignee_state')) {
                $table->string('consignee_state', 100)->nullable()->after('consignee_gstin');
            }
            if (! Schema::hasColumn('invoices', 'consignee_address_type')) {
                $table->string('consignee_address_type', 100)->nullable()->after('consignee_state');
            }
            if (! Schema::hasColumn('invoices', 'consignee_address_line_1')) {
                $table->string('consignee_address_line_1', 255)->nullable()->after('consignee_address_type');
            }
            if (! Schema::hasColumn('invoices', 'consignee_address_line_2')) {
                $table->string('consignee_address_line_2', 255)->nullable()->after('consignee_address_line_1');
            }
            if (! Schema::hasColumn('invoices', 'consignee_pincode')) {
                $table->string('consignee_pincode', 20)->nullable()->after('consignee_address_line_2');
            }
            if (! Schema::hasColumn('invoices', 'consignee_city')) {
                $table->string('consignee_city', 100)->nullable()->after('consignee_pincode');
            }
            if (! Schema::hasColumn('invoices', 'consignee_actual_state')) {
                $table->string('consignee_actual_state', 100)->nullable()->after('consignee_city');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'eway_bill_no',
                'eway_bill_date',
                'eway_sub_type',
                'eway_doc_type',
                'eway_supply_type',
                'consignor_name',
                'consignor_gstin',
                'consignor_state',
                'consignor_address_type',
                'consignor_address_line_1',
                'consignor_address_line_2',
                'consignor_pincode',
                'consignor_city',
                'consignor_actual_state',
                'consignee_name',
                'consignee_gstin',
                'consignee_state',
                'consignee_address_type',
                'consignee_address_line_1',
                'consignee_address_line_2',
                'consignee_pincode',
                'consignee_city',
                'consignee_actual_state',
            ]);
        });
    }
};

