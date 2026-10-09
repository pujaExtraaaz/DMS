<?php

namespace Tally\DataExchange\Import;

use Tally\DataExchange\Spreadsheet;
use Tally\Models\Company;
use Illuminate\Support\Facades\DB;
use Throwable;

class MasterImporter
{
    public function import(ImportDefinition $definition, string $path, ?Company $company): ImportOutcome
    {
        if ($definition->requiresCompany() && $company === null) {
            return ImportOutcome::rejected([
                ['row' => 0, 'message' => 'Select a company before importing '.$definition->label().'.'],
            ]);
        }

        try {
            $table = Spreadsheet::read($path);
        } catch (Throwable $exception) {
            return ImportOutcome::rejected([
                ['row' => 0, 'message' => $exception->getMessage()],
            ]);
        }

        $missing = array_values(array_diff($definition->requiredHeaders(), $table['headers']));

        if ($missing !== []) {
            return ImportOutcome::rejected([
                ['row' => 1, 'message' => 'Missing required columns: '.implode(', ', $missing).'.'],
            ]);
        }

        if ($table['rows'] === []) {
            return ImportOutcome::rejected([
                ['row' => 0, 'message' => 'The file has no data rows.'],
            ]);
        }

        $batch = new ImportBatch($company, $table['rows']);
        $definition->validate($batch);

        if ($batch->failed()) {
            return ImportOutcome::rejected($batch->errors);
        }

        try {
            $count = DB::transaction(fn () => $definition->persist($batch));
        } catch (Throwable $exception) {
            report($exception);

            return ImportOutcome::rejected([
                ['row' => 0, 'message' => 'The import was rolled back because the data could not be saved.'],
            ]);
        }

        return ImportOutcome::imported($count);
    }
}
