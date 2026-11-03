<?php

namespace App\Console\Commands;

use App\Domains\Organization\Models\Company as OrganizationCompany;
use App\Domains\Sync\Support\BooksCompany;
use Illuminate\Console\Command;

class ProvisionAccountingCompany extends Command
{
    protected $signature = 'accounting:provision-company {company? : DMS company id}';

    protected $description = 'Create books company, branch, year, and chart for a DMS company without touching operational tables';

    public function handle(): int
    {
        $ids = $this->argument('company')
            ? [(int) $this->argument('company')]
            : OrganizationCompany::query()->orderBy('id')->pluck('id')->all();

        foreach ($ids as $id) {
            $organization = OrganizationCompany::query()->find($id);
            if (! $organization) {
                $this->error("DMS company {$id} was not found.");

                return self::FAILURE;
            }

            $books = BooksCompany::ensureForOrganization($organization);
            $this->info("Books ready for {$organization->name} (acct company #{$books->id}).");
        }

        return self::SUCCESS;
    }
}