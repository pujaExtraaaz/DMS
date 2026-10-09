<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_vouchers', function (Blueprint $table) {
            $table->string('name_on_receipt', 160)->nullable()->after('narration');
            $table->boolean('is_post_dated')->default(false)->after('name_on_receipt');
            $table->boolean('is_optional')->default(false)->after('is_post_dated');
        });

        Schema::table('acct_purchase_orders', function (Blueprint $table) {
            $table->foreignId('purchase_ledger_id')->nullable()->after('supplier_ledger_id')->constrained('acct_ledgers')->nullOnDelete();
            $table->decimal('delivery_charge', 15, 2)->default(0)->after('discount_total');
        });
    }

    public function down(): void
    {
        Schema::table('acct_purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_ledger_id');
            $table->dropColumn('delivery_charge');
        });

        Schema::table('acct_vouchers', function (Blueprint $table) {
            $table->dropColumn(['name_on_receipt', 'is_post_dated', 'is_optional']);
        });
    }
};
