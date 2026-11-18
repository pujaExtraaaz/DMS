<?php

namespace App\Console\Commands;

use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\TallySync\Bridge\TallyInbound;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ImportTallyCommand extends Command
{
    protected $signature = 'dms:import-tally
        {path : Path to Master.zip, a directory, or an XML file}
        {--company=AVIT DIGITAL : Target company name (must match Tally SVCURRENTCOMPANY)}
        {--dry-run : Validate and report without writing business data}
        {--skip-backup : Skip mysqldump backup check (not recommended)}
        {--books : Also import chart / ledgers / vouchers into Books acct_*}
        {--transactions= : Optional path to Transactions.xml if not beside masters}';

    protected $description = 'Import TallyPrime XML masters/transactions into DMS (idempotent; never touches users/roles)';

    /** @var array<string, array{source:int, created:int, updated:int, skipped:int, conflicts:int}> */
    private array $report = [];

    public function handle(TallyInbound $inbound): int
    {
        ini_set('memory_limit', '1024M');

        $path = $this->resolvePath((string) $this->argument('path'));
        $companyName = trim((string) $this->option('company'));
        $dryRun = (bool) $this->option('dry-run');
        $withBooks = (bool) $this->option('books');

        $this->info('DMS Tally import');
        $this->line('  Database : '.config('database.connections.'.config('database.default').'.database'));
        $this->line('  Company  : '.$companyName);
        $this->line('  Source   : '.$path);
        $this->line('  Mode     : '.($dryRun ? 'DRY-RUN' : 'LIVE').($withBooks ? ' + BOOKS' : ''));

        if (! is_file($path) && ! is_dir($path)) {
            $this->error('Source not found. Pass a real Master.zip / XML path, e.g.:');
            $this->line('  php artisan dms:import-tally "C:\\Users\\jaind\\Downloads\\100000\\Master.zip" --company="AVIT DIGITAL" --books');

            return self::FAILURE;
        }

        if (! $this->option('skip-backup') && ! $dryRun) {
            if (! $this->createBackup()) {
                $this->error('Backup failed. Refusing to import. Use --skip-backup only if you already have a backup.');

                return self::FAILURE;
            }
        }

        $usersBefore = DB::table('users')->count();
        $rolesBefore = DB::table('roles')->count();

        [$masterXml, $txnXml] = $this->loadXmlPayloads($path);
        if ($masterXml === null && $txnXml === null) {
            $this->error('No Tally XML found. Provide Master.zip / Master.xml / Transactions.xml (not .1800 company folders).');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->dryRunSummary($masterXml, $txnXml);

            return self::SUCCESS;
        }

        $company = $this->ensureCompany($companyName, $masterXml);
        config(['tally_sync.company' => $company->name]);

        $this->ensureFinancialYears($company);
        $this->ensureDefaultBranch($company);

        if ($withBooks) {
            $books = BooksCompany::ensureForOrganization($company);
            $this->info("Books company ready #{$books->id}");
        }

        $stages = [];
        if ($masterXml) {
            $stages[] = ['cost_centres', $masterXml, 'dms'];
            $stages[] = ['ledgers', $masterXml, 'dms'];
            if ($withBooks) {
                $stages[] = ['groups', $masterXml, 'books'];
                $stages[] = ['cost_categories', $masterXml, 'books'];
                $stages[] = ['cost_centres', $masterXml, 'books'];
                $stages[] = ['ledgers', $masterXml, 'books'];
                $stages[] = ['voucher_types', $masterXml, 'books'];
            }
        }
        if ($txnXml) {
            $stages[] = ['vouchers', $txnXml, 'dms'];
            if ($withBooks) {
                $stages[] = ['vouchers', $txnXml, 'books'];
            }
        }

        foreach ($stages as [$key, $xml, $target]) {
            $label = "{$key} → {$target}";
            $this->info("Importing {$label}...");
            $sourceCount = $this->countTags($xml, $this->tagFor($key));
            try {
                $result = $inbound->apply($key, $xml, $target);
            } catch (\Throwable $e) {
                $this->error("Failed {$label}: ".$e->getMessage());
                $this->record($label, $sourceCount, 0, 0, 0, 1);

                continue;
            }
            $this->record(
                $label,
                $sourceCount,
                (int) ($result['created'] ?? 0),
                (int) ($result['updated'] ?? 0),
                (int) ($result['skipped'] ?? 0),
                (int) ($result['conflicts'] ?? 0),
            );
            $this->line(sprintf(
                '  created=%d updated=%d skipped=%d conflicts=%d (source tags≈%d)',
                $result['created'] ?? 0,
                $result['updated'] ?? 0,
                $result['skipped'] ?? 0,
                $result['conflicts'] ?? 0,
                $sourceCount
            ));
        }

        $usersAfter = DB::table('users')->count();
        $rolesAfter = DB::table('roles')->count();
        if ($usersAfter !== $usersBefore || $rolesAfter !== $rolesBefore) {
            $this->error('Users/roles count changed during import — investigate immediately.');

            return self::FAILURE;
        }

        $this->writeReport($company);
        $this->table(
            ['Stage', 'Source≈', 'Created', 'Updated', 'Skipped', 'Conflicts'],
            collect($this->report)->map(fn ($r, $k) => [$k, $r['source'], $r['created'], $r['updated'], $r['skipped'], $r['conflicts']])->values()->all()
        );

        $this->warn('Note: Master.xml in this export has no STOCKITEM/UNIT/GODOWN/STOCKGROUP. Inventory and sales/purchase history were not in the XML — only ledgers/groups/cost centres + the vouchers present in Transactions.xml.');
        $this->warn('Native .1800 company files cannot be imported; re-export stock items and day book/vouchers from TallyPrime as XML to complete the migration.');

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        $path = trim($path, "\"' ");
        if (! str_starts_with($path, '/') && ! preg_match('/^[A-Za-z]:/', $path)) {
            $path = base_path($path);
        }

        return $path;
    }

    private function createBackup(): bool
    {
        $dir = storage_path('app'.DIRECTORY_SEPARATOR.'backups');
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            $this->error('Cannot create backup directory: '.$dir);

            return false;
        }

        // mysqldump on Windows is picky: use backslashes and double-quoted --result-file.
        $file = $dir.DIRECTORY_SEPARATOR.'dms_pre_tally_import_'.now()->format('Ymd_His').'.sql';
        if (DIRECTORY_SEPARATOR === '\\') {
            $file = str_replace('/', '\\', $file);
        }

        $host = (string) config('database.connections.mysql.host');
        $port = (string) config('database.connections.mysql.port');
        $db = (string) config('database.connections.mysql.database');
        $user = (string) config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');

        $mysqldump = collect([
            'D:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'mysqldump',
        ])->first(fn ($bin) => $bin === 'mysqldump' || is_file($bin));

        $passwordArg = ($pass !== null && $pass !== '') ? ' -p'.escapeshellarg((string) $pass) : '';
        $cmd = sprintf(
            '"%s" --host=%s --port=%s --user=%s%s --result-file="%s" %s',
            $mysqldump,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            $passwordArg,
            $file,
            escapeshellarg($db)
        );

        $this->info("Creating backup → {$file}");
        $output = [];
        $code = 0;
        exec($cmd.' 2>&1', $output, $code);
        if ($code !== 0 || ! is_file($file) || filesize($file) < 100) {
            $this->error(implode("\n", $output) ?: 'mysqldump produced an empty file');

            return false;
        }
        $this->info('Backup OK ('.number_format(filesize($file)).' bytes)');

        return true;
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function loadXmlPayloads(string $path): array
    {
        $master = null;
        $txn = null;
        $txnOpt = $this->option('transactions');

        if (is_file($path) && str_ends_with(strtolower($path), '.zip')) {
            $extract = storage_path('app/tally-imports/extract_'.now()->format('YmdHis'));
            File::ensureDirectoryExists($extract);
            $zip = new ZipArchive;
            if ($zip->open($path) !== true) {
                $this->error('Cannot open zip: '.$path);

                return [null, null];
            }
            $zip->extractTo($extract);
            $zip->close();
            $path = $extract;
        }

        if (is_file($path) && preg_match('/\.xml$/i', $path)) {
            $xml = File::get($path);
            $hasLedgerMaster = preg_match('/<LEDGER[\s>]/i', $xml) === 1;
            $hasGroupMaster = preg_match('/<GROUP[\s>]/i', $xml) === 1;
            $hasVoucher = preg_match('/<VOUCHER[\s>]/i', $xml) === 1;
            if ($hasVoucher && ! $hasLedgerMaster && ! $hasGroupMaster) {
                $txn = $xml;
            } else {
                $master = $xml;
                if ($hasVoucher && ($hasLedgerMaster || $hasGroupMaster)) {
                    // Combined file — also treat as transactions source.
                    $txn = $xml;
                }
            }
        } elseif (is_dir($path)) {
            foreach (File::allFiles($path) as $file) {
                $name = strtolower($file->getFilename());
                if ($name === 'master.xml') {
                    $master = File::get($file->getPathname());
                }
                if ($name === 'transactions.xml') {
                    $txn = File::get($file->getPathname());
                }
            }
            if (! $master) {
                foreach (File::allFiles($path) as $file) {
                    if (! preg_match('/\.xml$/i', $file->getFilename())) {
                        continue;
                    }
                    $xml = File::get($file->getPathname());
                    if (stripos($xml, '<LEDGER') !== false || stripos($xml, '<GROUP') !== false) {
                        $master = $xml;
                        break;
                    }
                }
            }
            if (! $txn) {
                foreach (File::allFiles($path) as $file) {
                    if (! preg_match('/\.xml$/i', $file->getFilename())) {
                        continue;
                    }
                    $xml = File::get($file->getPathname());
                    if (stripos($xml, '<VOUCHER') !== false) {
                        $txn = $xml;
                        break;
                    }
                }
            }
        }

        if (is_string($txnOpt) && $txnOpt !== '' && is_file($txnOpt)) {
            $txn = File::get($txnOpt);
        }

        // Persist sanitized UTF-8 copies outside git for audit / re-runs.
        $store = storage_path('app/tally-imports');
        File::ensureDirectoryExists($store);
        if ($master) {
            $master = $this->normalizeXmlEncoding($master);
            File::put($store.'/last-master.xml', $master);
        }
        if ($txn) {
            $txn = $this->normalizeXmlEncoding($txn);
            File::put($store.'/last-transactions.xml', $txn);
        }

        return [$master, $txn];
    }

    /**
     * TallyPrime Windows exports are often UTF-16 LE (BOM FF FE). DOM/libxml need UTF-8.
     */
    private function normalizeXmlEncoding(string $xml): string
    {
        if (str_starts_with($xml, "\xFF\xFE") || str_starts_with($xml, "\xFE\xFF")) {
            $converted = @mb_convert_encoding($xml, 'UTF-8', 'UTF-16');
            if (is_string($converted) && $converted !== '') {
                $xml = $converted;
            }
        } elseif (str_starts_with($xml, "\xEF\xBB\xBF")) {
            $xml = substr($xml, 3);
        }

        // Drop NULs left from mis-detected encodings.
        if (str_contains($xml, "\0")) {
            $xml = str_replace("\0", '', $xml);
        }

        return TallyInbound::sanitizeTallyXml($xml);
    }

    private function dryRunSummary(?string $masterXml, ?string $txnXml): void
    {
        $this->info('Dry-run source inventory:');
        $rows = [];
        if ($masterXml) {
            foreach (['GROUP', 'LEDGER', 'COSTCENTRE', 'COSTCATEGORY', 'VOUCHERTYPE', 'STOCKITEM', 'UNIT', 'GODOWN', 'STOCKGROUP'] as $tag) {
                $c = $this->countTags($masterXml, $tag);
                if ($c > 0) {
                    $rows[] = ['Master.xml', $tag, $c];
                }
            }
        }
        if ($txnXml) {
            $rows[] = ['Transactions.xml', 'VOUCHER', $this->countTags($txnXml, 'VOUCHER')];
        }
        $this->table(['File', 'Tag', 'Count'], $rows);
        $this->warn('No data written. Stock masters missing from this export will block product/invoice migration until re-exported.');
    }

    private function ensureCompany(string $name, ?string $masterXml): Company
    {
        $company = Company::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($company) {
            $this->info("Using existing company #{$company->id} {$company->name}");

            return $company;
        }

        $gstin = '03ABGPG1954N1ZN';
        $pan = 'ABGPG1954N';
        if ($masterXml && preg_match('/<CMPGSTIN>([^<]+)</', $masterXml, $m)) {
            $gstin = trim($m[1]);
        }

        $company = Company::query()->create([
            'name' => $name,
            'code' => 'AVIT',
            'legal_name' => $name,
            'gstin' => $gstin,
            'pan' => $pan,
            'address' => 'B-19, 463 Sangat Road, Civil Lines',
            'city' => 'Ludhiana',
            'state' => 'Punjab',
            'pincode' => '141001',
            'country' => 'India',
            'email' => 'info@avitdigital.in',
            'website' => 'www.avitdigital.in',
            'phone' => '9646004696',
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->info("Created company #{$company->id} {$company->name}");

        return $company;
    }

    private function ensureFinancialYears(Company $company): void
    {
        $years = [
            ['2023-04-01', '2024-03-31', false],
            ['2024-04-01', '2025-03-31', false],
            ['2025-04-01', '2026-03-31', false],
            ['2026-04-01', '2027-03-31', true],
        ];

        foreach ($years as [$start, $end, $current]) {
            FinancialYear::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'starts_on' => $start,
                ],
                [
                    'name' => substr($start, 0, 4).'-'.substr($end, 2, 2),
                    'ends_on' => $end,
                    'is_current' => $current,
                    'is_closed' => false,
                ]
            );
        }

        if ($current = FinancialYear::query()->where('company_id', $company->id)->where('is_current', true)->first()) {
            FinancialYear::query()
                ->where('company_id', $company->id)
                ->where('id', '!=', $current->id)
                ->update(['is_current' => false]);
        }
    }

    private function ensureDefaultBranch(Company $company): void
    {
        Branch::query()->firstOrCreate(
            ['company_id' => $company->id, 'code' => 'HO'],
            [
                'name' => 'Head Office',
                'address' => $company->address,
                'state' => $company->state,
                'pincode' => $company->pincode,
                'phone' => $company->phone,
                'gstin' => $company->gstin,
                'is_active' => true,
            ]
        );
    }

    private function tagFor(string $key): string
    {
        return match ($key) {
            'groups' => 'GROUP',
            'stock_groups' => 'STOCKGROUP',
            'units' => 'UNIT',
            'godowns' => 'GODOWN',
            'cost_categories' => 'COSTCATEGORY',
            'cost_centres' => 'COSTCENTRE',
            'ledgers', 'bank_accounts' => 'LEDGER',
            'voucher_types' => 'VOUCHERTYPE',
            'stock_items' => 'STOCKITEM',
            'vouchers' => 'VOUCHER',
            default => strtoupper($key),
        };
    }

    private function countTags(string $xml, string $tag): int
    {
        return preg_match_all('/<'.preg_quote($tag, '/').'[\s>]/i', $xml) ?: 0;
    }

    private function record(string $stage, int $source, int $created, int $updated, int $skipped, int $conflicts): void
    {
        $this->report[$stage] = compact('source', 'created', 'updated', 'skipped', 'conflicts');
    }

    private function writeReport(Company $company): void
    {
        $payload = [
            'at' => now()->toIso8601String(),
            'company_id' => $company->id,
            'company' => $company->name,
            'database' => config('database.connections.'.config('database.default').'.database'),
            'stages' => $this->report,
            'counts' => [
                'users' => DB::table('users')->count(),
                'roles' => DB::table('roles')->count(),
                'customers' => DB::table('customers')->count(),
                'branches' => DB::table('branches')->where('company_id', $company->id)->count(),
                'payments' => DB::table('payments')->count(),
                'invoices' => DB::table('invoices')->count(),
                'purchase_invoices' => DB::table('purchase_invoices')->count(),
                'products' => DB::table('products')->count(),
                'acct_ledgers' => DB::table('acct_ledgers')->count(),
                'acct_vouchers' => DB::table('acct_vouchers')->count(),
                'acct_account_groups' => DB::table('acct_account_groups')->count(),
                'tally_sync_mappings' => DB::table('tally_sync_mappings')->count(),
                'tally_sync_conflicts' => DB::table('tally_sync_conflicts')->where('status', 'open')->count(),
            ],
            'gaps' => [
                'No STOCKITEM/UNIT/GODOWN/STOCKGROUP in Master.xml',
                'Transactions.xml contains mostly Payment/Contra/Receipt/Journal — not full sales/purchase history',
                'Native .1800 Tally company folder is not importable',
            ],
        ];

        $file = storage_path('app/tally-imports/import-report-'.now()->format('Ymd_His').'.json');
        File::put($file, json_encode($payload, JSON_PRETTY_PRINT));
        $this->info('Report written: '.$file);
    }
}
