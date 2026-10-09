<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_account_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('acct_account_groups')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->string('nature', 20);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'parent_id']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_account_groups');
    }
};
