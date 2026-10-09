<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_system')->default(false);
            $table->json('permissions');
            $table->timestamps();
            $table->unique('name');
        });

        Schema::create('acct_user_companies', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->primary(['user_id', 'company_id']);
        });

        Schema::create('acct_user_branches', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->cascadeOnDelete();
            $table->primary(['user_id', 'branch_id']);
        });

        Schema::create('acct_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
            $table->string('type', 20);
            $table->string('legal_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('billing_address', 500)->nullable();
            $table->string('shipping_address', 500)->nullable();
            $table->string('state')->nullable();
            $table->string('country', 80)->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('gst_registration_type', 20)->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->unsignedSmallInteger('credit_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique('ledger_id');
            $table->unique(['company_id', 'gstin']);
            $table->index(['company_id', 'type', 'is_active']);
        });

        Schema::create('acct_product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('acct_products')->cascadeOnDelete();
            $table->string('barcode', 64);
            $table->timestamps();
            $table->unique(['company_id', 'barcode']);
            $table->index(['company_id', 'product_id']);
        });

        Schema::create('acct_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('key', 80);
            $table->json('value');
            $table->timestamps();
            $table->unique(['company_id', 'user_id', 'key']);
        });

        Schema::create('acct_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('webhook_endpoint_id')->nullable()->constrained('acct_webhook_endpoints')->nullOnDelete();
            $table->string('name');
            $table->string('type', 40);
            $table->string('direction', 20);
            $table->string('external_system', 80)->nullable();
            $table->text('credentials')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('last_status', 20)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('acct_integration_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('acct_integrations')->cascadeOnDelete();
            $table->string('status', 20);
            $table->string('message', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['integration_id', 'status', 'created_at'], 'integration_syncs_status_index');
        });

        $this->backfillParties();
    }

    private function backfillParties(): void
    {
        $rows = DB::table('acct_ledgers')
            ->join('acct_account_groups', 'acct_account_groups.id', '=', 'acct_ledgers.account_group_id')
            ->leftJoin('acct_account_groups as parents', 'parents.id', '=', 'acct_account_groups.parent_id')
            ->where(function ($query): void {
                $query->whereIn('acct_account_groups.code', ['DEBTORS', 'CREDITORS'])
                    ->orWhereIn('parents.code', ['DEBTORS', 'CREDITORS']);
            })
            ->select('acct_ledgers.*', 'acct_account_groups.code as group_code', 'parents.code as parent_code')
            ->get();

        foreach ($rows as $ledger) {
            $code = in_array($ledger->group_code, ['DEBTORS', 'CREDITORS'], true) ? $ledger->group_code : $ledger->parent_code;
            DB::table('acct_parties')->insert([
                'company_id' => $ledger->company_id,
                'ledger_id' => $ledger->id,
                'type' => $code === 'CREDITORS' ? 'supplier' : 'customer',
                'phone' => $ledger->phone,
                'email' => $ledger->email,
                'billing_address' => $ledger->address,
                'state' => $ledger->state,
                'gstin' => $ledger->gstin,
                'pan' => $ledger->pan,
                'gst_registration_type' => $ledger->gst_registration_type,
                'credit_limit' => $ledger->credit_limit,
                'credit_days' => $ledger->credit_days,
                'is_active' => $ledger->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_integration_syncs');
        Schema::dropIfExists('acct_integrations');
        Schema::dropIfExists('acct_preferences');
        Schema::dropIfExists('acct_product_barcodes');
        Schema::dropIfExists('acct_parties');
        Schema::dropIfExists('acct_user_branches');
        Schema::dropIfExists('acct_user_companies');
        Schema::dropIfExists('acct_roles');
    }
};
