<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cheque_books')) {
            Schema::create('cheque_books', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('bank_account_no', 64);
                $table->string('bank_name', 100)->nullable();
                $table->string('cheque_book_name', 150)->nullable();
                $table->string('from_number', 30);
                $table->string('to_number', 30);
                $table->unsignedBigInteger('from_numeric');
                $table->unsignedBigInteger('to_numeric');
                $table->unsignedInteger('total_cheques');
                $table->string('status', 20)->default('active');
                $table->date('issue_date')->nullable();
                $table->text('remarks')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'bank_account_no']);
                $table->index(['company_id', 'bank_account_no', 'status']);
                $table->index(['company_id', 'bank_account_no', 'from_numeric', 'to_numeric'], 'cb_comp_acc_range_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_books');
    }
};

