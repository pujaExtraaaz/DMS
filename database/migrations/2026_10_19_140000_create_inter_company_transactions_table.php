<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inter_company_transactions')) {
            Schema::create('inter_company_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_no', 40)->unique();
                $table->date('transaction_date');
                $table->string('transaction_type', 50); // Sale, Purchase, Service, Loan Given, Loan Received
                $table->foreignId('from_company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('to_company_id')->constrained('companies')->cascadeOnDelete();

                // Amount Details
                $table->decimal('amount', 15, 2);
                $table->string('currency', 10)->default('INR');
                $table->decimal('exchange_rate', 10, 4)->default(1.0000);

                // Tax & TDS Details
                $table->boolean('is_gst_applicable')->default(false);
                $table->decimal('igst_amount', 15, 2)->default(0.00);
                $table->boolean('is_tds_applicable')->default(false);
                $table->string('tds_section', 50)->nullable();
                $table->decimal('tds_rate', 5, 2)->default(0.00);
                $table->decimal('tds_amount', 15, 2)->default(0.00);

                // Net Amount = (amount * exchange_rate) + igst_amount - tds_amount
                $table->decimal('net_amount', 15, 2);

                // References & Settlement
                $table->string('reference_invoice', 100)->nullable();
                $table->string('reference_po', 100)->nullable();
                $table->date('due_date')->nullable();
                $table->string('status', 30)->default('Pending'); // Pending, Approved, Settled, Cancelled

                // Additional Information & Audit
                $table->text('narration')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                $table->index(['transaction_date', 'status']);
                $table->index(['from_company_id', 'to_company_id']);
                $table->index('transaction_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inter_company_transactions');
    }
};

