<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        DB::statement('ALTER TABLE payments MODIFY COLUMN invoice_id BIGINT UNSIGNED NULL');

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });

        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash','upi','bank','cheque','other') NOT NULL DEFAULT 'cash'");

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->unique(['payment_id', 'invoice_id']);
        });

        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_no', 50);
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('purpose', ['security', 'pdc'])->default('pdc');
            $table->enum('direction', [
                'received_from_client',
                'received_from_vendor',
                'issued_to_vendor',
            ])->default('received_from_client');
            $table->decimal('amount', 14, 2);
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->date('cheque_date')->nullable();
            $table->date('deposit_date')->nullable();
            $table->date('clearance_date')->nullable();
            $table->enum('status', ['pending', 'deposited', 'cleared', 'bounced', 'cancelled'])->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('bounce_count')->default(0);
            $table->timestamp('bounced_at')->nullable();
            $table->string('bounce_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['cheque_no', 'bank_name']);
        });

        Schema::create('cheque_bounces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('bounced_on');
            $table->string('reason')->nullable();
            $table->decimal('charges', 12, 2)->default(0);
            $table->unsignedInteger('bounce_number')->default(1);
            $table->boolean('triggered_freeze')->default(false);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_no', 30)->unique();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->date('credit_note_date');
            $table->enum('reason', [
                'return',
                'price',
                'scheme',
                'damage',
                'settlement',
                'interest_reversal',
                'other',
            ])->default('other');
            $table->enum('status', ['draft', 'approved', 'posted', 'cancelled'])->default('draft');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->boolean('affects_stock')->default(false);
            $table->text('notes')->nullable();
            $table->string('created_by_name', 100)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approved_by_name', 100)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Widen ledger type carefully so credit_note can post without enum rebuild pain later.
        DB::statement("ALTER TABLE outstanding_ledger MODIFY COLUMN type VARCHAR(30) NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('cheque_bounces');
        Schema::dropIfExists('cheques');
        Schema::dropIfExists('payment_allocations');

        DB::statement("ALTER TABLE outstanding_ledger MODIFY COLUMN type ENUM('invoice','payment','settlement','adjustment') NOT NULL");
        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash','upi','bank','other') NOT NULL DEFAULT 'cash'");

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        DB::statement('ALTER TABLE payments MODIFY COLUMN invoice_id BIGINT UNSIGNED NOT NULL');

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices');
        });
    }
};
