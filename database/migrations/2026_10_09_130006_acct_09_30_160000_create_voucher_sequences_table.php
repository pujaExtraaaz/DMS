<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_voucher_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->string('voucher_type', 20);
            $table->string('prefix', 10);
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->timestamps();

            $table->unique(
                ['company_id', 'branch_id', 'financial_year_id', 'voucher_type'],
                'voucher_sequences_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_voucher_sequences');
    }
};
