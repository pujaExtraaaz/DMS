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
            if (! Schema::hasColumn('business_groups', 'parent_company_id')) {
                $table->foreignId('parent_company_id')->nullable()->after('code')->constrained('companies')->nullOnDelete();
            }
            if (! Schema::hasColumn('business_groups', 'incorporation_date')) {
                $table->date('incorporation_date')->nullable()->after('parent_company_id');
            }
            if (! Schema::hasColumn('business_groups', 'headquarters')) {
                $table->string('headquarters', 255)->nullable()->after('incorporation_date');
            }
            if (! Schema::hasColumn('business_groups', 'country')) {
                $table->string('country', 100)->nullable()->default('India')->after('headquarters');
            }
            if (! Schema::hasColumn('business_groups', 'industry')) {
                $table->string('industry', 150)->nullable()->after('country');
            }
            if (! Schema::hasColumn('business_groups', 'website')) {
                $table->string('website', 255)->nullable()->after('industry');
            }
            if (! Schema::hasColumn('business_groups', 'email')) {
                $table->string('email', 255)->nullable()->after('website');
            }
            if (! Schema::hasColumn('business_groups', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }
            if (! Schema::hasColumn('business_groups', 'logo_emoji')) {
                $table->string('logo_emoji', 20)->nullable()->default('🏢')->after('phone');
            }
            if (! Schema::hasColumn('business_groups', 'notes')) {
                $table->text('notes')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_groups', function (Blueprint $table) {
            if (Schema::hasColumn('business_groups', 'parent_company_id')) {
                $table->dropForeign(['parent_company_id']);
                $table->dropColumn('parent_company_id');
            }

            $columns = [
                'incorporation_date',
                'headquarters',
                'country',
                'industry',
                'website',
                'email',
                'phone',
                'logo_emoji',
                'notes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('business_groups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

