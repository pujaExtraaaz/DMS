<?php

namespace Tally\Http\Controllers;

use Tally\Audit\AuditLogger;
use Tally\Context\WorkspaceContext;
use Tally\DataExchange\Import\ImportCatalog;
use Tally\DataExchange\Import\MasterImporter;
use Tally\DataExchange\Import\TallyPrimeFile;
use Tally\DataExchange\Spreadsheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function index(Request $request, WorkspaceContext $context, ImportCatalog $catalog): View
    {
        $dataset = (string) $request->query('dataset', 'ledgers');
        $definition = null;

        try {
            $definition = $catalog->find($dataset);
        } catch (\InvalidArgumentException) {
            $dataset = 'ledgers';
            $definition = $catalog->find($dataset);
        }

        return view('tally::utilities.import', [
            'company' => $context->company(),
            'definitions' => $catalog->all(),
            'definition' => $definition,
            'dataset' => $dataset,
            'importErrors' => [],
        ]);
    }

    public function template(ImportCatalog $catalog, string $dataset, Request $request): StreamedResponse
    {
        $definition = $catalog->find($dataset);
        $format = $request->query('format') === 'xlsx' ? 'xlsx' : 'csv';
        $filename = $definition->key().'-template.'.$format;
        $type = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($definition, $format) {
            $path = tempnam(sys_get_temp_dir(), 'tally-template');
            $target = $path.'.'.$format;
            @unlink($path);
            Spreadsheet::write($target, $definition->headers(), $definition->sample(), $format);
            readfile($target);
            @unlink($target);
        }, $filename, ['Content-Type' => $type]);
    }

    public function store(Request $request, WorkspaceContext $context, ImportCatalog $catalog, MasterImporter $importer, string $dataset): View|RedirectResponse
    {
        $definition = $catalog->find($dataset);
        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $uploaded = $request->file('file');
        $extension = strtolower($uploaded->getClientOriginalExtension() ?: 'csv');
        $path = tempnam(sys_get_temp_dir(), 'tally-import');
        $target = $path.'.'.$extension;
        @unlink($path);
        copy($uploaded->getRealPath(), $target);

        try {
            $outcome = $importer->import($definition, $target, $context->company());
        } finally {
            @unlink($target);
        }

        if (! $outcome->imported) {
            Log::warning('Import was rejected.', [
                'dataset' => $definition->key(),
                'company_id' => $context->company()?->id,
                'rows' => count($outcome->errors),
            ]);
            app(AuditLogger::class)->record('import_failed', 'import', $context->company(), 'Import of '.$definition->label().' was rejected.');

            return view('tally::utilities.import', [
                'company' => $context->company(),
                'definitions' => $catalog->all(),
                'definition' => $definition,
                'dataset' => $dataset,
                'importErrors' => $outcome->errors,
            ]);
        }

        app(AuditLogger::class)->record('import_completed', 'import', $context->company(), $outcome->count.' '.$definition->label().' imported.');

        return redirect()
            ->route('books.tally.utilities.import', ['dataset' => $dataset])
            ->with('status', $outcome->count.' '.$definition->label().' imported.');
    }

    public function tally(Request $request, WorkspaceContext $context, ImportCatalog $catalog, TallyPrimeFile $file): View|RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();

        if (! $company || ! $branch || ! $year) {
            return back()->with('error', 'Select a company, branch, and financial year before importing a Tally file.');
        }

        $request->validate([
            'tally_file' => ['required', 'file', 'extensions:xml,txt', 'max:20480'],
        ]);

        try {
            $result = $file->import($request->file('tally_file')->getContent(), $company, $branch, $year, $request->user());
        } catch (ValidationException $exception) {
            return view('tally::utilities.import', [
                'company' => $company,
                'definitions' => $catalog->all(),
                'definition' => $catalog->find('ledgers'),
                'dataset' => 'ledgers',
                'importErrors' => [['row' => 0, 'message' => collect($exception->errors())->flatten()->first()]],
            ]);
        }

        $message = $result['ledgers'].' ledgers and '.$result['vouchers'].' vouchers imported from TallyPrime.';

        if ($result['skipped'] !== []) {
            $message .= ' Skipped '.implode(', ', array_slice($result['skipped'], 0, 8)).'.';
        }

        return redirect()->route('books.tally.utilities.import')->with('status', $message);
    }
}
