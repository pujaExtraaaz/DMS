<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('target_periods', function (Blueprint $table) {
            $table->string('target_category', 30)->nullable()->after('status');
            $table->foreignId('brand_id')->nullable()->after('target_category')->constrained('brands')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->after('brand_id')->constrained('products')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('product_id')->constrained('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('target_periods', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['target_category', 'brand_id', 'product_id', 'supplier_id']);
        });
    }
};

