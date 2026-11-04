<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'einvoice_status')) {
                $table->string('einvoice_status', 25)->default('pending')->after('status')->index();
            }
            if (! Schema::hasColumn('invoices', 'eway_bill_status')) {
                $table->string('eway_bill_status', 25)->default('pending')->after('einvoice_status')->index();
            }
            if (! Schema::hasColumn('invoices', 'eway_bill_required')) {
                $table->string('eway_bill_required', 20)->default('auto')->after('eway_bill_status');
            }
            if (! Schema::hasColumn('invoices', 'transporter_id')) {
                $table->string('transporter_id', 25)->nullable()->after('carrier_name');
            }
            if (! Schema::hasColumn('invoices', 'transporter_name')) {
                $table->string('transporter_name', 255)->nullable()->after('transporter_id');
            }
            if (! Schema::hasColumn('invoices', 'distance_km')) {
                $table->unsignedInteger('distance_km')->nullable()->after('destination');
            }
            if (! Schema::hasColumn('invoices', 'vehicle_type')) {
                $table->string('vehicle_type', 10)->default('R')->after('vehicle_no');
            }
        });

        Schema::table('e_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('e_invoices', 'cancel_reason')) {
                $table->string('cancel_reason', 50)->nullable()->after('last_error');
            }
            if (! Schema::hasColumn('e_invoices', 'cancel_remarks')) {
                $table->string('cancel_remarks', 255)->nullable()->after('cancel_reason');
            }
            if (! Schema::hasColumn('e_invoices', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancel_remarks');
            }
            if (! Schema::hasColumn('e_invoices', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            }
        });

        Schema::table('e_way_bills', function (Blueprint $table) {
            if (! Schema::hasColumn('e_way_bills', 'cancel_reason')) {
                $table->string('cancel_reason', 50)->nullable()->after('last_error');
            }
            if (! Schema::hasColumn('e_way_bills', 'cancel_remarks')) {
                $table->string('cancel_remarks', 255)->nullable()->after('cancel_reason');
            }
            if (! Schema::hasColumn('e_way_bills', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancel_remarks');
            }
            if (! Schema::hasColumn('e_way_bills', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('e_way_bills', 'doc_no')) {
                $table->string('doc_no', 60)->nullable()->after('transport_mode');
            }
            if (! Schema::hasColumn('e_way_bills', 'doc_date')) {
                $table->date('doc_date')->nullable()->after('doc_no');
            }
            if (! Schema::hasColumn('e_way_bills', 'vehicle_type')) {
                $table->string('vehicle_type', 10)->default('R')->after('vehicle_no');
            }
            if (! Schema::hasColumn('e_way_bills', 'from_pincode')) {
                $table->string('from_pincode', 10)->nullable()->after('vehicle_type');
            }
            if (! Schema::hasColumn('e_way_bills', 'to_pincode')) {
                $table->string('to_pincode', 10)->nullable()->after('from_pincode');
            }
            if (! Schema::hasColumn('e_way_bills', 'from_state')) {
                $table->string('from_state', 100)->nullable()->after('to_pincode');
            }
            if (! Schema::hasColumn('e_way_bills', 'to_state')) {
                $table->string('to_state', 100)->nullable()->after('from_state');
            }
        });

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE e_invoices MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending'");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE e_way_bills MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'einvoice_status',
                'eway_bill_status',
                'eway_bill_required',
                'transporter_id',
                'transporter_name',
                'distance_km',
                'vehicle_type',
            ]);
        });

        Schema::table('e_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'cancel_reason',
                'cancel_remarks',
                'cancelled_at',
                'cancelled_by',
            ]);
        });

        Schema::table('e_way_bills', function (Blueprint $table) {
            $table->dropColumn([
                'cancel_reason',
                'cancel_remarks',
                'cancelled_at',
                'cancelled_by',
                'doc_no',
                'doc_date',
                'vehicle_type',
                'from_pincode',
                'to_pincode',
                'from_state',
                'to_state',
            ]);
        });
    }
};
