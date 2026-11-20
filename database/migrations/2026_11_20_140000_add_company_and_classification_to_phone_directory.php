<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_directory_contacts')) {
            return;
        }

        Schema::table('phone_directory_contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('phone_directory_contacts', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('companies')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('phone_directory_contacts', 'customer_type_id')) {
                $table->foreignId('customer_type_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('customer_types')
                    ->nullOnDelete();
            }
        });

        // Backfill company_id from existing company_name if matches any company in companies table
        if (Schema::hasTable('companies')) {
            $companies = DB::table('companies')->select('id', 'name')->get();
            foreach ($companies as $comp) {
                if (! empty($comp->name)) {
                    DB::table('phone_directory_contacts')
                        ->whereNull('company_id')
                        ->whereRaw('LOWER(TRIM(company_name)) = ?', [mb_strtolower(trim($comp->name))])
                        ->update(['company_id' => $comp->id]);
                }
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('phone_directory_contacts')) {
            return;
        }

        Schema::table('phone_directory_contacts', function (Blueprint $table) {
            if (Schema::hasColumn('phone_directory_contacts', 'customer_type_id')) {
                $table->dropConstrainedForeignId('customer_type_id');
            }
            if (Schema::hasColumn('phone_directory_contacts', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};
