<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('acct_price_list_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('acct_price_lists')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('acct_products')->cascadeOnDelete();
            $table->decimal('rate', 15, 2);
            $table->timestamps();

            $table->unique(['price_list_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_price_list_lines');
        Schema::dropIfExists('acct_price_lists');
    }
};
