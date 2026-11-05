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
            if (! Schema::hasColumn('invoices', 'bill_to_place')) {
                $table->string('bill_to_place', 100)->nullable()->after('delivery_state');
            }
            if (! Schema::hasColumn('invoices', 'ship_to_place')) {
                $table->string('ship_to_place', 100)->nullable()->after('bill_to_place');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'ship_to_place')) {
                $table->dropColumn('ship_to_place');
            }
            if (Schema::hasColumn('invoices', 'bill_to_place')) {
                $table->dropColumn('bill_to_place');
            }
        });
    }
};

