<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropCodeFrom('brands', [
            'brands_code_unique',
        ]);

        $this->dropCodeFrom('categories', [
            'categories_code_unique',
        ]);

        $this->dropCodeFrom('sub_categories', [
            'sub_categories_category_id_code_unique',
            'sub_categories_code_unique',
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('brands', 'code')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->string('code', 30)->nullable()->unique()->after('name');
            });
        }

        if (! Schema::hasColumn('categories', 'code')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('code', 30)->nullable()->unique()->after('name');
            });
        }

        if (! Schema::hasColumn('sub_categories', 'code')) {
            Schema::table('sub_categories', function (Blueprint $table) {
                $table->string('code', 30)->nullable()->after('name');
                $table->unique(['category_id', 'code']);
            });
        }
    }

    private function dropCodeFrom(string $table, array $indexNames): void
    {
        if (! Schema::hasColumn($table, 'code')) {
            return;
        }

        $existing = collect(Schema::getIndexes($table))
            ->pluck('name')
            ->filter()
            ->all();

        foreach ($indexNames as $indexName) {
            if (! in_array($indexName, $existing, true)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropUnique($indexName);
            });
        }

        // Re-check after index drops in case a prior partial run already removed the column.
        if (Schema::hasColumn($table, 'code')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('code');
            });
        }
    }
};
