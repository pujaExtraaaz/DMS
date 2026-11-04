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
        Schema::table('invoices', function (Blueprint $table) {
            // Delivery Note Details
            $table->string('delivery_note_no', 255)->nullable()->after('reference_no');
            $table->boolean('delivery_note_na')->default(false)->after('delivery_note_no');

            // Dispatch Details
            $table->string('dispatch_doc_no', 100)->nullable()->after('delivery_note_na');
            $table->string('dispatched_through', 150)->nullable()->after('dispatch_doc_no');
            $table->string('destination', 150)->nullable()->after('dispatched_through');
            $table->string('carrier_name', 150)->nullable()->after('destination');
            $table->string('lr_rr_no', 100)->nullable()->after('carrier_name');
            $table->date('lr_rr_date')->nullable()->after('lr_rr_no');

            // Order Details
            $table->string('order_no', 100)->nullable()->after('lr_rr_date');
            $table->string('other_references', 255)->nullable()->after('order_no');
            $table->text('terms_of_delivery')->nullable()->after('other_references');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_note_no',
                'delivery_note_na',
                'dispatch_doc_no',
                'dispatched_through',
                'destination',
                'carrier_name',
                'lr_rr_no',
                'lr_rr_date',
                'order_no',
                'other_references',
                'terms_of_delivery',
            ]);
        });
    }
};

