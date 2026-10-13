<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_goods_receipts')) {
            return;
        }

        if (! Schema::hasColumn('acct_goods_receipts', 'stock_transaction_id')) {
            Schema::table('acct_goods_receipts', function (Blueprint $table) {
                $table->foreignId('stock_transaction_id')
                    ->nullable()
                    ->after('godown_id')
                    ->constrained('acct_stock_transactions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('acct_goods_receipts')) {
            return;
        }

        if (Schema::hasColumn('acct_goods_receipts', 'stock_transaction_id')) {
            Schema::table('acct_goods_receipts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('stock_transaction_id');
            });
        }
    }
};
