<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_interest_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('bill_id')->constrained('acct_bills')->cascadeOnDelete();
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained('acct_vouchers')->cascadeOnDelete();
            $table->date('as_on');
            $table->string('kind', 20);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->unique(['bill_id', 'as_on']);
        });

        Schema::table('acct_invoices', function (Blueprint $table) {
            $table->string('ims_status', 20)->default('pending')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('acct_invoices', function (Blueprint $table) {
            $table->dropColumn('ims_status');
        });

        Schema::dropIfExists('acct_interest_charges');
    }
};