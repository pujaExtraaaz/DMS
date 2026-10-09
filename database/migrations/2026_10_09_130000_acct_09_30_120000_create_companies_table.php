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
        Schema::create('acct_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('pincode', 12)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->date('financial_year_start');
            $table->date('financial_year_end');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('gstin');
            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acct_companies');
    }
};
