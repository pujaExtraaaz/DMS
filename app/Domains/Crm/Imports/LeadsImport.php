<?php

namespace App\Domains\Crm\Imports;

use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\LeadActivity;
use App\Domains\Crm\Models\LeadCampaign;
use App\Domains\Crm\Models\LeadSource;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import CRM Leads.
 * Accepts Excel / CSV columns based on the client specification:
 * - Contact Name (name)
 * - Company Name (company_name / organization)
 * - Title
 * - Email
 * - Secondary Email (secondary_email)
 * - Mobile (mobile)
 * - Second Mobile Number (secondary_mobile / SECND MOB)
 * - Phone (phone)
 * - Landline (landline)
 * - Sales Person (sales_person / salesperson / assigned_to)
 * - Tag (tag / tags)
 * - Sub Category (sub_category / subcategory)
 * - Mailing Street (mailing_street / street / address)
 * - Mailing City (mailing_city / city)
 * - Mailing State (mailing_state / state)
 * - Mailing Zip (mailing_zip / zip / pincode)
 */
class LeadsImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public array $errors = [];
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    public function __construct(protected ?int $companyId = null) {}

    public function collection(Collection $rows): void
    {
        $users = User::all();
        $usersByName = $users->keyBy(fn ($u) => strtolower(trim($u->name)));
        $usersByEmail = $users->keyBy(fn ($u) => strtolower(trim($u->email)));

        $subCategories = SubCategory::all();
        $subCategoriesByName = $subCategories->keyBy(fn ($sc) => strtolower(trim($sc->name)));

        $sources = LeadSource::all();
        $sourcesByName = $sources->keyBy(fn ($s) => strtolower(trim($s->name)));

        $campaigns = LeadCampaign::all();
        $campaignsByName = $campaigns->keyBy(fn ($c) => strtolower(trim($c->name)));

        $companyId = $this->companyId ?? Company::query()->value('id');
        $branchId = auth()->user()?->branch_id;
        $actorId = auth()->id();

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $data = collect($row)->mapWithKeys(function ($v, $k) {
                $cleanKey = strtolower(trim(str_replace([' ', '-', '.'], '_', (string) $k)));
                return [$cleanKey => is_string($v) ? trim($v) : $v];
            })->toArray();

            // Contact Name
            $contactName = $data['contact_name']
                ?? $data['name']
                ?? $data['full_name']
                ?? $data['contact']
                ?? '';

            if (blank($contactName)) {
                $this->skipped++;
                $this->errors[] = [
                    'row' => $rowNo,
                    'field' => 'contact_name',
                    'message' => 'Contact Name is required',
                ];
                continue;
            }

            // Company Name
            $companyName = $data['company_name']
                ?? $data['company']
                ?? $data['organization']
                ?? null;

            // Title
            $title = $data['title'] ?? null;

            // Email
            $email = $data['email'] ?? null;
            if (filled($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->skipped++;
                $this->errors[] = [
                    'row' => $rowNo,
                    'field' => 'email',
                    'message' => "Invalid email format: {$email}",
                ];
                continue;
            }

            // Secondary Email
            $secondaryEmail = $data['secondary_email']
                ?? $data['second_email']
                ?? $data['sec_email']
                ?? null;

            // Mobile & Second Mobile Number
            $mobile = (string) ($data['mobile'] ?? $data['mobile_number'] ?? '');
            $secondaryMobile = (string) (
                $data['second_mobile_number']
                ?? $data['second_mobile']
                ?? $data['secnd_mob']
                ?? $data['secondary_mobile']
                ?? ''
            );

            // Phone & Landline
            $phone = (string) ($data['phone'] ?? $data['telephone'] ?? '');
            $landline = (string) ($data['landline'] ?? '');

            // Sales Person
            $salesPersonRaw = strtolower((string) (
                $data['sales_person']
                ?? $data['salesperson']
                ?? $data['assigned_to']
                ?? ''
            ));
            $assignedTo = null;
            if (filled($salesPersonRaw)) {
                if (isset($usersByName[$salesPersonRaw])) {
                    $assignedTo = $usersByName[$salesPersonRaw]->id;
                } elseif (isset($usersByEmail[$salesPersonRaw])) {
                    $assignedTo = $usersByEmail[$salesPersonRaw]->id;
                } elseif (is_numeric($salesPersonRaw) && $users->contains('id', (int) $salesPersonRaw)) {
                    $assignedTo = (int) $salesPersonRaw;
                }
            }

            // Tag
            $tag = $data['tag'] ?? $data['tags'] ?? null;

            // Sub Category
            $subCategoryRaw = (string) ($data['sub_category'] ?? $data['subcategory'] ?? '');
            $subCategoryId = null;
            $subCategoryName = filled($subCategoryRaw) ? $subCategoryRaw : null;
            if (filled($subCategoryRaw) && isset($subCategoriesByName[strtolower($subCategoryRaw)])) {
                $sc = $subCategoriesByName[strtolower($subCategoryRaw)];
                $subCategoryId = $sc->id;
                $subCategoryName = $sc->name;
            }

            // Mailing Address
            $street = $data['mailing_street'] ?? $data['street'] ?? $data['address'] ?? null;
            $city = $data['mailing_city'] ?? $data['city'] ?? null;
            $state = $data['mailing_state'] ?? $data['state'] ?? null;
            $zip = $data['mailing_zip'] ?? $data['zip'] ?? $data['pincode'] ?? null;

            // Source & Campaign
            $sourceRaw = strtolower((string) ($data['lead_source'] ?? $data['source'] ?? ''));
            $sourceId = isset($sourcesByName[$sourceRaw]) ? $sourcesByName[$sourceRaw]->id : null;

            $campaignRaw = strtolower((string) ($data['campaign'] ?? $data['lead_campaign'] ?? ''));
            $campaignId = isset($campaignsByName[$campaignRaw]) ? $campaignsByName[$campaignRaw]->id : null;

            $priority = in_array(strtolower((string) ($data['priority'] ?? '')), ['low', 'normal', 'high', 'urgent'], true)
                ? strtolower((string) $data['priority'])
                : 'normal';

            $status = in_array(strtolower((string) ($data['status'] ?? '')), ['new', 'contacted', 'qualified', 'unqualified', 'lost'], true)
                ? strtolower((string) $data['status'])
                : 'new';

            $interestedProduct = $data['interested_product'] ?? $data['product'] ?? null;
            $notes = $data['notes'] ?? null;

            // Match existing lead by mobile or email
            $existing = null;
            if (filled($mobile) || filled($email)) {
                $existing = Lead::query()
                    ->when(filled($mobile), fn ($q) => $q->where('mobile', $mobile))
                    ->when(blank($mobile) && filled($email), fn ($q) => $q->where('email', $email))
                    ->first();
            }

            $payload = [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'name' => $contactName,
                'company_name' => $companyName,
                'organization' => $companyName,
                'title' => $title,
                'email' => $email,
                'secondary_email' => $secondaryEmail,
                'mobile' => filled($mobile) ? $mobile : null,
                'secondary_mobile' => filled($secondaryMobile) ? $secondaryMobile : null,
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
                'interested_product' => $interestedProduct,
                'notes' => $notes,
            ];

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                $lead = Lead::create($payload);
                $this->created++;

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $actorId,
                    'activity_type' => 'creation',
                    'body' => 'Lead imported from spreadsheet.',
                ]);
            }
        }
    }
}

