<?php

use App\Console\Commands\UppercaseNamesCommand;
use App\Support\NameCase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (UppercaseNamesCommand::$targets as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $primaryKey = Schema::hasColumn($table, 'id') ? 'id' : null;
                $records = DB::table($table)->whereNotNull($column)->where($column, '!=', '')->get();

                foreach ($records as $record) {
                    $original = $record->{$column};
                    $uppercased = NameCase::format($original);

                    if ($original !== $uppercased) {
                        if ($primaryKey) {
                            DB::table($table)
                                ->where($primaryKey, $record->{$primaryKey})
                                ->update([$column => $uppercased]);
                        } else {
                            DB::table($table)
                                ->where($column, $original)
                                ->update([$column => $uppercased]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversal is not required as uppercase name standardisation is permanent.
    }
};

