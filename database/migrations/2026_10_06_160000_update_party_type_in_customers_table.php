<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('party_type', 50)->default('sundry_debtors')->change();
        });

        DB::table('customers')
            ->whereIn('party_type', ['customer', 'dealer'])
            ->update(['party_type' => 'sundry_debtors']);

        DB::table('customers')
            ->where('party_type', 'supplier')
            ->update(['party_type' => 'sundry_creditors']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('customers')
            ->where('party_type', 'sundry_debtors')
            ->update(['party_type' => 'customer']);

        DB::table('customers')
            ->where('party_type', 'sundry_creditors')
            ->update(['party_type' => 'supplier']);

        Schema::table('customers', function (Blueprint $table) {
            $table->enum('party_type', ['dealer', 'customer', 'supplier', 'both'])->default('customer')->change();
        });
    }
};

