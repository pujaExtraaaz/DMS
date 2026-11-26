<?php
namespace App\Http\Controllers\Master;
use App\Domains\Crm\Imports\LeadsImport;
use App\Domains\Master\Imports\BranchesImport;
use App\Domains\Master\Imports\BrandsImport;
use App\Domains\Master\Imports\BulkImportCatalog;
use App\Domains\Master\Imports\CategoriesImport;
use App\Domains\Master\Imports\CompaniesImport;
use App\Domains\Master\Imports\FinancialYearsImport;
use App\Domains\Master\Imports\ImportCancelledException;
use App\Domains\Master\Imports\PartiesImport;
use App\Domains\Master\Imports\PriceMasterImport;
use App\Domains\Master\Imports\ProductsImport;
use App\Domains\Master\Imports\ProductTypesImport;
use App\Domains\Master\Imports\SubCategoriesImport;
use App\Domains\Master\Imports\UomsImport;
use App\Domains\Master\Imports\WarehousesImport;
use App\Domains\PhoneDirectory\Imports\PhoneDirectoryImport;
use App\Domains\Purchasing\Imports\PurchaseOrdersImport;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Models\UomMapping;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk import + template downloads for Setup, Catalog, Products, Parties, Price Master,
 * CRM Leads, Phone Directory, and Purchase Orders.
 */
class BulkImportController extends Controller
{
    public function index(): View
    {
        $summary = session('bulk_import_summary');
        $allUoms = Uom::query()->where('is_active', true)->orderBy('name')->get();
        $uomMappings = UomMapping::query()->with('uom')->latest()->get();

        return view('masters.bulk-import.index', [
            'summary' => $summary,
            'allUoms' => $allUoms,
            'uomMappings' => $uomMappings,
            'importTypes' => BulkImportCatalog::all(),
        ]);
    }

    public function template(string $type): StreamedResponse
    {
        $definition = BulkImportCatalog::get($type);
        $columns = $definition['columns'];
        $sample = $definition['sample'];

        return response()->streamDownload(function () use ($columns, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            fputcsv($out, $sample);
            fclose($out);
        }, "template-{$type}.csv", ['Content-Type' => 'text/csv']);
    }

    public function upload(Request $request, string $type): RedirectResponse|JsonResponse
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '300');

        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:20480',
        ]);

        $companyId = auth()->user()?->company_id;
        $progressId = $this->progressId($request);

        $import = match ($type) {
            'companies' => new CompaniesImport(),
            'branches' => new BranchesImport(),
            'warehouses' => new WarehousesImport(),
            'financial-years' => new FinancialYearsImport(),
            'uoms' => new UomsImport(),
            'brands' => new BrandsImport($companyId),
            'categories' => new CategoriesImport($companyId),
            'sub-categories' => new SubCategoriesImport($companyId),
            'product-types' => new ProductTypesImport($companyId),
            'products' => new ProductsImport($companyId, $progressId),
            'parties' => new PartiesImport($companyId),
            'price-master' => new PriceMasterImport(),
            'leads' => new LeadsImport($companyId),
            'phone-directory' => new PhoneDirectoryImport(),
            'purchase-orders' => new PurchaseOrdersImport($companyId),
            default => abort(404, 'Unknown import type'),
        };

        // Persist uploaded file so it can be retried if any rows fail
        $storedPath = $request->file('file')->store('imports/pending', 'local');
        $fullPath = Storage::disk('local')->path($storedPath);

        try {
            $import->import($fullPath);
        } catch (ImportCancelledException) {
            Storage::disk('local')->delete($storedPath);
            $this->forgetProgressArtifacts($progressId);

            $cancelled = [
                'type' => $type,
                'cancelled' => true,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => [],
                'missing_uoms' => [],
                'pending_file' => null,
                'errors_file' => null,
            ];

            if ($progressId) {
                return response()->json($cancelled);
            }

            return back()->with('error', 'Import cancelled. Nothing was saved.');
        }

        $failedCount = property_exists($import, 'failed') ? $import->failed : count($import->errors);
        $summary = [
            'type' => $type,
            'created' => $import->created,
            'updated' => $import->updated,
            'skipped' => $import->skipped,
            'failed' => $failedCount,
            'errors' => $import->errors,
            'missing_uoms' => method_exists($import, 'getMissingUoms') ? $import->getMissingUoms() : ($import->missingUoms ?? []),
            'pending_file' => ! empty($import->errors) ? $storedPath : null,
            'errors_file' => null,
        ];

        if (empty($import->errors)) {
            Storage::disk('local')->delete($storedPath);
        } else {
            $path = 'imports/errors-'.$type.'-'.now()->format('Ymd-His').'.csv';
            $csv = "Row Number,Product Identifier,Imported UOM,Field,Reason for Failure\n";
            foreach ($import->errors as $err) {
                $csv .= sprintf(
                    "%d,\"%s\",\"%s\",\"%s\",\"%s\"\n",
                    $err['row'] ?? 0,
                    str_replace('"', '""', (string) ($err['product_identifier'] ?? 'N/A')),
                    str_replace('"', '""', (string) ($err['imported_uom'] ?? 'N/A')),
                    str_replace('"', '""', (string) ($err['field'] ?? '')),
                    str_replace('"', '""', (string) ($err['message'] ?? ''))
                );
            }
            Storage::disk('local')->put($path, $csv);
            $summary['errors_file'] = $path;
        }

        session(['bulk_import_summary' => $summary]);
        $this->forgetProgressArtifacts($progressId);

        if ($progressId) {
            return response()->json($summary);
        }

        return $this->flashSuccess(
            sprintf(
                '%s import complete: %d created, %d updated, %d skipped, %d failed.',
                ucfirst(str_replace('-', ' ', $type)),
                $import->created,
                $import->updated,
                $import->skipped,
                $failedCount
            ),
            'masters.bulk-import.index',
        );
    }

    public function progress(string $id): JsonResponse
    {
        abort_unless($this->validProgressId($id), 404);

        $path = 'imports/progress/'.$id.'.json';
        if (! Storage::disk('local')->exists($path)) {
            return response()->json([
                'processed' => 0,
                'total' => 0,
                'percent' => 0,
                'status' => 'reading',
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ]);
        }

        $data = json_decode((string) Storage::disk('local')->get($path), true);

        return response()->json(is_array($data) ? $data : ['percent' => 0, 'status' => 'reading']);
    }

    public function cancel(string $id): JsonResponse
    {
        abort_unless($this->validProgressId($id), 404);

        Storage::disk('local')->makeDirectory('imports/progress');
        Storage::disk('local')->put('imports/progress/'.$id.'.cancel', '1');

        return response()->json(['cancelled' => true]);
    }

    protected function progressId(Request $request): ?string
    {
        $id = (string) $request->input('progress_id', '');

        return $this->validProgressId($id) ? $id : null;
    }

    protected function validProgressId(string $id): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9\-]{8,80}$/', $id);
    }

    protected function forgetProgressArtifacts(?string $progressId): void
    {
        if (! $progressId) {
            return;
        }

        Storage::disk('local')->delete([
            'imports/progress/'.$progressId.'.json',
            'imports/progress/'.$progressId.'.cancel',
        ]);
    }

    public function retry(Request $request): RedirectResponse
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '300');

        $summary = session('bulk_import_summary');
        if (! $summary || empty($summary['pending_file'])) {
            return back()->with('error', 'No pending import file available to retry.');
        }

        $relativePath = $summary['pending_file'];
        if (! Storage::disk('local')->exists($relativePath)) {
            return back()->with('error', 'The pending import file was not found.');
        }

        $fullPath = Storage::disk('local')->path($relativePath);
        $type = $summary['type'] ?? 'products';
        $companyId = auth()->user()?->company_id;

        $import = match ($type) {
            'products' => new ProductsImport($companyId),
            'price-master' => new PriceMasterImport(),
            default => new ProductsImport($companyId),
        };

        $import->import($fullPath);

        $failedCount = property_exists($import, 'failed') ? $import->failed : count($import->errors);
        $summary['created'] = $import->created;
        $summary['updated'] = $import->updated;
        $summary['skipped'] = $import->skipped;
        $summary['failed'] = $failedCount;
        $summary['errors'] = $import->errors;
        $summary['missing_uoms'] = method_exists($import, 'getMissingUoms') ? $import->getMissingUoms() : ($import->missingUoms ?? []);

        if (empty($import->errors)) {
            Storage::disk('local')->delete($relativePath);
            $summary['pending_file'] = null;
            $summary['errors_file'] = null;
        } else {
            $path = 'imports/errors-'.$type.'-'.now()->format('Ymd-His').'.csv';
            $csv = "Row Number,Product Identifier,Imported UOM,Field,Reason for Failure\n";
            foreach ($import->errors as $err) {
                $csv .= sprintf(
                    "%d,\"%s\",\"%s\",\"%s\",\"%s\"\n",
                    $err['row'] ?? 0,
                    str_replace('"', '""', (string) ($err['product_identifier'] ?? 'N/A')),
                    str_replace('"', '""', (string) ($err['imported_uom'] ?? 'N/A')),
                    str_replace('"', '""', (string) ($err['field'] ?? '')),
                    str_replace('"', '""', (string) ($err['message'] ?? ''))
                );
            }
            Storage::disk('local')->put($path, $csv);
            $summary['errors_file'] = $path;
        }

        session(['bulk_import_summary' => $summary]);

        return back()->with(
            'status',
            sprintf('Retry completed: %d created, %d updated, %d skipped, %d failed.', $import->created, $import->updated, $import->skipped, $failedCount)
        );
    }

    public function mapUom(Request $request): RedirectResponse
    {
        $request->validate([
            'imported_uom' => 'required|string|max:100',
            'uom_id' => 'required|exists:uoms,id',
        ]);

        $importedUom = trim($request->input('imported_uom'));
        $uom = Uom::findOrFail($request->input('uom_id'));

        UomMapping::updateOrCreate(
            ['imported_uom' => $importedUom],
            ['uom_id' => $uom->id]
        );

        $summary = session('bulk_import_summary');
        if (is_array($summary) && isset($summary['missing_uoms'][$importedUom])) {
            unset($summary['missing_uoms'][$importedUom]);
            session(['bulk_import_summary' => $summary]);
        }

        return back()->with('status', "UOM '{$importedUom}' successfully mapped to {$uom->name} ({$uom->code}).");
    }

    public function addUom(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'imported_uom' => 'nullable|string|max:100',
        ]);

        $name = trim($request->input('name'));
        $code = strtoupper(trim($request->input('code')));
        $importedUom = trim((string) $request->input('imported_uom'));

        $uom = Uom::query()
            ->whereRaw('UPPER(code) = ?', [$code])
            ->orWhereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();

        if (! $uom) {
            $uom = Uom::create([
                'name' => $name,
                'code' => $code,
                'is_active' => true,
            ]);
        }

        if ($importedUom !== '') {
            UomMapping::updateOrCreate(
                ['imported_uom' => $importedUom],
                ['uom_id' => $uom->id]
            );
        }

        $summary = session('bulk_import_summary');
        if (is_array($summary) && $importedUom !== '' && isset($summary['missing_uoms'][$importedUom])) {
            unset($summary['missing_uoms'][$importedUom]);
            session(['bulk_import_summary' => $summary]);
        }

        return back()->with('status', "UOM {$uom->name} ({$uom->code}) successfully added to UOM Master.");
    }

    public function deleteUomMapping(UomMapping $mapping): RedirectResponse
    {
        $imported = $mapping->imported_uom;
        $mapping->delete();

        return back()->with('status', "UOM mapping for '{$imported}' removed.");
    }

    public function clearSummary(): RedirectResponse
    {
        $summary = session('bulk_import_summary');
        if ($summary && ! empty($summary['pending_file'])) {
            Storage::disk('local')->delete($summary['pending_file']);
        }
        session()->forget('bulk_import_summary');

        return back()->with('status', 'Import summary dismissed.');
    }

    public function errors(string $filename): StreamedResponse
    {
        abort_unless(str_starts_with($filename, 'errors-') && str_ends_with($filename, '.csv'), 404);
        $path = 'imports/'.$filename;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->streamDownload(function () use ($path) {
            echo Storage::disk('local')->get($path);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
