<?php

namespace App\Http\Controllers\Crm;

use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadAssignment;
use App\Domains\Crm\Models\LeadCampaign;
use App\Domains\Crm\Models\LeadSource;
use App\Domains\Crm\Services\LeadDuplicateChecker;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\IndianStates;
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
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadBulkUploadController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function index(): View
    {
        return view('crm.leads.bulk-upload', [
            'previewData' => null,
            'importToken' => null,
            'filename' => null,
            'users' => User::orderBy('name')->get(),
            'subCategories' => SubCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function template(Request $request): SymfonyResponse
    {
        $format = strtolower($request->query('format', 'xlsx'));

        $columns = [
            'Contact Name',
            'Company Name',
            'Email',
            'Secondary Email',
            'Mobile',
            'SECND MOB',
            'Phone',
            'LANDLINE',
            'Title',
            'SALES PERSON',
            'Tag',
            'Mailing City',
            'Mailing Zip',
            'Mailing State',
            'Mailing Street',
            'SUB CATEGORY',
            'Lead Source',
            'Campaign',
            'Priority',
            'Initial Status',
            'Interested Product',
            'Additional Notes',
        ];

        $sample = [
            'Rajesh Sharma',
            'Sharma Power Technologies',
            'rajesh@sharmapower.com',
            'accounts@sharmapower.com',
            '9820011222',
            '9820033444',
            '022-28776655',
            '022-28776650',
            'Managing Director',
            'Admin',
            'VIP, High Value',
            'Mumbai',
            '400053',
            'Maharashtra',
            'Suite 501, Crystal Plaza, New Link Road',
            'Inverters',
            'Trade Exhibition',
            'Q4 Outreach',
            'High',
            'New',
            'Grid-Tie Solar Inverter 10kW',
            'Looking for distributor pricing and bulk delivery.',
        ];

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($columns, $sample) {
                $out = fopen('php://output', 'w');
                // UTF-8 BOM for Excel compatibility
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($out, $columns);
                fputcsv($out, $sample);
                fclose($out);
            }, 'crm-leads-template.csv', ['Content-Type' => 'text/csv']);
        }

        // Generate Excel (.xlsx) workbook
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CRM Leads');

        $colLetter = 'A';
        foreach ($columns as $index => $colName) {
            $coord = chr(65 + ($index % 26));
            if ($index >= 26) {
                $coord = 'A' . chr(65 + ($index - 26));
            }
            $sheet->setCellValue("{$coord}1", $colName);
            $sheet->setCellValue("{$coord}2", $sample[$index] ?? '');
            $sheet->getColumnDimension($coord)->setAutoSize(true);
        }

        // Header style
        $highestColumn = $sheet->getHighestColumn();
        $headerRange = "A1:{$highestColumn}1";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(22);

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'leads_tpl_');
        $writer->save($tempPath);

        return response()->download($tempPath, 'crm-leads-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:20480',
        ], [
            'file.required' => 'Please select an Excel or CSV file to upload.',
            'file.mimes' => 'Only .xlsx, .xls, and .csv files are supported.',
            'file.max' => 'The file size must not exceed 20MB.',
        ]);

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $storedPath = $file->storeAs('imports/temp_uploads', Str::uuid() . '.' . $file->getClientOriginalExtension());
        $fullPath = Storage::path($storedPath);

        try {
            $parsed = $this->parseAndValidateFile($fullPath);
        } catch (\Throwable $e) {
            Storage::delete($storedPath);
            return back()->with('error', 'Failed to read spreadsheet: ' . $e->getMessage());
        }

        Storage::delete($storedPath);

        if (empty($parsed['rows'])) {
            return back()->with('error', 'The uploaded file does not contain any data rows.');
        }

        $importToken = Str::uuid()->toString();
        $previewPayload = [
            'filename' => $originalFilename,
            'summary' => $parsed['summary'],
            'rows' => $parsed['rows'],
            'created_at' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put("imports/previews/{$importToken}.json", json_encode($previewPayload));

        return view('crm.leads.bulk-upload', [
            'previewData' => $previewPayload,
            'importToken' => $importToken,
            'filename' => $originalFilename,
            'users' => User::orderBy('name')->get(),
            'subCategories' => SubCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'import_token' => 'required|string',
        ]);

        $importToken = $request->input('import_token');
        $previewFilePath = "imports/previews/{$importToken}.json";

        if (! Storage::disk('local')->exists($previewFilePath)) {
            return redirect()->route('crm.leads.bulk-upload')
                ->with('error', 'Import session expired. Please re-upload your spreadsheet.');
        }

        $payload = json_decode(Storage::disk('local')->get($previewFilePath), true);
        if (! $payload || empty($payload['rows'])) {
            return redirect()->route('crm.leads.bulk-upload')
                ->with('error', 'Invalid import data. Please re-upload.');
        }

        $skipDuplicates = $request->boolean('skip_duplicates', true);
        $companyId = auth()->user()?->company_id ?? Company::query()->value('id');
        $branchId = auth()->user()?->branch_id;
        $actorId = auth()->id();

        $created = 0;
        $skippedDuplicates = 0;
        $failed = 0;
        $errorsReport = [];

        DB::beginTransaction();
        try {
            foreach ($payload['rows'] as $row) {
                // Reject and exclude any invalid or duplicate row
                if ($row['status'] !== 'valid') {
                    $failed++;
                    $errorsReport[] = [
                        'row' => $row['row_number'],
                        'name' => $row['data']['name'] ?? '',
                        'mobile' => $row['data']['mobile'] ?? '',
                        'email' => $row['data']['email'] ?? '',
                        'status' => 'Invalid',
                        'errors' => implode('; ', $row['errors'] ?? ['Validation failed']),
                    ];
                    continue;
                }

                // Re-validate on the server immediately before importing to avoid race conditions
                $mobile = $row['data']['mobile'] ?? null;
                $email = $row['data']['email'] ?? null;

                $revalErrors = LeadDuplicateChecker::checkManualLead($email, $mobile, $companyId);
                if (! empty($revalErrors)) {
                    $failed++;
                    $skippedDuplicates++;
                    $errorsReport[] = [
                        'row' => $row['row_number'],
                        'name' => $row['data']['name'] ?? '',
                        'mobile' => $mobile ?? '',
                        'email' => $email ?? '',
                        'status' => 'Duplicate Skipped',
                        'errors' => implode('; ', array_values($revalErrors)),
                    ];
                    continue;
                }

                $leadData = $row['data'];
                $leadData['company_id'] = $companyId;
                $leadData['branch_id'] = $branchId;

                $lead = Lead::create($leadData);
                $created++;

                if (! empty($leadData['assigned_to'])) {
                    LeadAssignment::create([
                        'lead_id' => $lead->id,
                        'assigned_to' => $leadData['assigned_to'],
                        'assigned_by' => $actorId,
                        'method' => 'manual',
                        'notes' => 'Assigned via bulk spreadsheet upload.',
                    ]);
                }

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $actorId,
                    'activity_type' => 'creation',
                    'body' => 'Lead created via bulk upload by ' . (auth()->user()?->name ?? 'User'),
                ]);

                $this->auditLogService->record($lead, 'created');
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('crm.leads.bulk-upload')
                ->with('error', 'Import failed due to database error: ' . $e->getMessage());
        }

        // Clean up preview file
        Storage::disk('local')->delete($previewFilePath);

        // Generate error report CSV if there were any skipped or failed rows
        $errorFilename = null;
        if (! empty($errorsReport)) {
            $errorFilename = 'errors-leads-' . now()->format('Ymd-His') . '.csv';
            $csvContent = "Excel Row,Contact Name,Mobile,Email,Status,Reason / Errors\n";
            foreach ($errorsReport as $err) {
                $csvContent .= sprintf(
                    "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                    $err['row'],
                    str_replace('"', '""', $err['name']),
                    str_replace('"', '""', $err['mobile']),
                    str_replace('"', '""', $err['email']),
                    str_replace('"', '""', $err['status']),
                    str_replace('"', '""', $err['errors'])
                );
            }
            Storage::disk('local')->put("imports/errors/{$errorFilename}", $csvContent);
            session()->flash('bulk_errors_file', $errorFilename);
        }

        $message = sprintf(
            'Bulk upload complete: %d leads imported successfully. %d duplicates skipped. %d invalid rows skipped.',
            $created,
            $skippedDuplicates,
            $failed
        );

        return redirect()->route('crm.leads.index')
            ->with('success', $message);
    }

    public function downloadErrors(string $filename): StreamedResponse
    {
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
            abort(400, 'Invalid filename');
        }

        $path = "imports/errors/{$filename}";
        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Error report file not found.');
        }

        return Storage::disk('local')->download($path, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function parseAndValidateFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rawRows = $worksheet->toArray(null, true, true, false);

        if (count($rawRows) < 2) {
            return [
                'summary' => ['total' => 0, 'valid' => 0, 'invalid' => 0, 'duplicate' => 0],
                'rows' => [],
            ];
        }

        // Row 0 is headers
        $headerRow = $rawRows[0];
        $columnMap = [];
        foreach ($headerRow as $colIndex => $colName) {
            if ($colName === null || trim((string) $colName) === '') {
                continue;
            }
            $cleanKey = preg_replace('/[^a-z0-9]/', '', strtolower((string) $colName));
            $columnMap[$colIndex] = $cleanKey;
        }

        // Cache lookup tables
        $users = User::all();
        $usersByName = $users->keyBy(fn ($u) => strtolower(trim($u->name)));
        $usersByEmail = $users->keyBy(fn ($u) => strtolower(trim($u->email)));

        $subCategories = SubCategory::all();
        $subCategoriesByName = $subCategories->keyBy(fn ($sc) => strtolower(trim($sc->name)));

        $sources = LeadSource::all();
        $sourcesByName = $sources->keyBy(fn ($s) => strtolower(trim($s->name)));

        $campaigns = LeadCampaign::all();
        $campaignsByName = $campaigns->keyBy(fn ($c) => strtolower(trim($c->name)));

        $companyId = auth()->user()?->company_id ?? Company::query()->value('id');

        $seenMobiles = [];
        $seenEmails = [];

        $parsedRows = [];
        $validCount = 0;
        $invalidCount = 0;
        $duplicateCount = 0;

        for ($i = 1; $i < count($rawRows); $i++) {
            $rowNo = $i + 1;
            $rowCells = $rawRows[$i];

            // Check if entire row is empty
            $nonEmpty = array_filter($rowCells, fn ($c) => $c !== null && trim((string) $c) !== '');
            if (empty($nonEmpty)) {
                continue;
            }

            // Map cells by column map
            $rowValues = [];
            foreach ($columnMap as $colIndex => $cleanKey) {
                $val = $rowCells[$colIndex] ?? null;
                $rowValues[$cleanKey] = is_string($val) ? trim($val) : $val;
            }

            // Extract fields based on mapping
            $contactName = $rowValues['contactname'] ?? $rowValues['name'] ?? $rowValues['fullname'] ?? null;
            $companyName = $rowValues['companyname'] ?? $rowValues['organization'] ?? $rowValues['company'] ?? null;
            $email = $rowValues['email'] ?? null;
            $secEmail = $rowValues['secondaryemail'] ?? $rowValues['secemail'] ?? $rowValues['secondemail'] ?? null;
            $mobile = (string) ($rowValues['mobile'] ?? $rowValues['mobilenumber'] ?? '');
            $secMobile = (string) ($rowValues['secndmob'] ?? $rowValues['secondmobilenumber'] ?? $rowValues['secondarymobile'] ?? $rowValues['secondmobile'] ?? '');
            $phone = (string) ($rowValues['phone'] ?? $rowValues['telephone'] ?? '');
            $landline = (string) ($rowValues['landline'] ?? '');
            $title = $rowValues['title'] ?? null;
            $tag = $rowValues['tag'] ?? $rowValues['tags'] ?? null;
            $city = $rowValues['mailingcity'] ?? $rowValues['city'] ?? null;
            $zip = $rowValues['mailingzip'] ?? $rowValues['zip'] ?? $rowValues['pincode'] ?? null;
            $state = $rowValues['mailingstate'] ?? $rowValues['state'] ?? null;
            $street = $rowValues['mailingstreet'] ?? $rowValues['street'] ?? $rowValues['address'] ?? null;
            $subCatRaw = $rowValues['subcategory'] ?? null;
            $sourceRaw = $rowValues['leadsource'] ?? $rowValues['source'] ?? null;
            $campaignRaw = $rowValues['campaign'] ?? $rowValues['leadcampaign'] ?? null;
            $priorityRaw = strtolower((string) ($rowValues['priority'] ?? 'normal'));
            $statusRaw = strtolower((string) ($rowValues['initialstatus'] ?? $rowValues['status'] ?? 'new'));
            $product = $rowValues['interestedproduct'] ?? $rowValues['product'] ?? null;
            $notes = $rowValues['additionalnotes'] ?? $rowValues['notes'] ?? null;
            $salesPersonRaw = $rowValues['salesperson'] ?? $rowValues['salespersonname'] ?? $rowValues['assignedto'] ?? null;

            // Row validation
            $errors = [];
            $duplicateReasons = [];

            if (blank($contactName)) {
                $errors[] = 'Contact Name is required.';
            }

            if (filled($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid Email format: '{$email}'";
            }

            if (filled($secEmail) && ! filter_var($secEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid Secondary Email format: '{$secEmail}'";
            }

            if (filled($mobile)) {
                $digitsOnly = preg_replace('/[^0-9]/', '', $mobile);
                if (strlen($digitsOnly) < 7) {
                    $errors[] = "Mobile must contain at least 7 digits: '{$mobile}'";
                }
            }

            // Sales Person resolution
            $assignedTo = null;
            $salesPersonName = null;
            if (filled($salesPersonRaw)) {
                $spKey = strtolower(trim((string) $salesPersonRaw));
                if (isset($usersByName[$spKey])) {
                    $assignedTo = $usersByName[$spKey]->id;
                    $salesPersonName = $usersByName[$spKey]->name;
                } elseif (isset($usersByEmail[$spKey])) {
                    $assignedTo = $usersByEmail[$spKey]->id;
                    $salesPersonName = $usersByEmail[$spKey]->name;
                } elseif (is_numeric($spKey) && $users->contains('id', (int) $spKey)) {
                    $u = $users->firstWhere('id', (int) $spKey);
                    $assignedTo = $u->id;
                    $salesPersonName = $u->name;
                }
            }

            // Sub Category resolution
            $subCategoryId = null;
            $subCategoryName = filled($subCatRaw) ? $subCatRaw : null;
            if (filled($subCatRaw) && isset($subCategoriesByName[strtolower(trim((string) $subCatRaw))])) {
                $sc = $subCategoriesByName[strtolower(trim((string) $subCatRaw))];
                $subCategoryId = $sc->id;
                $subCategoryName = $sc->name;
            }

            // Source & Campaign
            $sourceId = filled($sourceRaw) && isset($sourcesByName[strtolower(trim((string) $sourceRaw))])
                ? $sourcesByName[strtolower(trim((string) $sourceRaw))]->id
                : null;

            $campaignId = filled($campaignRaw) && isset($campaignsByName[strtolower(trim((string) $campaignRaw))])
                ? $campaignsByName[strtolower(trim((string) $campaignRaw))]->id
                : null;

            $priority = in_array($priorityRaw, ['low', 'normal', 'high', 'urgent'], true) ? $priorityRaw : 'normal';
            $status = in_array($statusRaw, ['new', 'contacted', 'qualified', 'unqualified', 'lost'], true) ? $statusRaw : 'new';

            // Duplicate checks
            $normalizedMobile = LeadDuplicateChecker::normalizeMobile($mobile);
            $normalizedEmail = LeadDuplicateChecker::normalizeEmail($email);

            if ($normalizedMobile !== null) {
                if (isset($seenMobiles[$normalizedMobile])) {
                    $duplicateReasons[] = "Duplicate mobile/contact number found in the uploaded file (matches Row {$seenMobiles[$normalizedMobile]}).";
                } else {
                    $seenMobiles[$normalizedMobile] = $rowNo;
                }

                $existingInDb = LeadDuplicateChecker::findDuplicateMobile($mobile, $companyId);
                if ($existingInDb) {
                    $duplicateReasons[] = "Duplicate mobile/contact number already exists in the database (Lead #{$existingInDb->id} - {$existingInDb->name}).";
                }
            }

            if ($normalizedEmail !== null) {
                if (isset($seenEmails[$normalizedEmail])) {
                    $duplicateReasons[] = "Duplicate email address found in the uploaded file (matches Row {$seenEmails[$normalizedEmail]}).";
                } else {
                    $seenEmails[$normalizedEmail] = $rowNo;
                }

                $existingEmailInDb = LeadDuplicateChecker::findDuplicateEmail($email, $companyId);
                if ($existingEmailInDb) {
                    $duplicateReasons[] = "Duplicate email address already exists in the database (Lead #{$existingEmailInDb->id} - {$existingEmailInDb->name}).";
                }
            }

            // Determine status: mark duplicate rows as Invalid
            if (! empty($duplicateReasons)) {
                $duplicateCount++;
                $errors = array_merge($errors, $duplicateReasons);
            }

            if (! empty($errors)) {
                $statusLabel = 'invalid';
                $invalidCount++;
            } else {
                $statusLabel = 'valid';
                $validCount++;
            }

            $leadPayload = [
                'name' => $contactName,
                'company_name' => $companyName,
                'organization' => $companyName,
                'title' => $title,
                'email' => $email,
                'secondary_email' => $secEmail,
                'mobile' => filled($mobile) ? $mobile : null,
                'secondary_mobile' => filled($secMobile) ? $secMobile : null,
                'phone' => filled($phone) ? $phone : null,
                'landline' => filled($landline) ? $landline : null,
                'assigned_to' => $assignedTo,
                'tag' => $tag,
                'sub_category_id' => $subCategoryId,
                'sub_category' => $subCategoryName,
                'street' => $street,
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
                'lead_source_id' => $sourceId,
                'lead_campaign_id' => $campaignId,
                'priority' => $priority,
                'status' => $status,
                'interested_product' => $product,
                'notes' => $notes,
            ];

            $parsedRows[] = [
                'row_number' => $rowNo,
                'status' => $statusLabel,
                'errors' => $errors,
                'duplicate_reasons' => $duplicateReasons,
                'salesperson_display' => $salesPersonName ?: ($salesPersonRaw ?: '—'),
                'subcategory_display' => $subCategoryName ?: ($subCatRaw ?: '—'),
                'data' => $leadPayload,
            ];
        }

        return [
            'summary' => [
                'total' => count($parsedRows),
                'valid' => $validCount,
                'invalid' => $invalidCount,
                'duplicate' => $duplicateCount,
            ],
            'rows' => $parsedRows,
        ];
    }
}

