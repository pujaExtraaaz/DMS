<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_number_series') && Schema::hasColumn('document_number_series', 'numbering_method')) {
            Schema::table('document_number_series', function (Blueprint $table) {
                $table->string('numbering_method', 50)->default('automatic')->change();
            });
        }

        if (Schema::hasTable('quotations') && Schema::hasColumn('quotations', 'quotation_no')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->string('quotation_no', 50)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quotations') && Schema::hasColumn('quotations', 'quotation_no')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->string('quotation_no', 30)->change();
            });
        }

        if (Schema::hasTable('document_number_series') && Schema::hasColumn('document_number_series', 'numbering_method')) {
            Schema::table('document_number_series', function (Blueprint $table) {
                $table->string('numbering_method', 20)->default('automatic')->change();
            });
        }
    }
};
