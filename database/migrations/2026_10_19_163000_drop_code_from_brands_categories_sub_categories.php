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

    /**
     * @param  list<string>  $indexNames
     */
    private function dropCodeFrom(string $table, array $indexNames): void
    {
        if (! Schema::hasColumn($table, 'code')) {
            return;
        }

        $indexes = collect(Schema::getIndexes($table))->keyBy('name');
        $restore = [];

        foreach ($indexNames as $indexName) {
            $index = $indexes->get($indexName);

            if (! $index) {
                continue;
            }

            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if (isset($restore[$foreignKey['name']])) {
                    continue;
                }

                if (! $this->indexSupports($index['columns'], $foreignKey['columns'])) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($foreignKey) {
                    $blueprint->dropForeign($foreignKey['name']);
                });

                $restore[$foreignKey['name']] = $foreignKey;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropUnique($indexName);
            });
        }

        if (Schema::hasColumn($table, 'code')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('code');
            });
        }

        foreach ($restore as $foreignKey) {
            Schema::table($table, function (Blueprint $blueprint) use ($foreignKey) {
                $blueprint->foreign($foreignKey['columns'], $foreignKey['name'])
                    ->references($foreignKey['foreign_columns'])
                    ->on($foreignKey['foreign_table'])
                    ->onUpdate($foreignKey['on_update'])
                    ->onDelete($foreignKey['on_delete']);
            });
        }
    }

    /**
     * @param  list<string>  $indexColumns
     * @param  list<string>  $foreignColumns
     */
    private function indexSupports(array $indexColumns, array $foreignColumns): bool
    {
        return array_slice(array_values($indexColumns), 0, count($foreignColumns)) === array_values($foreignColumns);
    }
};
