<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bridge tables only. Does not alter existing DMS operational tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_company_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedBigInteger('acct_company_id');
            $table->unsignedBigInteger('acct_branch_id')->nullable();
            $table->unsignedBigInteger('acct_financial_year_id')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique('organization_company_id');
            $table->unique('acct_company_id');
        });

        Schema::create('sync_entity_links', function (Blueprint $table) {
            $table->id();
            $table->string('entity_key', 60);
            $table->string('dms_type', 120);
            $table->unsignedBigInteger('dms_id');
            $table->string('acct_type', 120);
            $table->unsignedBigInteger('acct_id');
            $table->string('sync_status', 30)->default('synced');
            $table->unsignedInteger('sync_version')->default(1);
            $table->text('last_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['dms_type', 'dms_id', 'entity_key'], 'sync_entity_dms_unique');
            $table->unique(['acct_type', 'acct_id', 'entity_key'], 'sync_entity_acct_unique');
            $table->index(['entity_key', 'sync_status']);
        });

        Schema::create('sync_failures', function (Blueprint $table) {
            $table->id();
            $table->string('entity_key', 60);
            $table->string('direction', 20);
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['entity_key', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_failures');
        Schema::dropIfExists('sync_entity_links');
        Schema::dropIfExists('accounting_company_links');
    }
};
