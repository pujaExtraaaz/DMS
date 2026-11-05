<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('credit_status', 30)->default('open')->change();
            $table->string('credit_period_basis', 30)->default('cumulative')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 50)->nullable(false)->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->enum('credit_status', ['open', 'restricted', 'frozen'])->default('open')->change();
            $table->enum('credit_period_basis', ['monthly', 'quarterly', 'yearly', 'cumulative'])->default('cumulative')->change();
        });
    }
};