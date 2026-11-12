<?php

namespace App\Http\Controllers\Master;

use App\Domains\Crm\Imports\LeadsImport;
use App\Domains\Master\Imports\BranchesImport;
use App\Domains\Master\Imports\BrandsImport;
use App\Domains\Master\Imports\CategoriesImport;
use App\Domains\Master\Imports\CompaniesImport;
use App\Domains\Master\Imports\FinancialYearsImport;
use App\Domains\Master\Imports\PartiesImport;
use App\Domains\Master\Imports\PriceMasterImport;
use App\Domains\Master\Imports\ProductsImport;
use App\Domains\Master\Imports\ProductTypesImport;
use App\Domains\Master\Imports\SubCategoriesImport;
use App\Domains\Master\Imports\UomsImport;
use App\Domains\Master\Imports\WarehousesImport;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Models\UomMapping;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk import + template downloads for Setup, Catalog, Products, Parties, Price Master, CRM Leads.
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
        ]);
    }

    public function template(string $type): StreamedResponse
    {
        $columns = match ($type) {
            'companies' => [
                'code', 'name', 'legal_name', 'company_type', 'business_group_code',
                'gstin', 'pan', 'address', 'city', 'state', 'pincode', 'phone', 'email', 'website',
                'is_active', 'is_default',
            ],
            'branches' => [
                'company_code', 'code', 'name', 'gstin', 'phone', 'state', 'pincode', 'address', 'is_active',
            ],
            'warehouses' => [
                'company_code', 'branch_code', 'code', 'name', 'address', 'is_default', 'is_active',
            ],
            'financial-years' => [
                'company_code', 'name', 'starts_on', 'ends_on', 'is_current', 'is_closed',
            ],
            'uoms' => ['code', 'name', 'is_active'],
            'brands' => ['company_code', 'name', 'detail', 'is_active'],
            'categories' => ['brand_name', 'name', 'is_active'],
            'sub-categories' => ['brand_name', 'category_name', 'name', 'is_active'],
            'product-types' => [
                'company_code', 'name', 'code', 'description', 'track_serial', 'is_active',
            ],
            'products' => [
                'name', 'part_number', 'model', 'product_type', 'brand', 'category', 'sub_category',
                'uom_code', 'alternate_uom_code', 'conversion_factor', 'supply_type', 'hsn_code', 'tax_rate',
                'track_serial', 'track_batch',
                'height_cm', 'length_cm', 'width_cm', 'net_weight', 'weight_unit',
                'purchase_price', 'trade_price', 'selling_price', 'mrp', 'purchase_price_tax_basis',
                'alt_purchase_price', 'alt_trade_price', 'alt_selling_price', 'alt_mrp',
                'costing_method', 'market_valuation_method',
                'min_stock', 'reorder_level', 'specification', 'warranty', 'catalog_link', 'is_active',
            ],
            'parties' => [
                'name', 'code', 'party_type', 'classification', 'trade_name', 'gstin', 'phone', 'email',
                'opened_on', 'reference_by', 'udyam_number', 'website', 'location_link', 'is_active',
                'area', 'route',
                'address_line_1', 'address_line_2', 'city', 'state', 'pincode',
                'credit_limit', 'credit_days', 'credit_status', 'credit_period_basis', 'interest_rate',
                'contact_name', 'contact_role', 'contact_phone', 'contact_email',
                'bank_account_holder', 'bank_name', 'bank_branch', 'bank_account_number', 'bank_account_type', 'bank_ifsc',
            ],
            'price-master' => ['customer_type', 'part_number', 'uom_code', 'rate', 'min_qty', 'rate_includes_gst'],
            'leads' => [
                'contact_name', 'company_name', 'title', 'email', 'secondary_email',
                'mobile', 'second_mobile_number', 'phone', 'landline',
                'sales_person', 'tag', 'sub_category',
                'mailing_street', 'mailing_city', 'mailing_state', 'mailing_zip',
            ],
            default => abort(404, 'Unknown template'),
        };

        $sample = match ($type) {
            'companies' => [
                'CMP-01', 'Acme Trading', 'Acme Trading Pvt Ltd', 'Private Limited', '',
                '29AABCU9603R1ZM', 'AABCU9603R', '#1 Main Road', 'Bengaluru', 'Karnataka', '560001',
                '9999900001', 'info@acme.test', 'https://acme.test', 1, 1,
            ],
            'branches' => [
                'CMP-01', 'BR-HQ', 'Head Office', '29AABCU9603R1ZM', '9999900002', 'Karnataka', '560001', '#1 Main Road', 1,
            ],
            'warehouses' => [
                'CMP-01', 'BR-HQ', 'WH-01', 'Main Warehouse', 'Godown Lane', 1, 1,
            ],
            'financial-years' => [
                'CMP-01', '2026-2027', '2026-04-01', '2027-03-31', 1, 0,
            ],
            'uoms' => ['PCS', 'Pieces', 1],
            'brands' => ['CMP-01', 'Acme', 'Primary OEM brand', 1],
            'categories' => ['Acme', 'Widgets', 1],
            'sub-categories' => ['Acme', 'Widgets', 'Standard', 1],
            'product-types' => ['CMP-01', 'Finished Goods', 'FG', 'Sellable finished items', 1, 1],
            'products' => [
                'Widget X', 'PN-WIDGET-001', 'WX-100', 'Finished Goods', 'Acme', 'Widgets', 'Standard',
                'PCS', 'BOX', 10, 'goods', '84713010', 18,
                1, 0,
                10, 20, 15, 1.5, 'KG',
                100, 130, 150, 175, 'exclusive',
                900, 1100, 1400, 1600,
                'avg_cost', 'avg_price',
                5, 10, 'Steel body', '12 months', 'https://example.com/catalog/widget-x', 1,
            ],
            'parties' => [
                'Sample Distributor', 'PARTY-001', 'sundry_debtors', 'Retail', 'Sample Dist. Pvt Ltd',
                '29AABCU9603R1ZM', '9999999999', 'ops@sample.co',
                '2024-04-01', 'Referral - Ravi', 'UDYAM-KR-01-0000001', 'https://sample.co', 'https://maps.example/sample', 1,
                'Zone A', 'Route 1',
                '#12, MG Road', 'Near Metro', 'Bengaluru', 'Karnataka', '560001',
                50000, 30, 'open', 'cumulative', 18,
                'Ravi Kumar', 'Owner', '9999888877', 'ravi@sample.co',
                'Sample Distributor', 'HDFC Bank', 'MG Road', '50200012345678', 'Current', 'HDFC0001234',
            ],
            'price-master' => ['Retail', 'PN-WIDGET-001', 'PCS', 150, 0, 0],
            'leads' => [
                'Anand Verma', 'Apex Tech Solutions', 'Director', 'anand@apextech.in', 'billing@apextech.in',
                '9820011223', '9820099887', '022-26543210', '022-26543211', 'Admin', 'VIP', 'Electronics',
                'Plot 42, MIDC, Andheri East', 'Mumbai', 'Maharashtra', '400093',
            ],
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

        $companyId = auth()->user()?->company_id;

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
            'products' => new ProductsImport($companyId),
            'parties' => new PartiesImport($companyId),
            'price-master' => new PriceMasterImport(),
            'leads' => new LeadsImport($companyId),
            default => abort(404, 'Unknown import type'),
        };

        // Persist uploaded file so it can be retried if any rows fail
        $storedPath = $request->file('file')->store('imports/pending', 'local');
        $fullPath = Storage::disk('local')->path($storedPath);

        $import->import($fullPath);

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

    public function retry(Request $request): RedirectResponse
    {
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
