<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->string('name');
            $table->string('symbol', 16);
            $table->unsignedTinyInteger('decimal_places')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'symbol']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('acct_product_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('acct_product_groups')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'parent_id']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('acct_godowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->string('address', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('acct_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('product_group_id')->constrained('acct_product_groups')->restrictOnDelete();
            $table->foreignId('primary_unit_id')->constrained('acct_units')->restrictOnDelete();
            $table->foreignId('alternate_unit_id')->nullable()->constrained('acct_units')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->string('barcode', 64)->nullable();
            $table->decimal('conversion_factor', 18, 6)->nullable();
            $table->decimal('purchase_rate', 15, 2)->default(0);
            $table->decimal('sales_rate', 15, 2)->default(0);
            $table->decimal('opening_quantity', 15, 4)->default(0);
            $table->decimal('opening_rate', 15, 2)->default(0);
            $table->decimal('opening_value', 15, 2)->default(0);
            $table->decimal('minimum_stock', 15, 4)->default(0);
            $table->decimal('reorder_level', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'barcode']);
            $table->index(['company_id', 'product_group_id']);
            $table->index(['company_id', 'primary_unit_id']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_products');
        Schema::dropIfExists('acct_godowns');
        Schema::dropIfExists('acct_product_groups');
        Schema::dropIfExists('acct_units');
    }
};
