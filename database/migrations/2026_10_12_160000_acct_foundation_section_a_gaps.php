<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_locations')) {
            Schema::create('acct_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
                $table->string('code', 30)->nullable();
                $table->string('name', 120);
                $table->string('address', 500)->nullable();
                $table->string('city', 80)->nullable();
                $table->string('state', 80)->nullable();
                $table->string('country', 80)->nullable();
                $table->string('pincode', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'name']);
            });
        }

        Schema::table('acct_gst_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_gst_registrations', 'location_id')) {
                $table->foreignId('location_id')->nullable()->after('branch_id')
                    ->constrained('acct_locations')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_gst_registrations', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('state');
            }
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_ledgers', 'parent_ledger_id')) {
                $table->foreignId('parent_ledger_id')->nullable()->after('account_group_id')
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_ledgers', 'is_control_account')) {
                $table->boolean('is_control_account')->default(false)->after('is_system');
            }
        });

        if (! Schema::hasTable('acct_exchange_rates')) {
            Schema::create('acct_exchange_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('currency_id')->constrained('acct_currencies')->cascadeOnDelete();
                $table->date('rate_date');
                $table->decimal('rate', 18, 6);
                $table->timestamps();
                $table->unique(['company_id', 'currency_id', 'rate_date']);
            });
        }

        if (! Schema::hasTable('acct_ledger_mirrors')) {
            Schema::create('acct_ledger_mirrors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('source_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('counterparty_company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('counterparty_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['source_ledger_id', 'counterparty_company_id'], 'acct_ledger_mirrors_src_cp_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_ledger_mirrors');
        Schema::dropIfExists('acct_exchange_rates');

        if (Schema::hasColumn('acct_ledgers', 'parent_ledger_id')) {
            Schema::table('acct_ledgers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_ledger_id');
            });
        }
        if (Schema::hasColumn('acct_ledgers', 'is_control_account')) {
            Schema::table('acct_ledgers', function (Blueprint $table) {
                $table->dropColumn('is_control_account');
            });
        }

        if (Schema::hasColumn('acct_gst_registrations', 'location_id')) {
            Schema::table('acct_gst_registrations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('location_id');
            });
        }
        if (Schema::hasColumn('acct_gst_registrations', 'is_default')) {
            Schema::table('acct_gst_registrations', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }

        Schema::dropIfExists('acct_locations');
    }
};
