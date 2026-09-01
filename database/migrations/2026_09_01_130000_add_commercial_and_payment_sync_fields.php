<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('selling_price', 12, 2)->default(0)->after('tax_rate');
            $table->string('hsn_code', 20)->nullable()->after('sku');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('state', 100)->nullable()->after('address');
            $table->string('pincode', 12)->nullable()->after('state');
            $table->string('shipping_name')->nullable()->after('pincode');
            $table->text('shipping_address')->nullable()->after('shipping_name');
            $table->string('shipping_state', 100)->nullable()->after('shipping_address');
            $table->string('shipping_pincode', 12)->nullable()->after('shipping_state');
            $table->string('shipping_gstin', 20)->nullable()->after('shipping_pincode');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference_no', 100)->nullable()->unique()->after('payment_no');
        });

        Schema::table('payment_links', function (Blueprint $table) {
            $table->string('provider', 40)->default('internal')->after('url');
            $table->string('provider_reference', 100)->nullable()->unique()->after('provider');
            $table->timestamp('paid_at')->nullable()->after('expires_at');
            $table->json('webhook_payload')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->dropColumn(['provider', 'provider_reference', 'paid_at', 'webhook_payload']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['reference_no']);
            $table->dropColumn('reference_no');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['state', 'pincode', 'shipping_name', 'shipping_address', 'shipping_state', 'shipping_pincode', 'shipping_gstin']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['selling_price', 'hsn_code']);
        });
    }
};
