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
        Schema::create('party_credit_cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('cheque_number', 50)->index();
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->date('cheque_date')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('cheque_type', 40)->default('credit_cheque'); // security_cheque, credit_cheque, post_dated_cheque, other
            $table->string('status', 30)->default('pending'); // received, pending, deposited, cleared, bounced, cancelled
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_credit_cheques');
    }
};