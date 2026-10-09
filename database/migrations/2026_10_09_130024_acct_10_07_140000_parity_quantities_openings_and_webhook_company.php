<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->widenQuantity('acct_invoice_lines');
        $this->widenQuantity('acct_purchase_order_lines');

        Schema::create('acct_ledger_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->string('opening_balance_type', 6);
            $table->timestamps();
            $table->index(['ledger_id', 'financial_year_id', 'branch_id'], 'ledger_openings_lookup');
        });

        if (Schema::hasTable('acct_webhook_endpoints') && ! Schema::hasColumn('acct_webhook_endpoints', 'company_id')) {
            Schema::table('acct_webhook_endpoints', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('user_id')->constrained('acct_companies')->nullOnDelete();
            });
        }

        $this->copyExistingOpenings();
    }

    public function down(): void
    {
        if (Schema::hasColumn('acct_webhook_endpoints', 'company_id')) {
            Schema::table('acct_webhook_endpoints', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }

        Schema::dropIfExists('acct_ledger_openings');

        $this->widenQuantity('acct_purchase_order_lines', 3);
        $this->widenQuantity('acct_invoice_lines', 3);
    }

    private function widenQuantity(string $table, int $places = 4): void
    {
        if (! Schema::hasTable($table) || Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($places) {
            $blueprint->decimal('quantity', 15, $places)->change();
        });
    }

    private function copyExistingOpenings(): void
    {
        $years = DB::table('acct_financial_years')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->unique('company_id');

        foreach ($years as $year) {
            $ledgers = DB::table('acct_ledgers')->where('company_id', $year->company_id)->get();

            foreach ($ledgers as $ledger) {
                DB::table('acct_ledger_openings')->insert([
                    'company_id' => $ledger->company_id,
                    'ledger_id' => $ledger->id,
                    'financial_year_id' => $year->id,
                    'branch_id' => null,
                    'opening_balance' => $ledger->opening_balance,
                    'opening_balance_type' => $ledger->opening_balance_type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
