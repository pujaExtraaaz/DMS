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
        Schema::table('business_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('business_groups', 'proprietor_name')) {
                $table->string('proprietor_name', 191)->nullable()->after('name');
            }
            if (! Schema::hasColumn('business_groups', 'proprietor_relationship')) {
                $table->string('proprietor_relationship', 191)->nullable()->default('Common ownership and business oversight')->after('proprietor_name');
            }
            if (! Schema::hasColumn('business_groups', 'proprietor_email')) {
                $table->string('proprietor_email', 191)->nullable()->after('proprietor_relationship');
            }
            if (! Schema::hasColumn('business_groups', 'proprietor_phone')) {
                $table->string('proprietor_phone', 50)->nullable()->after('proprietor_email');
            }
            if (! Schema::hasColumn('business_groups', 'proprietor_pan')) {
                $table->string('proprietor_pan', 20)->nullable()->after('proprietor_phone');
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'business_type')) {
                $table->string('business_type', 100)->nullable()->default('Existing Business')->after('company_type');
            }
            if (! Schema::hasColumn('companies', 'main_work')) {
                $table->text('main_work')->nullable()->after('nature_of_business');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_groups', function (Blueprint $table) {
            $table->dropColumn([
                'proprietor_name',
                'proprietor_relationship',
                'proprietor_email',
                'proprietor_phone',
                'proprietor_pan',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'business_type',
                'main_work',
            ]);
        });
    }
};

