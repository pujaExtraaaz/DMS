<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('party_bank_accounts')) {
            Schema::create('party_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('bank_name');
                $table->string('account_holder_name')->nullable();
                $table->string('account_number', 50);
                $table->string('account_type', 30)->default('current');
                $table->string('ifsc_code', 20)->nullable();
                $table->string('branch_name')->nullable();
                $table->text('branch_address')->nullable();
                $table->string('upi_id', 100)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('party_bank_accounts');
    }
};