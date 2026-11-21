<?php

namespace App\Console\Commands;

use App\Domains\Sync\Support\BooksCompany;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes broken accounting_company_links after a partial SQL import
 * (MySQL #1452 on organization_company_id → companies.id).
 */
class RepairAccountingCompanyLinksCommand extends Command
{
    protected $signature = 'accounting:repair-company-links
                            {--dry-run : Show orphans without deleting or provisioning}
                            {--no-provision : Only remove orphans; do not create Books companies}';

    protected $description = 'Remove orphan DMS↔Books company links and re-provision missing links';

    public function handle(): int
    {
        if (! Schema::hasTable('accounting_company_links')) {
            $this->error('accounting_company_links table is missing. Run migrations first.');

            return self::FAILURE;
        }

        $links = DB::table('accounting_company_links')->get();
        $orphans = [];

        foreach ($links as $link) {
            $orgOk = DB::table('companies')->where('id', $link->organization_company_id)->exists();
            $acctOk = Schema::hasTable('acct_companies')
                && DB::table('acct_companies')->where('id', $link->acct_company_id)->exists();

            if (! $orgOk || ! $acctOk) {
                $orphans[] = [
                    'id' => $link->id,
                    'organization_company_id' => $link->organization_company_id,
                    'acct_company_id' => $link->acct_company_id,
                    'org_exists' => $orgOk ? 'yes' : 'NO',
                    'acct_exists' => $acctOk ? 'yes' : 'NO',
                ];
            }
        }

        $this->info('Company links: '.$links->count());
        $this->info('Orphans: '.count($orphans));

        if ($orphans !== []) {
            $this->table(
                ['link_id', 'org_company_id', 'acct_company_id', 'org_exists', 'acct_exists'],
                $orphans
            );
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run — no changes made.');
            $this->line('Tip: for phpMyAdmin imports, prepend SET FOREIGN_KEY_CHECKS=0; and end with SET FOREIGN_KEY_CHECKS=1;');

            return self::SUCCESS;
        }

        $result = BooksCompany::repairLinks(provisionMissing: ! $this->option('no-provision'));

        $this->info("Removed orphans: {$result['removed_orphans']}");
        $this->info("Provisioned / ensured: {$result['provisioned']}");
        $this->info("Healthy links now: {$result['healthy']}");

        return self::SUCCESS;
    }
}
