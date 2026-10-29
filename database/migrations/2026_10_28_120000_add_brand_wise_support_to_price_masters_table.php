<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('price_masters')) {
            Schema::table('price_masters', function (Blueprint $table) {
                if (! Schema::hasColumn('price_masters', 'type')) {
                    $table->string('type', 30)->default('customer_type')->after('id');
                }

                if (! Schema::hasColumn('price_masters', 'name')) {
                    $table->string('name')->nullable()->after('type');
                }

                if (! Schema::hasColumn('price_masters', 'brand_id')) {
                    $table->foreignId('brand_id')->nullable()->after('customer_type_id')->constrained('brands')->nullOnDelete();
                }

                if (Schema::hasColumn('price_masters', 'customer_type_id')) {
                    $table->unsignedBigInteger('customer_type_id')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('price_masters')) {
            Schema::table('price_masters', function (Blueprint $table) {
                if (Schema::hasColumn('price_masters', 'brand_id')) {
                    $table->dropForeign(['brand_id']);
                    $table->dropColumn('brand_id');
                }

                if (Schema::hasColumn('price_masters', 'name')) {
                    $table->dropColumn('name');
                }

                if (Schema::hasColumn('price_masters', 'type')) {
                    $table->dropColumn('type');
                }

                if (Schema::hasColumn('price_masters', 'customer_type_id')) {
                    $table->unsignedBigInteger('customer_type_id')->nullable(false)->change();
                }
            });
        }
    }
};

