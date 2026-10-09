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
        Schema::create('acct_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->string('address', 500)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('pincode', 12)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acct_branches');
    }
};
