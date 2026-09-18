<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach ([
                'vehicle_no' => fn () => $table->string('vehicle_no', 40)->nullable()->after('due_date_source_date'),
                'reference_no' => fn () => $table->string('reference_no', 60)->nullable()->after('vehicle_no'),
                'terms_and_conditions' => fn () => $table->text('terms_and_conditions')->nullable()->after('notes'),
                'universal_discount_type' => fn () => $table->enum('universal_discount_type', ['percent', 'flat'])->default('flat')->after('discount_amount'),
                'universal_discount_value' => fn () => $table->decimal('universal_discount_value', 12, 2)->default(0)->after('universal_discount_type'),
                'item_discount_total' => fn () => $table->decimal('item_discount_total', 12, 2)->default(0)->after('universal_discount_value'),
                'delivery_state' => fn () => $table->string('delivery_state', 100)->nullable()->after('reference_no'),
                'transport_mode' => fn () => $table->string('transport_mode', 30)->nullable()->after('vehicle_no'),
            ] as $column => $adder) {
                if (! Schema::hasColumn('invoices', $column)) {
                    $adder();
                }
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            foreach ([
                'discount_type' => fn () => $table->enum('discount_type', ['percent', 'flat'])->default('flat')->after('discount_amount'),
                'discount_value' => fn () => $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type'),
                'hsn_code' => fn () => $table->string('hsn_code', 20)->nullable()->after('discount_value'),
                'batch_no' => fn () => $table->string('batch_no', 60)->nullable()->after('hsn_code'),
            ] as $column => $adder) {
                if (! Schema::hasColumn('invoice_items', $column)) {
                    $adder();
                }
            }
        });

        Schema::table('e_invoices', function (Blueprint $table) {
            foreach ([
                'ack_no' => fn () => $table->string('ack_no', 40)->nullable()->after('irn'),
                'ack_date' => fn () => $table->timestamp('ack_date')->nullable()->after('ack_no'),
                'signed_invoice' => fn () => $table->longText('signed_invoice')->nullable()->after('ack_date'),
                'signed_qr_base64' => fn () => $table->longText('signed_qr_base64')->nullable()->after('signed_invoice'),
                'qr_image_path' => fn () => $table->string('qr_image_path', 255)->nullable()->after('signed_qr_base64'),
                'provider' => fn () => $table->string('provider', 30)->default('mastersindia')->after('status'),
                'last_error' => fn () => $table->text('last_error')->nullable()->after('qr_image_path'),
                'requested_at' => fn () => $table->timestamp('requested_at')->nullable()->after('last_error'),
            ] as $column => $adder) {
                if (! Schema::hasColumn('e_invoices', $column)) {
                    $adder();
                }
            }
        });

        Schema::table('e_way_bills', function (Blueprint $table) {
            foreach ([
                'valid_upto' => fn () => $table->timestamp('valid_upto')->nullable()->after('eway_bill_no'),
                'ewb_date' => fn () => $table->timestamp('ewb_date')->nullable()->after('valid_upto'),
                'distance_km' => fn () => $table->unsignedInteger('distance_km')->nullable()->after('ewb_date'),
                'transporter_id' => fn () => $table->string('transporter_id', 20)->nullable()->after('distance_km'),
                'transporter_name' => fn () => $table->string('transporter_name', 255)->nullable()->after('transporter_id'),
                'vehicle_no' => fn () => $table->string('vehicle_no', 40)->nullable()->after('transporter_name'),
                'transport_mode' => fn () => $table->string('transport_mode', 20)->nullable()->after('vehicle_no'),
                'provider' => fn () => $table->string('provider', 30)->default('mastersindia')->after('status'),
                'last_error' => fn () => $table->text('last_error')->nullable()->after('provider'),
            ] as $column => $adder) {
                if (! Schema::hasColumn('e_way_bills', $column)) {
                    $adder();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['vehicle_no', 'reference_no', 'terms_and_conditions', 'universal_discount_type', 'universal_discount_value', 'item_discount_total', 'delivery_state', 'transport_mode'] as $c) {
                if (Schema::hasColumn('invoices', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            foreach (['discount_type', 'discount_value', 'hsn_code', 'batch_no'] as $c) {
                if (Schema::hasColumn('invoice_items', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('e_invoices', function (Blueprint $table) {
            foreach (['ack_no', 'ack_date', 'signed_invoice', 'signed_qr_base64', 'qr_image_path', 'provider', 'last_error', 'requested_at'] as $c) {
                if (Schema::hasColumn('e_invoices', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('e_way_bills', function (Blueprint $table) {
            foreach (['valid_upto', 'ewb_date', 'distance_km', 'transporter_id', 'transporter_name', 'vehicle_no', 'transport_mode', 'provider', 'last_error'] as $c) {
                if (Schema::hasColumn('e_way_bills', $c)) $table->dropColumn($c);
            }
        });
    }
};
