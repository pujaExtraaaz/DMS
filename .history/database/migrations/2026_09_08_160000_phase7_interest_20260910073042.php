<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('annual_rate', 8, 2)->default(18);
            $table->unsignedInteger('grace_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('interest_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('interest_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('as_of_date');
            $table->decimal('overdue_balance', 14, 2)->default(0);
            $table->unsignedInteger('overdue_days')->default(0);
            $table->decimal('annual_rate', 8, 2)->default(18);
            $table->decimal('interest_amount', 14, 2)->default(0);
            $table->enum('status', ['preview', 'posted', 'waived', 'reversed'])->default('preview');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'as_of_date', 'status']);
            $table->index(['invoice_id', 'as_of_date']);
        });

        Schema::create('interest_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interest_ledger_id')->nullable()->constrained()->nullOnDelete();
            $table->date('document_date');
            $table->decimal('amount', 14, 2);
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('created_by_name', 100)->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        // Widen communication type for email / notification channels (Phase 8).
        DB::statement("ALTER TABLE communication_logs MODIFY COLUMN type VARCHAR(40) NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_documents');
        Schema::dropIfExists('interest_ledgers');
        Schema::dropIfExists('interest_rules');

        DB::statement("ALTER TABLE communication_logs MODIFY COLUMN type ENUM('whatsapp_invoice','payment_link','payment_reminder') NOT NULL");
    }
};
