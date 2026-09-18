<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Add purchase terms and conditions column if it doesn't exist
            if (! Schema::hasColumn('companies', 'purchase_terms_and_conditions')) {
                $table->text('purchase_terms_and_conditions')
                    ->nullable()
                    ->after('additional_details');
            }

            if (! Schema::hasColumn('companies', 'selling_terms_and_conditions')) {
                $table->text('selling_terms_and_conditions')
                    ->nullable()
                    ->after('purchase_terms_and_conditions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'selling_terms_and_conditions')) {
                $table->dropColumn('selling_terms_and_conditions');
            }

            if (Schema::hasColumn('companies', 'purchase_terms_and_conditions')) {
                $table->dropColumn('purchase_terms_and_conditions');
            }
        });
    }
};