<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('acct_api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('api_token_id')->nullable()->constrained('acct_api_tokens')->nullOnDelete();
            $table->string('method', 10);
            $table->string('path');
            $table->unsignedSmallInteger('status');
            $table->string('ip', 45)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['path', 'created_at']);
        });

        Schema::create('acct_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('request_fingerprint', 64);
            $table->unsignedSmallInteger('status_code');
            $table->longText('response_body');
            $table->timestamps();
            $table->unique(['user_id', 'key']);
        });

        Schema::create('acct_integration_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('acct_companies')->cascadeOnDelete();
            $table->string('referenceable_type');
            $table->unsignedBigInteger('referenceable_id');
            $table->index(['referenceable_type', 'referenceable_id'], 'acct_int_ref_morph');
            $table->string('source', 80);
            $table->string('external_reference_id');
            $table->string('sync_status', 20)->default('synced');
            $table->timestamps();
            $table->unique(['company_id', 'source', 'referenceable_type', 'external_reference_id'], 'integration_reference_unique');
        });

        Schema::create('acct_webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->text('secret');
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('acct_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained('acct_webhook_endpoints')->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_webhook_deliveries');
        Schema::dropIfExists('acct_webhook_endpoints');
        Schema::dropIfExists('acct_integration_references');
        Schema::dropIfExists('acct_idempotency_keys');
        Schema::dropIfExists('acct_api_request_logs');
        Schema::dropIfExists('acct_api_tokens');
    }
};
