<?php

namespace App\Http\Controllers\PhoneDirectory;

use App\Domains\Organization\Models\Company;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhoneDirectoryExportController extends Controller
{
    protected function getFilteredQuery(Request $request)
    {
        $query = PhoneDirectoryContact::query()->with(['department', 'category', 'company', 'classification']);

        // If specific IDs passed (e.g. from bulk selection)
        if ($request->filled('ids')) {
            $ids = is_array($request->input('ids'))
                ? $request->input('ids')
                : explode(',', $request->input('ids'));
            return $query->whereIn('id', array_filter($ids));
        }

        // Otherwise apply current active filters
        $filters = [
            'search' => $request->query('search'),
            'contact_type' => $request->query('contact_type'),
            'department_id' => $request->query('department_id'),
            'designation' => $request->query('designation'),
            'company_name' => $request->query('company_name'),
            'company_id' => $request->query('company_id'),
            'classification_id' => $request->query('classification_id') ?: $request->query('customer_type_id'),
            'state' => $request->query('state'),
            'city' => $request->query('city'),
            'status' => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'important' => $request->query('important'),
        ];

        return $query->filter($filters)->orderBy('contact_name', 'asc');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorizePermission('phone_directory.export');

        $fileName = 'phone_directory_contacts_' . date('Y_m_d_His') . '.csv';
        $contacts = $this->getFilteredQuery($request)->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'ID',
            'Contact Name',
            'Contact Type',
            'Classification',
            'Designation',
            'Department',
            'Company / Organization',
            'Company Website',
            'Category',
            'Primary Mobile',
            'Alternate Mobile',
            'Office Phone',
            'Extension',
            'WhatsApp Number',
            'Email',
            'Alternate Email',
            'Country',
            'State',
            'City',
            'Area',
            'Address',
            'PIN Code',
            'Important',
            'Status',
            'Notes',
            'Archived At',
            'Created At',
        ];

        return response()->stream(function () use ($contacts, $columns) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, $columns);

            foreach ($contacts as $c) {
                fputcsv($output, [
                    $c->id,
                    $c->contact_name,
                    PhoneDirectoryContact::CONTACT_TYPES[$c->contact_type] ?? ucfirst($c->contact_type),
                    $c->classification?->name ?? '',
                    $c->designation ?? '',
                    $c->department?->name ?? '',
                    $c->effective_company_name ?: ($c->company_name ?? ''),
                    $c->company_website ?? '',
                    $c->category?->name ?? '',
                    $c->mobile_number,
                    $c->alternate_mobile ?? '',
                    $c->office_phone ?? '',
                    $c->extension_number ?? '',
                    $c->whatsapp_number ?? '',
                    $c->email ?? '',
                    $c->alternate_email ?? '',
                    $c->country ?? 'India',
                    $c->state ?? '',
                    $c->city ?? '',
                    $c->area ?? '',
                    $c->address ?? '',
                    $c->pincode ?? '',
                    $c->is_important ? 'Yes' : 'No',
                    ucfirst($c->status ?? 'active'),
                    $c->notes ?? '',
                    $c->archived_at ? $c->archived_at->format('Y-m-d H:i:s') : '',
                    $c->created_at ? $c->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($output);
        }, 200, $headers);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizePermission('phone_directory.export');

        $fileName = 'phone_directory_contacts_' . date('Y_m_d_His') . '.xlsx';
        $contacts = $this->getFilteredQuery($request)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Contacts');

        $headers = [
            'ID',
            'Contact Name',
            'Contact Type',
            'Classification',
            'Designation',
            'Department',
            'Company / Organization',
            'Company Website',
            'Category',
            'Primary Mobile',
            'Alternate Mobile',
            'Office Phone',
            'Extension',
            'WhatsApp Number',
            'Email',
            'Alternate Email',
            'Country',
            'State',
            'City',
            'Area',
            'Address',
            'PIN Code',
            'Important',
            'Status',
            'Notes',
            'Created At',
        ];

        // Header row
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '6D28D9'], // Deep Violet / DMS accent
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A1:Z1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Data rows
        $rowIndex = 2;
        foreach ($contacts as $c) {
            $row = [
                $c->id,
                $c->contact_name,
                PhoneDirectoryContact::CONTACT_TYPES[$c->contact_type] ?? ucfirst($c->contact_type),
                $c->classification?->name ?? '',
                $c->designation ?? '',
                $c->department?->name ?? '',
                $c->effective_company_name ?: ($c->company_name ?? ''),
                $c->company_website ?? '',
                $c->category?->name ?? '',
                $c->mobile_number,
                $c->alternate_mobile ?? '',
                $c->office_phone ?? '',
                $c->extension_number ?? '',
                $c->whatsapp_number ?? '',
                $c->email ?? '',
                $c->alternate_email ?? '',
                $c->country ?? 'India',
                $c->state ?? '',
                $c->city ?? '',
                $c->area ?? '',
                $c->address ?? '',
                $c->pincode ?? '',
                $c->is_important ? 'Yes' : 'No',
                ucfirst($c->status ?? 'active'),
                $c->notes ?? '',
                $c->created_at ? $c->created_at->format('d-M-Y H:i') : '',
            ];

            $sheet->fromArray($row, null, "A{$rowIndex}");

            // Zebra striping
            if ($rowIndex % 2 === 0) {
                $sheet->getStyle("A{$rowIndex}:Y{$rowIndex}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $rowIndex++;
        }

        // Auto-size columns
        foreach (range('A', 'Y') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Borders
        $lastRow = max($rowIndex - 1, 1);
        $sheet->getStyle("A1:Y{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('E2E8F0');

        return response()->stream(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorizePermission('phone_directory.export');

        $contacts = $this->getFilteredQuery($request)->get();
        $company = Company::find(auth()->user()?->company_id);

        $pdf = Pdf::loadView('phone_directory.pdf', [
            'contacts' => $contacts,
            'company' => $company,
            'title' => 'Phone Directory Contact List',
            'generatedAt' => now()->format('d M Y, h:i A'),
            'filtersApplied' => array_filter($request->only([
                'search', 'contact_type', 'classification_id', 'department_id', 'designation', 'company_name', 'state', 'city', 'important', 'status'
            ])),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('phone_directory_' . date('Y_m_d_His') . '.pdf');
    }

    protected function authorizePermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        if ($user->hasAnyRole(['super-admin', 'client-admin'])) {
            return;
        }

        $altPermission = str_replace('_', '-', $permission);
        if ($user->hasAnyPermission([$permission, $altPermission, 'phone_directory.manage', 'phone-directory.manage'])) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }
}
