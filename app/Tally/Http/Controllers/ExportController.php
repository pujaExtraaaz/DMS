<?php

namespace Tally\Http\Controllers;

use Tally\Audit\AuditLogger;
use Tally\Context\WorkspaceContext;
use Tally\DataExchange\Export\DataExporter;
use Tally\DataExchange\Export\ExportCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(WorkspaceContext $context, ExportCatalog $catalog): View
    {
        return view('tally::utilities.export', [
            'company' => $context->company(),
            'year' => $context->financialYear(),
            'options' => $catalog->options(),
        ]);
    }

    public function download(Request $request, DataExporter $exporter, string $dataset): StreamedResponse|RedirectResponse
    {
        $format = $request->query('format') === 'xlsx' ? 'xlsx' : 'csv';

        try {
            $download = $exporter->download($dataset, $format);
        } catch (InvalidArgumentException $exception) {
            app(AuditLogger::class)->record('export_failed', 'export', null, 'Export of '.$dataset.' was rejected.');

            return redirect()->route('books.tally.utilities.export')->with('error', $exception->getMessage());
        }

        app(AuditLogger::class)->record('export_completed', 'export', null, 'Exported '.$dataset.' as '.$format.'.');

        return $download;
    }
}
