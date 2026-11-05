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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('payment_terms')->nullable()->after('notes');
            $table->decimal('advance_amount', 15, 2)->default(0)->after('payment_terms');
            $table->decimal('balance_amount', 15, 2)->nullable()->after('advance_amount');
            $table->decimal('advance_percentage', 5, 2)->nullable()->after('advance_amount');
            $table->string('reference_no', 100)->nullable()->after('quotation_id');
            $table->string('salesperson_name', 100)->nullable()->after('salesperson_id');
            $table->string('terms_of_delivery', 50)->nullable()->after('fulfilment_mode');
            $table->string('caretaker_phone', 20)->nullable()->after('terms_of_delivery');
            $table->string('architect_name', 255)->nullable()->after('caretaker_phone');
            $table->string('architect_phone', 20)->nullable()->after('architect_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_terms',
                'advance_amount',
                'balance_amount',
                'advance_percentage',
                'reference_no',
                'salesperson_name',
                'terms_of_delivery',
                'caretaker_phone',
                'architect_name',
                'architect_phone',
            ]);
        });
    }
};
