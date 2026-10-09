<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_voucher_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('acct_vouchers')->cascadeOnDelete();
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('narration', 500)->nullable();
            $table->string('reference', 50)->nullable();
            $table->timestamps();

            $table->index(['voucher_id', 'line_number']);
            $table->index('ledger_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_voucher_entries');
    }
};
