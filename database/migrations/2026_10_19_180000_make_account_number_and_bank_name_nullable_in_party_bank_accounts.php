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
        if (Schema::hasTable('party_bank_accounts')) {
            Schema::table('party_bank_accounts', function (Blueprint $table) {
                if (Schema::hasColumn('party_bank_accounts', 'account_number')) {
                    $table->string('account_number', 50)->nullable()->change();
                }
                if (Schema::hasColumn('party_bank_accounts', 'bank_name')) {
                    $table->string('bank_name', 255)->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('party_bank_accounts')) {
            Schema::table('party_bank_accounts', function (Blueprint $table) {
                if (Schema::hasColumn('party_bank_accounts', 'account_number')) {
                    $table->string('account_number', 50)->nullable(false)->change();
                }
                if (Schema::hasColumn('party_bank_accounts', 'bank_name')) {
                    $table->string('bank_name', 255)->nullable(false)->change();
                }
            });
        }
    }
};

