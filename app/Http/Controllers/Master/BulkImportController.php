<?php

namespace App\Http\Controllers\Master;

use App\Domains\Crm\Imports\LeadsImport;
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
 * Bulk import + template downloads for Products, Parties, Price Master, and CRM Leads.
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
            'products' => [
                'name', 'part_number', 'model', 'product_type', 'brand', 'category', 'sub_category',
                'uom_code', 'alternate_uom_code', 'conversion_factor', 'supply_type', 'hsn_code', 'tax_rate',
                'track_serial', 'track_batch',
                'height_cm', 'length_cm', 'width_cm', 'net_weight', 'weight_unit',
                'purchase_price', 'trade_price', 'selling_price', 'mrp', 'purchase_price_tax_basis',
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
            'price-master' => ['customer_type', 'part_number', 'uom_code', 'rate', 'min_qty'],
            'leads' => ['contact_name', 'company_name', 'title', 'email', 'secondary_email',
                        'mobile', 'second_mobile_number', 'phone', 'landline',
                        'sales_person', 'tag', 'sub_category',
                        'mailing_street', 'mailing_city', 'mailing_state', 'mailing_zip'],
            default => abort(404, 'Unknown template'),
        };

        $sample = match ($type) {
            'products' => [
                'Widget X', 'PN-WIDGET-001', 'WX-100', 'Finished Goods', 'Acme', 'Widgets', 'Standard',
                'PCS', 'BOX', 10, 'goods', '84713010', 18,
                1, 0,
                10, 20, 15, 1.5, 'KG',
                100, 130, 150, 175, 'exclusive',
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
            'price-master' => ['Retail', 'PROD-1-00001', 'PCS', 150, 0],
            'leads' => ['Anand Verma', 'Apex Tech Solutions', 'Director', 'anand@apextech.in', 'billing@apextech.in', '9820011223', '9820099887', '022-26543210', '022-26543211', 'Admin', 'VIP', 'Electronics', 'Plot 42, MIDC, Andheri East', 'Mumbai', 'Maharashtra', '400093'],
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
            'leads' => new LeadsImport(auth()->user()?->company_id),
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
