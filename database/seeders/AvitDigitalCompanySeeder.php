<?php

namespace Database\Seeders;

use App\Domains\Banking\Models\OdAccount;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds / refreshes the Avit Digital DMS organisation profile.
 * Details below are the source of truth for company identity used by DMS screens.
 */
class AvitDigitalCompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->orderByDesc('is_default')->orderBy('id')->first()
            ?? new Company(['code' => 'MAIN', 'is_active' => true]);

        $logoRelative = $this->storeLogo($company);

        $purchaseTerms = implode("\n", [
            '1. Goods must be inspected at delivery.',
            '2. Shortage must be reported within 24 hours.',
            '3. Payment as agreed.',
        ]);

        $sellingTerms = implode("\n", [
            '1. Goods once sold are subject to company policy.',
            '2. Payment within 30 days.',
            '3. Warranty as applicable.',
        ]);

        $company->fill([
            'name' => 'Avit Digital',
            'code' => $company->code ?: 'MAIN',
            'legal_name' => 'Avit Digital',
            'gstin' => '03ABGPG1954N1ZN',
            'pan' => 'ABGPG1954N',
            'tan' => 'PTLV14425F',
            'cin' => null,
            'udyam_registration_no' => 'UDYAM-PB-12-0146707',
            'msme_category' => 'small',
            'msme_registration_no' => 'UDYAM-PB-12-0146707',
            'phone' => '8264240288',
            'email' => 'INFO@AVITDIGITAL.IN',
            'website' => 'www.avitdigital.in',
            'state' => 'Punjab',
            'pincode' => '141001',
            'address' => 'B-19/463 Sangat Road Civil Lines Ludhiana -141001',
            'bank_name' => 'HDFC BANK LTD.',
            'bank_account_no' => '50200088458819',
            'bank_ifsc' => 'HDFC0001390',
            'upi_id' => 'avitdigital19@okhdfcbank',
            'od_limit' => 0,
            'interest_rate' => 0,
            'additional_details' => [
                'contacts' => [],
                'bank_accounts' => [],
            ],
            'purchase_terms_and_conditions' => $purchaseTerms,
            'selling_terms_and_conditions' => $sellingTerms,
            'due_date_basis' => $company->due_date_basis ?: 'invoice_date',
            'logo_path' => $logoRelative,
            'is_active' => true,
            'is_default' => true,
        ]);
        $company->save();

        Company::query()->where('id', '!=', $company->id)->update(['is_default' => false]);

        OdAccount::updateOrCreate(
            [
                'company_id' => $company->id,
                'account_number' => '50200088458819',
            ],
            [
                'bank_name' => 'HDFC BANK LTD.',
                'ifsc_code' => 'HDFC0001390',
                'od_limit' => 0,
                'interest_rate' => 0,
                'interest_calculation_method' => 'daily_simple',
                'effective_from' => now()->startOfMonth()->toDateString(),
                'status' => 'active',
            ]
        );

        $branch = Branch::query()->where('company_id', $company->id)->orderBy('id')->first();
        if ($branch) {
            $branch->update([
                'gstin' => '03ABGPG1954N1ZN',
                'address' => 'B-19/463 Sangat Road Civil Lines Ludhiana -141001',
                'state' => 'Punjab',
                'pincode' => '141001',
                'phone' => '8264240288',
                'is_active' => true,
            ]);
        }

        // Keep Avit staff users on this tenant (external users are unlinked by UnlinkExternalUsersSeeder).
        $external = ['rohit@rgddental.com'];
        $branchId = Branch::query()->where('company_id', $company->id)->orderBy('id')->value('id');
        User::query()
            ->whereNotIn('email', $external)
            ->update([
                'company_id' => $company->id,
                'branch_id' => $branchId,
            ]);

        $this->command?->info("Avit Digital DMS company ready (id={$company->id}).");
    }

    private function storeLogo(Company $company): string
    {
        $source = public_path('images/avit-digital-logo.png');
        $relative = 'companies/logos/avit-digital-logo.png';

        if (! is_file($source)) {
            $this->command?->warn('Logo missing at public/images/avit-digital-logo.png — keeping existing logo_path.');

            return $company->logo_path ?: $relative;
        }

        Storage::disk('public')->makeDirectory('companies/logos');
        $dest = Storage::disk('public')->path($relative);
        File::ensureDirectoryExists(dirname($dest));
        File::copy($source, $dest);

        if ($company->logo_path && $company->logo_path !== $relative) {
            $old = $company->getCleanLogoPath();
            if ($old && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        }

        return $relative;
    }
}
