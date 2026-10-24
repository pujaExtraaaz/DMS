<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addVoucherTypeColumns();
        $this->addSequenceColumns();
        $this->replaceSequenceUnique(
            ['company_id', 'branch_id', 'financial_year_id', 'voucher_type', 'period_key']
        );
    }

    public function down(): void
    {
        $this->replaceSequenceUnique(
            ['company_id', 'branch_id', 'financial_year_id', 'voucher_type']
        );

        Schema::table('acct_voucher_sequences', function (Blueprint $table) {
            if (Schema::hasColumn('acct_voucher_sequences', 'period_key')) {
                $table->dropColumn('period_key');
            }
            if (Schema::hasColumn('acct_voucher_sequences', 'suffix')) {
                $table->dropColumn('suffix');
            }
        });

        Schema::table('acct_voucher_type_masters', function (Blueprint $table) {
            foreach (['restart', 'suffix', 'numbering_method'] as $column) {
                if (Schema::hasColumn('acct_voucher_type_masters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function addVoucherTypeColumns(): void
    {
        Schema::table('acct_voucher_type_masters', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_voucher_type_masters', 'numbering_method')) {
                $table->string('numbering_method', 24)->default('automatic')->after('category');
            }
            if (! Schema::hasColumn('acct_voucher_type_masters', 'suffix')) {
                $table->string('suffix', 12)->nullable()->after('prefix');
            }
            if (! Schema::hasColumn('acct_voucher_type_masters', 'restart')) {
                $table->string('restart', 16)->default('yearly')->after('padding');
            }
        });
    }

    private function addSequenceColumns(): void
    {
        Schema::table('acct_voucher_sequences', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_voucher_sequences', 'suffix')) {
                $table->string('suffix', 12)->nullable()->after('prefix');
            }
            if (! Schema::hasColumn('acct_voucher_sequences', 'period_key')) {
                $table->string('period_key', 16)->default('')->after('voucher_type');
            }
        });
    }

    /**
     * MySQL uses this unique index as the supporting index for the company foreign key.
     * The foreign keys have to be removed before the index can be replaced.
     *
     * @param  list<string>  $columns
     */
    private function replaceSequenceUnique(array $columns): void
    {
        $current = collect(DB::select("SHOW INDEX FROM acct_voucher_sequences WHERE Key_name = 'voucher_sequences_scope_unique'"))
            ->sortBy('Seq_in_index')
            ->pluck('Column_name')
            ->values()
            ->all();

        if ($current === $columns) {
            return;
        }

        $foreignKeys = collect(DB::select("
            SELECT k.CONSTRAINT_NAME AS name,
                   k.COLUMN_NAME AS column_name,
                   k.REFERENCED_TABLE_NAME AS referenced_table,
                   k.REFERENCED_COLUMN_NAME AS referenced_column,
                   r.DELETE_RULE AS delete_rule,
                   r.UPDATE_RULE AS update_rule
            FROM information_schema.KEY_COLUMN_USAGE k
            JOIN information_schema.REFERENTIAL_CONSTRAINTS r
              ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
             AND r.TABLE_NAME = k.TABLE_NAME
             AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
            WHERE k.TABLE_SCHEMA = DATABASE()
              AND k.TABLE_NAME = 'acct_voucher_sequences'
              AND k.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY k.CONSTRAINT_NAME, k.ORDINAL_POSITION
        "))->groupBy('name');

        foreach ($foreignKeys->keys() as $name) {
            DB::statement('ALTER TABLE acct_voucher_sequences DROP FOREIGN KEY `'.$name.'`');
        }

        if ($current !== []) {
            DB::statement('ALTER TABLE acct_voucher_sequences DROP INDEX `voucher_sequences_scope_unique`');
        }

        Schema::table('acct_voucher_sequences', function (Blueprint $table) use ($columns) {
            $table->unique($columns, 'voucher_sequences_scope_unique');
        });

        foreach ($foreignKeys as $name => $columnsForKey) {
            $local = $columnsForKey->pluck('column_name')->implode('`, `');
            $referenced = $columnsForKey->pluck('referenced_column')->implode('`, `');
            $table = $columnsForKey->first()->referenced_table;
            $delete = $this->referentialAction($columnsForKey->first()->delete_rule);
            $update = $this->referentialAction($columnsForKey->first()->update_rule);

            DB::statement(
                'ALTER TABLE acct_voucher_sequences ADD CONSTRAINT `'.$name.'` FOREIGN KEY (`'.$local.'`) REFERENCES `'.$table.'` (`'.$referenced.'`) ON DELETE '.$delete.' ON UPDATE '.$update
            );
        }
    }

    private function referentialAction(string $rule): string
    {
        return match (strtoupper($rule)) {
            'CASCADE' => 'CASCADE',
            'SET NULL' => 'SET NULL',
            'SET DEFAULT' => 'SET DEFAULT',
            'RESTRICT' => 'RESTRICT',
            default => 'NO ACTION',
        };
    }
};