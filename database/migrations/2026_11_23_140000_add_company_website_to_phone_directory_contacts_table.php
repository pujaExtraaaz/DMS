<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_directory_contacts')) {
            return;
        }

        Schema::table('phone_directory_contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('phone_directory_contacts', 'company_website')) {
                $table->string('company_website', 500)->nullable()->after('company_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('phone_directory_contacts')) {
            return;
        }

        Schema::table('phone_directory_contacts', function (Blueprint $table) {
            if (Schema::hasColumn('phone_directory_contacts', 'company_website')) {
                $table->dropColumn('company_website');
            }
        });
    }
};
