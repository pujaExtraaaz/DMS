<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_number_series')) {
            Schema::table('document_number_series', function (Blueprint $table) {
                if (! Schema::hasColumn('document_number_series', 'starting_number')) {
                    $table->unsignedInteger('starting_number')->default(1)->after('padding');
                }
                if (! Schema::hasColumn('document_number_series', 'prefix_zero')) {
                    $table->boolean('prefix_zero')->default(true)->after('starting_number');
                }
                if (! Schema::hasColumn('document_number_series', 'restart_rules')) {
                    $table->json('restart_rules')->nullable()->after('restart_each_fy');
                }
                if (! Schema::hasColumn('document_number_series', 'prefix_rules')) {
                    $table->json('prefix_rules')->nullable()->after('restart_rules');
                }
                if (! Schema::hasColumn('document_number_series', 'suffix_rules')) {
                    $table->json('suffix_rules')->nullable()->after('prefix_rules');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_number_series')) {
            Schema::table('document_number_series', function (Blueprint $table) {
                $columns = ['starting_number', 'prefix_zero', 'restart_rules', 'prefix_rules', 'suffix_rules'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('document_number_series', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
