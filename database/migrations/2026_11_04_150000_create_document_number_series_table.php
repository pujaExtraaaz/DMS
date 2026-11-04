<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_number_series')) {
            Schema::create('document_number_series', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('document_type', 40);
                $table->string('name')->nullable();
                $table->string('prefix', 40)->default('');
                $table->string('suffix', 40)->nullable();
                $table->boolean('include_financial_year')->default(true);
                $table->string('financial_year_style', 20)->default('yy_yy');
                $table->string('separator', 5)->default('-');
                $table->unsignedTinyInteger('padding')->default(3);
                $table->string('numbering_method', 20)->default('automatic');
                $table->boolean('restart_each_fy')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'document_type']);
            });
        }

        if (Schema::hasTable('document_sequences') && ! Schema::hasColumn('document_sequences', 'series_key')) {
            Schema::table('document_sequences', function (Blueprint $table) {
                $table->string('series_key', 120)->nullable()->after('prefix');
            });
        }

        $exists = DB::table('document_number_series')->where('document_type', 'PO')->exists();
        if (! $exists) {
            DB::table('document_number_series')->insert([
                'company_id' => null,
                'document_type' => 'PO',
                'name' => 'Purchase Order',
                'prefix' => 'AVITPO',
                'suffix' => null,
                'include_financial_year' => true,
                'financial_year_style' => 'yy_yy',
                'separator' => '-',
                'padding' => 3,
                'numbering_method' => 'automatic',
                'restart_each_fy' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_sequences') && Schema::hasColumn('document_sequences', 'series_key')) {
            Schema::table('document_sequences', function (Blueprint $table) {
                $table->dropColumn('series_key');
            });
        }
        Schema::dropIfExists('document_number_series');
    }
};
