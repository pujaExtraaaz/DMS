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
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'series')) {
                $table->string('series', 100)->nullable()->after('sku');
            }
            if (! Schema::hasColumn('products', 'model')) {
                $table->string('model', 100)->nullable()->after('series');
            }
            if (! Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 100)->nullable()->after('model');
            }
            if (! Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('products', 'product_type')) {
                $table->dropColumn('product_type');
            }
            if (Schema::hasColumn('products', 'model')) {
                $table->dropColumn('model');
            }
            if (Schema::hasColumn('products', 'series')) {
                $table->dropColumn('series');
            }
        });
    }
};

