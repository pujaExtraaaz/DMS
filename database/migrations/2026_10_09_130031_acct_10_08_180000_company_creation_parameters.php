<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            $table->string('data_path')->nullable()->after('name');
            $table->string('mailing_name')->nullable()->after('legal_name');
            $table->string('mobile', 30)->nullable()->after('phone');
            $table->string('fax', 30)->nullable()->after('mobile');
            $table->string('website')->nullable()->after('email');
            $table->date('books_beginning_from')->nullable()->after('financial_year_end');
            $table->string('currency_symbol', 8)->default('₹')->after('books_beginning_from');
            $table->string('currency_formal_name', 20)->default('INR')->after('currency_symbol');
        });
    }

    public function down(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            $table->dropColumn([
                'data_path',
                'mailing_name',
                'mobile',
                'fax',
                'website',
                'books_beginning_from',
                'currency_symbol',
                'currency_formal_name',
            ]);
        });
    }
};
