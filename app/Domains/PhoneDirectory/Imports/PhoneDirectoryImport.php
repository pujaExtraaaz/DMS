<?php

namespace App\Domains\PhoneDirectory\Imports;

use App\Domains\Master\Imports\BulkImportHelpers;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\Designation;
use App\Domains\Organization\Models\Company;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryCategory;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryDepartment;
use App\Support\NameCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import Phone Directory contacts.
 * Canonical columns: see BulkImportCatalog::get('phone-directory').
 */
class PhoneDirectoryImport implements ToCollection, WithHeadingRow
{
    use BulkImportHelpers, Importable;

    public array $errors = [];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<string, string> */
    protected array $contactTypeMap = [
        'employee' => 'employee',
        'emp' => 'employee',
        'customer' => 'customer',
        'client' => 'customer',
        'supplier' => 'supplier',
        'vendor' => 'supplier',
        'dealer' => 'dealer',
        'distributor' => 'distributor',
        'business partner' => 'business_partner',
        'business_partner' => 'business_partner',
        'partner' => 'business_partner',
        'service provider' => 'service_provider',
        'service_provider' => 'service_provider',
        'management' => 'management',
        'government' => 'government_official',
        'government_official' => 'government_official',
        'official' => 'government_official',
        'other' => 'other',
    ];

    public function collection(Collection $rows): void
    {
        $departments = PhoneDirectoryDepartment::query()->get()->keyBy(fn ($d) => strtolower(trim((string) $d->name)));
        $categories = PhoneDirectoryCategory::query()->get()->keyBy(fn ($c) => strtolower(trim((string) $c->name)));

        $designations = collect();
        if (Schema::hasTable('designations')) {
            $designations = Designation::query()->get()->keyBy(fn ($d) => strtolower(trim((string) $d->name)));
        }

        $companies = Company::query()->get()->keyBy(fn ($c) => strtolower(trim((string) $c->name)));
        $classifications = CustomerType::query()->get()->keyBy(fn ($t) => strtolower(trim((string) $t->name)));
        $seenInFile = [];

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = $this->normalizeRow($row);

            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $contactName = NameCase::format((string) $this->first($data, [
                'contact_name', 'name', 'full_name', 'contact',
            ])) ?? '';
            $mobile = preg_replace('/[^\d+]/', '', (string) $this->first($data, [
                'primary_mobile', 'mobile_number', 'mobile', 'phone',
            ])) ?: '';
            $email = trim((string) $this->first($data, ['email', 'email_address'])) ?: null;

            if ($contactName === '') {
                $this->skip($rowNo, 'contact_name', 'Contact Name is required');
                continue;
            }

            if ($mobile === '' || strlen(preg_replace('/\D/', '', $mobile)) < 7) {
                $this->skip($rowNo, 'primary_mobile', 'Primary Mobile is required and must be valid');
                continue;
            }

            if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->skip($rowNo, 'email', "Invalid email format: {$email}");
                continue;
            }

            $contactTypeRaw = strtolower(trim((string) $this->first($data, ['contact_type', 'type'])));
            $contactType = $this->contactTypeMap[$contactTypeRaw] ?? 'other';

            $departmentName = trim((string) $this->first($data, ['department', 'dept']));
            $departmentId = null;
            if ($departmentName !== '') {
                $key = strtolower($departmentName);
                $department = $departments->get($key);
                if (! $department) {
                    $department = PhoneDirectoryDepartment::create([
                        'name' => $departmentName,
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]);
                    $departments->put($key, $department);
                }
                $departmentId = $department->id;
            }

            $categoryName = trim((string) $this->first($data, ['category']));
            $categoryId = null;
            if ($categoryName !== '') {
                $category = $categories->get(strtolower($categoryName));
                $categoryId = $category?->id;
            }

            $designationText = trim((string) $this->first($data, ['designation', 'title', 'job_title'])) ?: null;
            $designationId = null;
            if ($designationText && Schema::hasTable('designations')) {
                $key = strtolower($designationText);
                $designation = $designations->get($key);
                if (! $designation) {
                    $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr(strtoupper($designationText), 0, 8)) ?: 'DES');
                    $base = $code;
                    $suffix = 1;
                    while (Designation::query()->where('code', $code)->exists()) {
                        $code = $base.$suffix;
                        $suffix++;
                    }
                    $designation = Designation::create([
                        'name' => $designationText,
                        'code' => $code,
                        'is_active' => true,
                    ]);
                    $designations->put($key, $designation);
                }
                $designationId = $designation->id;
                $designationText = $designation->name;
            }

            $companyRaw = trim((string) $this->first($data, ['company_name', 'company', 'organization'])) ?: null;
            $companyId = $companyRaw ? ($companies->get(strtolower($companyRaw))?->id) : null;

            $classificationRaw = trim((string) $this->first($data, [
                'classification', 'party_classification', 'customer_type',
            ]));
            $customerTypeId = $classificationRaw !== ''
                ? ($classifications->get(strtolower($classificationRaw))?->id)
                : null;

            $isImportant = $this->isTruthy($this->first($data, [
                'is_important', 'important', 'important_yes_no',
            ]));

            $statusRaw = strtolower(trim((string) $this->first($data, ['status', 'status_active_inactive'])));
            $status = in_array($statusRaw, ['inactive', '0', 'no', 'disabled'], true) ? 'inactive' : 'active';

            $payload = [
                'contact_name' => $contactName,
                'contact_type' => $contactType,
                'designation' => $designationText,
                'designation_id' => $designationId,
                'department_id' => $departmentId,
                'company_id' => $companyId,
                'customer_type_id' => $customerTypeId,
                'company_name' => $companyRaw,
                'category_id' => $categoryId,
                'mobile_number' => $mobile,
                'alternate_mobile' => $this->nullableFirst($data, ['alternate_mobile', 'secondary_mobile']),
                'office_phone' => $this->nullableFirst($data, ['office_phone', 'landline']),
                'extension_number' => $this->nullableFirst($data, ['extension', 'extension_number', 'ext']),
                'whatsapp_number' => $this->nullableFirst($data, ['whatsapp_number', 'whatsapp']),
                'email' => $email,
                'alternate_email' => $this->nullableFirst($data, ['alternate_email', 'secondary_email']),
                'country' => $this->nullableFirst($data, ['country']) ?: 'India',
                'state' => $this->nullableFirst($data, ['state']),
                'city' => $this->nullableFirst($data, ['city']),
                'area' => $this->nullableFirst($data, ['area']),
                'address' => $this->nullableFirst($data, ['address', 'street', 'complete_address']),
                'pincode' => $this->nullableFirst($data, ['pincode', 'pin_code', 'zip']),
                'is_important' => $isImportant,
                'status' => $status,
                'notes' => $this->nullableFirst($data, ['notes', 'remarks']),
            ];

            $dedupeKey = 'm:'.preg_replace('/\D/', '', $mobile);
            if (isset($seenInFile[$dedupeKey])) {
                $this->skipped++;
                continue;
            }
            $seenInFile[$dedupeKey] = true;

            $existing = PhoneDirectoryContact::query()
                ->where('mobile_number', $mobile)
                ->first();

            if (! $existing && $email) {
                $existing = PhoneDirectoryContact::query()
                    ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                    ->first();
            }

            if ($existing) {
                $compareKeys = [
                    'contact_name', 'contact_type', 'designation', 'designation_id', 'department_id',
                    'company_id', 'customer_type_id', 'company_name', 'category_id', 'mobile_number',
                    'alternate_mobile', 'office_phone', 'extension_number', 'whatsapp_number',
                    'email', 'alternate_email', 'country', 'state', 'city', 'area', 'address',
                    'pincode', 'is_important', 'status', 'notes',
                ];

                if (! $this->payloadChanged($existing->only($compareKeys), $payload, $compareKeys)) {
                    $this->skipped++;
                    continue;
                }

                $payload['updated_by'] = auth()->id();
                $existing->update($payload);
                $this->updated++;
            } else {
                $payload['created_by'] = auth()->id();
                PhoneDirectoryContact::create($payload);
                $this->created++;
            }
        }
    }

    protected function normalizeRow(mixed $row): array
    {
        return collect($row)->mapWithKeys(function ($value, $key) {
            $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string) $key) ?? ''));
            $clean = trim($clean, '_');

            return [$clean => is_string($value) ? trim($value) : $value];
        })->toArray();
    }

    protected function rowIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function first(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if ($key === '') {
                continue;
            }
            if (array_key_exists($key, $data) && $data[$key] !== null && trim((string) $data[$key]) !== '') {
                return $data[$key];
            }
        }

        return null;
    }

    protected function nullableFirst(array $data, array $keys): ?string
    {
        $value = $this->first($data, $keys);

        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
