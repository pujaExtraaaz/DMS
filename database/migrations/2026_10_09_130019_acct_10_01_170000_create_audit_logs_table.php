<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('module', 40);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('acct_companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained('acct_financial_years')->nullOnDelete();
            $table->string('description', 500)->nullable();
            $table->json('previous_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('source', 16)->default('web');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'action']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_audit_logs');
    }
};
