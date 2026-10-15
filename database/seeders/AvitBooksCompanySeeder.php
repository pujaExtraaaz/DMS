<?php

namespace Database\Seeders;

use App\Domains\Organization\Models\Company as OrganizationCompany;
use App\Domains\Sync\Support\BooksCompany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Tally\Models\Company as BooksCompanyModel;

/**
 * Ensures Books (acct_companies) mirrors Avit Digital DMS company via accounting_company_links.
 * Fixes the case where Books still shows "Default Company" after DMS was renamed.
 */
class AvitBooksCompanySeeder extends Seeder
{
    public function run(): void
    {
        $org = OrganizationCompany::query()
            ->where('name', 'Avit Digital')
            ->orWhere('is_default', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (! $org) {
            $this->command?->error('Avit Digital DMS company not found. Run AvitDigitalCompanySeeder first.');

            return;
        }

        $books = BooksCompany::forOrganization($org->id);

        if (! $books) {
            $this->command?->info("Provisioning Books company for DMS #{$org->id}…");
            Artisan::call('accounting:provision-company', ['company' => $org->id]);
            $this->command?->getOutput()?->write(Artisan::output());
            $books = BooksCompany::forOrganization($org->id);
        }

        if (! $books) {
            $this->command?->error('Failed to resolve Books company after provision.');

            return;
        }

        // Keep GSTIN unique across acct_companies
        $gstin = $org->gstin;
        if ($gstin && BooksCompanyModel::query()
            ->where('gstin', $gstin)
            ->where('id', '!=', $books->id)
            ->exists()) {
            $gstin = null;
        }

        $books->fill([
            'name' => $org->name,
            'legal_name' => $org->legal_name ?: $org->name,
            'mailing_name' => $org->name,
            'address' => $org->address,
            'state' => $org->state,
            'country' => 'India',
            'pincode' => $org->pincode,
            'phone' => $org->phone,
            'email' => $org->email,
            'website' => $org->website,
            'gstin' => $gstin ?: $org->gstin,
            'pan' => $org->pan,
            'sales_terms' => $org->selling_terms_and_conditions,
            'purchase_terms' => $org->purchase_terms_and_conditions,
            'is_active' => true,
        ]);
        $books->save();

        // Deactivate any other orphan Books companies not linked to this org
        BooksCompanyModel::query()
            ->where('id', '!=', $books->id)
            ->update(['is_active' => false]);

        $this->command?->info("Books company synced: acct_companies.id={$books->id} name={$books->name}");
    }
}
