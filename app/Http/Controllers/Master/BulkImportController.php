<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Imports\PartiesImport;
use App\Domains\Master\Imports\PriceMasterImport;
use App\Domains\Master\Imports\ProductsImport;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk import + template downloads for Products, Parties, and Price Master.
 * Templates are generated in-memory as CSVs so operators can populate them and
 * upload the same file back — matches the AVIT 11/09/2026 requirement #22.
 */
class BulkImportController extends Controller
{
    public function index(): View
    {
        $summary = Session::pull('bulk_import_summary');

        return view('masters.bulk-import.index', ['summary' => $summary]);
    }

    public function template(string $type): StreamedResponse
    {
        $columns = match ($type) {
            'products' => ['name', 'sku', 'hsn_code', 'uom_code', 'brand', 'category', 'color_variant',
                            'purchase_price', 'selling_price', 'trade_price', 'mrp', 'tax_rate',
                            'sender_warranty_months', 'customer_warranty_months', 'discount_percent'],
            'parties' => ['name', 'code', 'party_type', 'classification', 'area', 'route',
                          'phone', 'email', 'gstin', 'address', 'state', 'pincode',
                          'credit_limit', 'credit_days', 'interest_rate',
                          'contact_name', 'contact_phone', 'contact_email', 'contact_role'],
            'price-master' => ['customer_type', 'sku', 'uom_code', 'rate', 'min_qty'],
            default => abort(404, 'Unknown template'),
        };

        $sample = match ($type) {
            'products' => ['Widget X', '', '84713010', 'PCS', 'Acme', 'Widgets', 'Red', 100, 150, 130, 175, 18, 12, 24, 0],
            'parties' => ['Sample Distributor', '', 'customer', 'Retail', 'Zone A', 'Route 1', '9999999999', 'ops@sample.co', '', '#12, MG Road', 'Karnataka', '560001', 50000, 30, 18, 'Ravi', '9999888877', 'ravi@sample.co', 'Owner'],
            'price-master' => ['Retail', 'PROD-1-00001', 'PCS', 150, 0],
        };

        return response()->streamDownload(function () use ($columns, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            fputcsv($out, $sample);
            fclose($out);
        }, "template-{$type}.csv", ['Content-Type' => 'text/csv']);
    }

    public function upload(Request $request, string $type): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:20480',
        ]);

        $import = match ($type) {
            'products' => new ProductsImport(auth()->user()?->company_id),
            'parties' => new PartiesImport(auth()->user()?->company_id),
            'price-master' => new PriceMasterImport(),
            default => abort(404, 'Unknown import type'),
        };

        $import->import($request->file('file'));

        $summary = [
            'type' => $type,
            'created' => $import->created,
            'updated' => $import->updated,
            'skipped' => $import->skipped,
            'errors' => $import->errors,
            'errors_file' => null,
        ];

        if (! empty($import->errors)) {
            $path = 'imports/errors-'.$type.'-'.now()->format('Ymd-His').'.csv';
            $csv = "row,field,message\n";
            foreach ($import->errors as $err) {
                $csv .= sprintf("%d,%s,\"%s\"\n", $err['row'], $err['field'], str_replace('"', '""', $err['message']));
            }
            Storage::disk('local')->put($path, $csv);
            $summary['errors_file'] = $path;
        }

        Session::flash('bulk_import_summary', $summary);

        return $this->flashSuccess(
            sprintf('%s import complete: %d created, %d updated, %d skipped.', ucfirst(str_replace('-', ' ', $type)), $import->created, $import->updated, $import->skipped),
            'masters.bulk-import.index',
        );
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
