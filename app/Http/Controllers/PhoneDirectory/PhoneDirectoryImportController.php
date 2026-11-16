<?php

namespace App\Http\Controllers\PhoneDirectory;

use App\Domains\PhoneDirectory\Models\PhoneDirectoryCategory;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryDepartment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhoneDirectoryImportController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission('phone_directory.import');

        return view('phone_directory.import', [
            'summary' => session('import_summary'),
            'errorFile' => session('import_error_file'),
        ]);
    }

    public function downloadTemplate(Request $request): StreamedResponse
    {
        $this->authorizePermission('phone_directory.import');

        $format = strtolower($request->query('format', 'csv'));

        $headers = [
            'Contact Name',
            'Contact Type',
            'Designation',
            'Department',
            'Company Name',
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
            'Important (Yes/No)',
            'Status (Active/Inactive)',
            'Notes',
        ];

        $sampleRow1 = [
            'Rajesh Kumar Sharma',
            'Customer',
            'Procurement Manager',
            'Purchase',
            'Apex Industries Pvt Ltd',
            'Key Client',
            '9876543210',
            '9876543211',
            '011-23456789',
            '104',
            '9876543210',
            'rajesh.sharma@apexind.com',
            'accounts@apexind.com',
            'India',
            'Maharashtra',
            'Mumbai',
            'Andheri East',
            'Plot 45, MIDC Industrial Area',
            '400093',
            'Yes',
            'Active',
            'Key procurement contact for western region',
        ];

        $sampleRow2 = [
            'Sneha Patel',
            'Employee',
            'Senior Accounts Executive',
            'Accounts',
            'Extraaaz DMS',
            'Internal',
            '9822012345',
            '',
            '',
            '205',
            '9822012345',
            'sneha.patel@example.com',
            '',
            'India',
            'Gujarat',
            'Ahmedabad',
            'Navrangpura',
            '102, Silver Heights, CG Road',
            '380009',
            'No',
            'Active',
            'Joined Q2 2024',
        ];

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Contacts Template');

            $sheet->fromArray($headers, null, 'A1');
            $sheet->fromArray($sampleRow1, null, 'A2');
            $sheet->fromArray($sampleRow2, null, 'A3');

            $sheet->getStyle('A1:V1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D28D9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            foreach (range('A', 'V') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            return response()->stream(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="phone_directory_template.xlsx"',
            ]);
        }

        // CSV template
        return response()->stream(function () use ($headers, $sampleRow1, $sampleRow2) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, $headers);
            fputcsv($output, $sampleRow1);
            fputcsv($output, $sampleRow2);
            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="phone_directory_template.csv"',
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $this->authorizePermission('phone_directory.import');

        $request->merge([
            'duplicate_strategy' => strtolower((string) $request->input('duplicate_strategy', 'skip')),
        ]);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
            'duplicate_strategy' => ['required', 'in:skip,update'],
        ]);

        $file = $request->file('file');
        $duplicateStrategy = strtolower($request->input('duplicate_strategy', 'skip'));

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to read file: ' . $e->getMessage());
        }

        if (count($rows) < 2) {
            return back()->with('error', 'The uploaded file contains no data rows.');
        }

        // Header mapping
        $headerRow = array_shift($rows);
        $headerMap = [];
        foreach ($headerRow as $colKey => $colName) {
            $normalized = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string) $colName)));
            $headerMap[$normalized] = $colKey;
        }

        $getColumn = function (array $row, array $possibleKeys) use ($headerMap) {
            foreach ($possibleKeys as $key) {
                $norm = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $key)));
                if (isset($headerMap[$norm])) {
                    $colLetter = $headerMap[$norm];
                    $val = $row[$colLetter] ?? null;
                    if ($val !== null && trim((string) $val) !== '') {
                        return trim((string) $val);
                    }
                }
            }
            return null;
        };

        // Cache existing departments & categories to avoid repeated queries
        $departments = PhoneDirectoryDepartment::pluck('id', 'name')->mapWithKeys(function ($id, $name) {
            return [strtolower(trim($name)) => $id];
        })->toArray();

        $categories = PhoneDirectoryCategory::pluck('id', 'name')->mapWithKeys(function ($id, $name) {
            return [strtolower(trim($name)) => $id];
        })->toArray();

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $errorRows = [];

        $contactTypeMap = [
            'employee' => 'employee',
            'emp' => 'employee',
            'customer' => 'customer',
            'client' => 'customer',
            'supplier' => 'supplier',
            'vendor' => 'supplier',
            'dealer' => 'dealer',
            'distributor' => 'distributor',
            'business partner' => 'business_partner',
            'partner' => 'business_partner',
            'service provider' => 'service_provider',
            'management' => 'management',
            'government' => 'government_official',
            'official' => 'government_official',
            'other' => 'other',
        ];

        DB::beginTransaction();

        try {
            foreach ($rows as $rowIndex => $row) {
                // Check if row is empty
                $nonEmptyValues = array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== '');
                if (empty($nonEmptyValues)) {
                    continue;
                }

                $contactName = $getColumn($row, ['contact name', 'name', 'full name']);
                $mobileNumber = $getColumn($row, ['primary mobile', 'mobile', 'mobile number', 'phone']);
                $contactTypeRaw = $getColumn($row, ['contact type', 'type']);
                $departmentName = $getColumn($row, ['department', 'dept']);
                $categoryName = $getColumn($row, ['category']);
                $email = $getColumn($row, ['email', 'email address']);

                // Validation: Contact Name and Mobile are strictly required
                $errors = [];
                if (empty($contactName)) {
                    $errors[] = 'Contact Name is required.';
                }
                if (empty($mobileNumber)) {
                    $errors[] = 'Primary Mobile Number is required.';
                } elseif (strlen(preg_replace('/[^\d]/', '', $mobileNumber)) < 7) {
                    $errors[] = 'Invalid mobile number format.';
                }

                if (! empty($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email address format.';
                }

                if (! empty($errors)) {
                    $failedCount++;
                    $errorRows[] = [
                        'row' => $rowIndex,
                        'name' => $contactName ?? '—',
                        'mobile' => $mobileNumber ?? '—',
                        'reason' => implode(' | ', $errors),
                    ];
                    continue;
                }

                // Resolve contact type
                $contactTypeKey = strtolower(trim((string) $contactTypeRaw));
                $contactType = $contactTypeMap[$contactTypeKey] ?? 'other';

                // Resolve or create department
                $departmentId = null;
                if (! empty($departmentName)) {
                    $normDept = strtolower(trim($departmentName));
                    if (isset($departments[$normDept])) {
                        $departmentId = $departments[$normDept];
                    } else {
                        $newDept = PhoneDirectoryDepartment::create([
                            'name' => trim($departmentName),
                            'is_active' => true,
                            'created_by' => auth()->id(),
                        ]);
                        $departmentId = $newDept->id;
                        $departments[$normDept] = $departmentId;
                    }
                }

                // Resolve category
                $categoryId = null;
                if (! empty($categoryName)) {
                    $normCat = strtolower(trim($categoryName));
                    if (isset($categories[$normCat])) {
                        $categoryId = $categories[$normCat];
                    }
                }

                $importantRaw = strtolower((string) $getColumn($row, ['important', 'important yes/no', 'is important']));
                $isImportant = in_array($importantRaw, ['yes', 'true', '1', 'y'], true);

                $statusRaw = strtolower((string) $getColumn($row, ['status', 'status active/inactive']));
                $status = in_array($statusRaw, ['inactive', '0', 'no', 'disabled'], true) ? 'inactive' : 'active';

                $contactData = [
                    'contact_name' => $contactName,
                    'contact_type' => $contactType,
                    'designation' => $getColumn($row, ['designation', 'title', 'job title']),
                    'department_id' => $departmentId,
                    'company_name' => $getColumn($row, ['company name', 'company', 'organization']),
                    'category_id' => $categoryId,
                    'mobile_number' => $mobileNumber,
                    'alternate_mobile' => $getColumn($row, ['alternate mobile', 'secondary mobile']),
                    'office_phone' => $getColumn($row, ['office phone', 'landline']),
                    'extension_number' => $getColumn($row, ['extension', 'ext']),
                    'whatsapp_number' => $getColumn($row, ['whatsapp number', 'whatsapp']),
                    'email' => $email,
                    'alternate_email' => $getColumn($row, ['alternate email', 'secondary email']),
                    'country' => $getColumn($row, ['country']) ?: 'India',
                    'state' => $getColumn($row, ['state']),
                    'city' => $getColumn($row, ['city']),
                    'area' => $getColumn($row, ['area']),
                    'address' => $getColumn($row, ['address', 'street', 'complete address']),
                    'pincode' => $getColumn($row, ['pin code', 'pincode', 'zip']),
                    'is_important' => $isImportant,
                    'status' => $status,
                    'notes' => $getColumn($row, ['notes', 'remarks']),
                ];

                // Duplicate check by mobile_number or email
                $existing = PhoneDirectoryContact::where('mobile_number', $mobileNumber)
                    ->when(! empty($email), function ($q) use ($email) {
                        $q->orWhere('email', $email);
                    })
                    ->first();

                if ($existing) {
                    if ($duplicateStrategy === 'update') {
                        $contactData['updated_by'] = auth()->id();
                        $existing->update($contactData);
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    $contactData['created_by'] = auth()->id();
                    PhoneDirectoryContact::create($contactData);
                    $createdCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        // Store error report if failures occurred
        $errorFileName = null;
        if (! empty($errorRows)) {
            $errorFileName = 'import_errors_' . Str::random(10) . '.csv';
            $errorContent = fopen('php://temp', 'r+');
            fprintf($errorContent, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($errorContent, ['Row Number', 'Contact Name', 'Mobile Number', 'Failure Reason']);
            foreach ($errorRows as $err) {
                fputcsv($errorContent, [$err['row'], $err['name'], $err['mobile'], $err['reason']]);
            }
            rewind($errorContent);
            $csvData = stream_get_contents($errorContent);
            fclose($errorContent);

            Storage::disk('local')->put('phone-directory/errors/' . $errorFileName, $csvData);
        }

        $summary = [
            'created' => $createdCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount,
            'total_processed' => $createdCount + $updatedCount + $skippedCount + $failedCount,
        ];

        return redirect()->route('phone-directory.import.index')
            ->with('import_summary', $summary)
            ->with('import_error_file', $errorFileName);
    }

    public function downloadErrors(Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorizePermission('phone_directory.import');

        $fileName = $request->query('file');
        if (empty($fileName) || ! Storage::disk('local')->exists('phone-directory/errors/' . $fileName)) {
            return redirect()->route('phone-directory.import.index')->with('error', 'Error report file expired or not found.');
        }

        return Storage::disk('local')->download('phone-directory/errors/' . $fileName, 'contact_import_errors.csv', [
            'Content-Type' => 'text/csv',
        ]);
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
