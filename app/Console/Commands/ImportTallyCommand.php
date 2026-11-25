<?php

namespace App\Console\Commands;

use App\Domains\Master\Observers\ProductPriceObserver;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Sync\Support\BooksCompany;
use App\Domains\Sync\Support\SyncGuard;
use App\Domains\TallySync\Bridge\TallyInbound;
use App\Domains\TallySync\Import\TallyBulkUpsert;
use App\Domains\TallySync\Import\TallyImportLookupCache;
use App\Domains\TallySync\Import\TallyXmlStreamer;
use App\Jobs\ImportTallyVoucherChunkJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;

class ImportTallyCommand extends Command
{
    protected $signature = 'dms:import-tally
        {path : Path to Avit_data_xml.zip, an extracted directory, or a single XML file}
        {--company=AVIT DIGITAL : Target DMS company name}
        {--dry-run : Analyse XML and preview counts without writing business data}
        {--books : Deprecated alias — Books import is on by default; use --dms-only to skip}
        {--dms-only : Import DMS commercial data only (skip Books acct_* / Journal / Contra)}
        {--force : Confirm idempotent re-import when Tally mappings already exist}
        {--backup : Attempt mysqldump backup before import (needs exec; local XAMPP only)}
        {--skip-backup : Deprecated alias — auto-backup is off by default; use --backup to enable}
        {--queue-vouchers : Dispatch voucher chunks to queue (run queue:work --queue=tally-import)}
        {--chunk=200 : Voucher/master entities per streamed chunk}
        {--no-fast : Disable parse-once / streaming fast path (legacy per-stage full-file load)}';

    protected $description = 'Import TallyPrime XML into DMS + Books (fast: stream once, cache lookups, chunk vouchers)';

    /** @var array<string, array{dir:string, counts:array<string,int>}> */
    private array $fileIndexes = [];

    private string $runId = '';

    /** @var array<string, array{source:int, created:int, updated:int, skipped:int, conflicts:int, file?:string}> */
    private array $report = [];

    /** @var list<string> */
    private array $notes = [];

    public function handle(TallyInbound $inbound): int
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        $path = $this->resolvePath((string) $this->argument('path'));
        $companyName = trim((string) $this->option('company'));
        $dryRun = (bool) $this->option('dry-run');
        // Combined DMS + Books by default. Journal/Contra need Books to show in DMS Day Book.
        $withBooks = ! (bool) $this->option('dms-only');
        // Artisan bulk XML import always treats Tally as source of truth (idempotent upsert).
        // --force is kept for backwards-compatible CLI; it is effectively always on.
        $force = true;

        $this->info('DMS Tally import');
        $this->line('  Database : '.config('database.connections.'.config('database.default').'.database'));
        $this->line('  Company  : '.$companyName);
        $this->line('  Source   : '.$path);
        $fast = ! (bool) $this->option('no-fast');
        $queueVouchers = (bool) $this->option('queue-vouchers');
        $chunkSize = max(50, (int) $this->option('chunk'));
        $this->runId = Str::lower(Str::random(10));

        $this->line('  Mode     : '.($dryRun ? 'DRY-RUN' : 'LIVE').($withBooks ? ' + BOOKS (combined)' : ' (DMS only)').' + FORCE'.($fast ? ' + FAST' : '').($queueVouchers ? ' + QUEUE-VOUCHERS' : ''));

        if (! is_file($path) && ! is_dir($path)) {
            $this->error('Source not found.');
            $this->line('  php artisan dms:import-tally "storage/app/tally-imports/Avit_data_xml.zip" --company="AVIT DIGITAL" --force');

            return self::FAILURE;
        }

        $bundles = $this->discoverBundles($path);
        if ($bundles === []) {
            $this->error('No Tally XML files found in the source.');

            return self::FAILURE;
        }

        $this->info('Discovered XML bundles:');
        foreach ($bundles as $role => $file) {
            if (is_array($file)) {
                foreach ($file as $i => $path) {
                    $this->line(sprintf(
                        '  %-18s %s (%s)',
                        $i === 0 ? $role : '',
                        basename($path),
                        $this->humanSize(filesize($path))
                    ));
                }
                continue;
            }
            $this->line(sprintf('  %-18s %s (%s)', $role, basename($file), $this->humanSize(filesize($file))));
        }

        if ($dryRun) {
            $this->dryRun($bundles);

            return self::SUCCESS;
        }

        // Auto-backup is opt-in: shared hosts often disable exec()/mysqldump.
        // Always take a manual DB backup (cPanel / phpMyAdmin) before live imports.
        if ($this->option('backup')) {
            if (! $this->createBackup()) {
                $this->error('Backup failed. Refusing to import. Fix mysqldump/exec or omit --backup after a manual backup.');

                return self::FAILURE;
            }
        } else {
            $this->warn('Skipping auto-backup (default). Ensure you already took a manual DB backup before LIVE import.');
        }

        $usersBefore = DB::table('users')->count();
        $rolesBefore = DB::table('roles')->count();

        $seedXml = $this->readNormalized($bundles['accounting'] ?? $bundles['all_masters'] ?? reset($bundles));
        $company = $this->ensureCompany($companyName, $seedXml);
        config(['tally_sync.company' => $company->name]);

        $this->ensureFinancialYears($company);
        $this->ensureDefaultBranch($company);

        if ($withBooks) {
            $books = BooksCompany::ensureForOrganization($company);
            BooksCompany::syncFinancialYearsFromOrganization($company);
            $this->info("Books company ready #{$books->id} (FYs synced for Journal/Contra)");
        }

        $inbound->setForceOverwrite($force);
        $inbound->setBulkXmlImport(true);

        $lookup = new TallyImportLookupCache;
        $lookup->warmDms($company);
        $inbound->setLookupCache($lookup);
        $this->line('Lookup cache warmed for DMS masters.');
        if ($withBooks) {
            $booksCompany = BooksCompany::ensureForOrganization($company);
            $lookup->warmBooks($booksCompany);
            $this->line('Lookup cache warmed for Books masters.');
        }

        if ($force) {
            // Drop stale open noise from earlier tax-rate / dual-write bugs so the report reflects this run.
            $cleared = DB::table('tally_sync_conflicts')
                ->where('status', 'open')
                ->where(function ($q) {
                    $q->where('reason', 'like', '%Duplicate entry%GST%')
                        ->orWhere('reason', 'like', '%acct_tax_rates_company_id_code%')
                        ->orWhere('reason', 'like', 'Books and Tally both changed%')
                        ->orWhere('reason', 'like', "%Field 'code' doesn%")
                        ->orWhere(function ($q2) {
                            $q2->where('reason', 'Voucher type is not mapped.')
                                ->where(function ($q3) {
                                    $q3->where('name', 'like', '%Gst Tax Invoice%')
                                        ->orWhere('name', 'like', '%Sale Return%')
                                        ->orWhere('name', 'like', '%Tax Invoice%');
                                });
                        })
                        ->orWhere('reason', 'like', '%No DMS commercial document mapping%');
                })
                ->update(['status' => 'resolved', 'updated_at' => now()]);
            if ($cleared > 0) {
                $this->line("Cleared {$cleared} stale open conflict(s) before re-import.");
            }
        }

        if ($withBooks) {
            $this->backfillStandardGroupCodes();
        }

        $stages = $this->buildStages($bundles, $withBooks);

        if ($fast) {
            $this->indexSourceFiles($stages, $chunkSize);
        }

        TallyBulkUpsert::withFastSession(function () use ($inbound, $stages, $fast, $queueVouchers, $companyName, $lookup, $withBooks, $company) {
            // Tally XML already lands in DMS + Books explicitly — skip per-row BooksSyncObserver / price history.
            SyncGuard::run(function () use ($inbound, $stages, $fast, $queueVouchers, $companyName, $lookup, $withBooks, $company) {
                $priceObserverWasDisabled = ProductPriceObserver::$disabled;
                ProductPriceObserver::$disabled = true;

                try {
                    foreach ($stages as $stage) {
                        if ($stage['target'] === 'dms' && in_array($stage['key'], ['stock_items', 'stock_groups', 'units', 'godowns'], true)) {
                            $lookup->warmDms($company);
                            $inbound->setLookupCache($lookup);
                        }
                        if ($withBooks && $stage['target'] === 'books') {
                            // Refresh cache after each master stage so voucher lines resolve new ids.
                            $booksCompany = \Tally\Models\Company::query()
                                ->whereRaw('LOWER(name) = ?', [mb_strtolower($companyName)])
                                ->first();
                            if ($booksCompany) {
                                $lookup->warmBooks($booksCompany);
                                $inbound->setLookupCache($lookup);
                            }
                        }
                        $this->runStage($inbound, $stage, $fast, $queueVouchers, $companyName);
                    }
                } finally {
                    ProductPriceObserver::$disabled = $priceObserverWasDisabled;
                }
            });
        });

        if ($queueVouchers) {
            $this->notes[] = 'Voucher chunks queued on [tally-import]. Run: php artisan queue:work --queue=tally-import --timeout=600';
            $this->notes[] = 'Track progress: php artisan dms:import-tally-status '.$this->runId;
        }

        $this->logUnsupportedBundles($bundles);

        $usersAfter = DB::table('users')->count();
        $rolesAfter = DB::table('roles')->count();
        if ($usersAfter !== $usersBefore || $rolesAfter !== $rolesBefore) {
            $this->error('Users/roles changed during import — aborting post-checks.');

            return self::FAILURE;
        }

        $this->writeReport($company);
        $this->table(
            ['Stage', 'File', 'Source≈', 'Created', 'Updated', 'Skipped', 'Conflicts'],
            collect($this->report)->map(fn ($r, $k) => [
                $k,
                $r['file'] ?? '',
                $r['source'],
                $r['created'],
                $r['updated'],
                $r['skipped'],
                $r['conflicts'],
            ])->values()->all()
        );

        foreach ($this->notes as $note) {
            $this->warn($note);
        }

        $this->info('Import finished. Users/roles preserved.');
        if ($fast) {
            $this->line('Fast path used: stream-once index + lookup cache + chunked apply'.($queueVouchers ? ' (vouchers queued)' : ' (vouchers inline chunks)').'.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, string> role => absolute path
     */
    private function discoverBundles(string $path): array
    {
        if (is_file($path) && str_ends_with(strtolower($path), '.zip')) {
            $extract = storage_path('app/tally-imports/extract_'.now()->format('YmdHis'));
            File::ensureDirectoryExists($extract);
            $zip = new ZipArchive;
            if ($zip->open($path) !== true) {
                $this->error('Cannot open zip: '.$path);

                return [];
            }
            $zip->extractTo($extract);
            $zip->close();
            $path = $extract;
        }

        $files = [];
        if (is_file($path) && preg_match('/\.xml$/i', $path)) {
            $files[] = $path;
        } elseif (is_dir($path)) {
            foreach (File::allFiles($path) as $file) {
                if (preg_match('/\.xml$/i', $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }

        $bundles = [];
        $transactionFiles = [];
        foreach ($files as $file) {
            $base = strtolower(pathinfo($file, PATHINFO_FILENAME));
            // Prefer quarterly splits (transaction-apr-jun.xml …); ignore monolithic Transactions-new when splits exist.
            if (str_contains($base, 'transaction')) {
                $transactionFiles[] = $file;
                continue;
            }
            $role = match (true) {
                str_contains($base, 'inventor') => 'inventory',
                str_contains($base, 'accout') || str_contains($base, 'account') => 'accounting',
                str_contains($base, 'all_master') || $base === 'master' => 'all_masters',
                str_contains($base, 'payroll') => 'payroll',
                str_contains($base, 'statut') => 'statutory',
                default => 'other_'.pathinfo($file, PATHINFO_FILENAME),
            };
            $bundles[$role] = $file;
        }

        if ($transactionFiles !== []) {
            usort($transactionFiles, function (string $a, string $b): int {
                return $this->transactionSortKey($a) <=> $this->transactionSortKey($b);
            });

            $quarterly = array_values(array_filter(
                $transactionFiles,
                fn (string $f) => (bool) preg_match(
                    '/^transaction-(apr-jun|jul-sep|oct-dec|jan-mar)$/i',
                    pathinfo($f, PATHINFO_FILENAME)
                )
            ));

            // When quarterly splits are present, skip the huge Transactions-new / Transactions.xml originals.
            $bundles['transactions'] = $quarterly !== [] ? $quarterly : $transactionFiles;
        }

        return $bundles;
    }

    private function transactionSortKey(string $path): string
    {
        $base = strtolower(pathinfo($path, PATHINFO_FILENAME));
        $order = [
            'transaction-apr-jun' => '1',
            'transaction-jul-sep' => '2',
            'transaction-oct-dec' => '3',
            'transaction-jan-mar' => '4',
        ];

        foreach ($order as $name => $key) {
            if ($base === $name) {
                return $key.$base;
            }
        }

        return '9'.$base;
    }

    /**
     * @param  array<string, string>  $bundles
     * @return list<array{key:string,target:string,file:string,label:string}>
     */
    private function buildStages(array $bundles, bool $withBooks): array
    {
        $stages = [];

        $accounting = $bundles['accounting'] ?? $bundles['all_masters'] ?? null;
        $inventory = $bundles['inventory'] ?? $bundles['all_masters'] ?? null;
        $transactionFiles = $bundles['transactions'] ?? [];
        if (is_string($transactionFiles)) {
            $transactionFiles = [$transactionFiles];
        }
        $payroll = $bundles['payroll'] ?? null;
        $statutory = $bundles['statutory'] ?? $payroll;

        // Prefer dedicated files; fall back to all_masters when a split file is missing.
        if ($accounting) {
            foreach (['cost_centres', 'ledgers'] as $key) {
                $stages[] = ['key' => $key, 'target' => 'dms', 'file' => $accounting, 'label' => "{$key} → dms"];
            }
            if ($withBooks) {
                foreach (['groups', 'cost_categories', 'cost_centres', 'ledgers', 'voucher_types'] as $key) {
                    $stages[] = ['key' => $key, 'target' => 'books', 'file' => $accounting, 'label' => "{$key} → books"];
                }
            }
        }

        if ($inventory) {
            foreach (['units', 'godowns', 'stock_groups', 'stock_items'] as $key) {
                $stages[] = ['key' => $key, 'target' => 'dms', 'file' => $inventory, 'label' => "{$key} → dms"];
            }
            if ($withBooks) {
                foreach (['units', 'godowns', 'stock_groups', 'stock_items'] as $key) {
                    $stages[] = ['key' => $key, 'target' => 'books', 'file' => $inventory, 'label' => "{$key} → books"];
                }
            }
        }

        if ($payroll) {
            $stages[] = ['key' => 'pay_heads', 'target' => 'dms', 'file' => $payroll, 'label' => 'pay_heads → dms'];
            if ($withBooks) {
                $stages[] = ['key' => 'pay_heads', 'target' => 'books', 'file' => $payroll, 'label' => 'pay_heads → books'];
            }
        }

        if ($statutory && $withBooks) {
            $stages[] = ['key' => 'currencies', 'target' => 'books', 'file' => $statutory, 'label' => 'currencies → books'];
        }

        // Import each quarterly (or single) transactions file one by one — DMS then Books per file.
        // Skip near-empty stubs (e.g. jan-mar shell with no vouchers).
        foreach ($transactionFiles as $transactions) {
            if (! is_file($transactions) || filesize($transactions) < 512) {
                continue;
            }
            $tag = basename($transactions);
            $stages[] = ['key' => 'vouchers', 'target' => 'dms', 'file' => $transactions, 'label' => "vouchers → dms ({$tag})"];
            if ($withBooks) {
                $stages[] = ['key' => 'vouchers', 'target' => 'books', 'file' => $transactions, 'label' => "vouchers → books ({$tag})"];
            }
        }

        return $stages;
    }

    /**
     * Parse each unique source XML once into tag/chunk stores (points 1 + 6).
     *
     * @param  list<array{key:string,target:string,file:string,label:string}>  $stages
     */
    private function indexSourceFiles(array $stages, int $chunkSize): void
    {
        $needed = [];
        foreach ($stages as $stage) {
            $file = $stage['file'];
            $tag = strtoupper($this->tagFor($stage['key']));
            $needed[$file][$tag] = true;
        }

        foreach ($needed as $file => $tags) {
            $workDir = storage_path('app/tally-imports/stream_'.$this->runId.'_'.md5($file));
            $this->info('Streaming index (once): '.basename($file).' → '.implode(', ', array_keys($tags)));
            $counts = TallyXmlStreamer::indexToDisk($file, array_keys($tags), $workDir, $chunkSize);
            $this->fileIndexes[$file] = ['dir' => $workDir, 'counts' => $counts];
            foreach ($counts as $tag => $count) {
                if ($count > 0) {
                    $this->line("  {$tag}: {$count}");
                }
            }
        }
    }

    /**
     * @param  array{key:string,target:string,file:string,label:string}  $stage
     */
    private function runStage(TallyInbound $inbound, array $stage, bool $fast = true, bool $queueVouchers = false, string $companyName = ''): void
    {
        $label = $stage['label'];
        $tag = strtoupper($this->tagFor($stage['key']));
        $this->info("Importing {$label} from ".basename($stage['file']).'...');

        if (! $fast || ! isset($this->fileIndexes[$stage['file']])) {
            $this->runStageLegacy($inbound, $stage);

            return;
        }

        $index = $this->fileIndexes[$stage['file']];
        $sourceCount = (int) ($index['counts'][$tag] ?? 0);
        $chunks = TallyXmlStreamer::chunkFiles($index['dir'], $tag);

        if ($chunks === []) {
            $this->record($label, 0, 0, 0, 0, 0, basename($stage['file']));
            $this->line('  (no entities)');

            return;
        }

        // Point 5: voucher chunks → queue or process inline one chunk at a time.
        if ($stage['key'] === 'vouchers' && $queueVouchers) {
            $dispatched = 0;
            foreach ($chunks as $i => $chunkPath) {
                ImportTallyVoucherChunkJob::dispatch(
                    $chunkPath,
                    $stage['target'],
                    $companyName !== '' ? $companyName : (string) $this->option('company'),
                    $this->runId,
                    $i + 1,
                );
                $dispatched++;
            }
            $this->record($label, $sourceCount, 0, 0, 0, 0, basename($stage['file']));
            $this->line("  queued {$dispatched} chunk(s) on [tally-import] (source≈{$sourceCount})");

            return;
        }

        $created = $updated = $skipped = $conflicts = 0;
        foreach ($chunks as $i => $chunkPath) {
            $chunkStarted = microtime(true);
            $xml = File::get($chunkPath);
            try {
                $result = $inbound->apply($stage['key'], $xml, $stage['target']);
                $created += (int) ($result['created'] ?? 0);
                $updated += (int) ($result['updated'] ?? 0);
                $skipped += (int) ($result['skipped'] ?? 0);
                $conflicts += (int) ($result['conflicts'] ?? 0);
            } catch (\Throwable $e) {
                $this->error('  chunk '.($i + 1).'/'.count($chunks).' failed: '.$e->getMessage());
                $conflicts++;
            }
            unset($xml);
            $elapsed = round(microtime(true) - $chunkStarted, 1);
            $this->line('  … chunk '.($i + 1).'/'.count($chunks)." ({$elapsed}s)");
        }

        $this->record($label, $sourceCount, $created, $updated, $skipped, $conflicts, basename($stage['file']));
        $this->line(sprintf(
            '  created=%d updated=%d skipped=%d conflicts=%d (source tags≈%d, chunks=%d)',
            $created,
            $updated,
            $skipped,
            $conflicts,
            $sourceCount,
            count($chunks)
        ));
    }

    /**
     * @param  array{key:string,target:string,file:string,label:string}  $stage
     */
    private function runStageLegacy(TallyInbound $inbound, array $stage): void
    {
        $label = $stage['label'];
        $xml = $this->readNormalized($stage['file']);
        $sourceCount = $this->countTags($xml, $this->tagFor($stage['key']));

        try {
            $result = $inbound->apply($stage['key'], $xml, $stage['target']);
        } catch (\Throwable $e) {
            $this->error("Failed {$label}: ".$e->getMessage());
            $this->record($label, $sourceCount, 0, 0, 0, 1, basename($stage['file']));

            return;
        }

        unset($xml);

        $this->record(
            $label,
            $sourceCount,
            (int) ($result['created'] ?? 0),
            (int) ($result['updated'] ?? 0),
            (int) ($result['skipped'] ?? 0),
            (int) ($result['conflicts'] ?? 0),
            basename($stage['file'])
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

    /**
     * @param  array<string, string>  $bundles
     */
    private function dryRun(array $bundles): void
    {
        $rows = [];
        $tags = ['GROUP', 'LEDGER', 'UNIT', 'GODOWN', 'STOCKGROUP', 'STOCKITEM', 'COSTCENTRE', 'COSTCATEGORY', 'VOUCHERTYPE', 'VOUCHER', 'ATTENDANCETYPE', 'CURRENCY'];

        foreach ($bundles as $role => $fileOrFiles) {
            $paths = is_array($fileOrFiles) ? $fileOrFiles : [$fileOrFiles];
            foreach ($paths as $file) {
                $xml = $this->readNormalized($file);
                foreach ($tags as $tag) {
                    $c = $this->countTags($xml, $tag);
                    if ($c > 0) {
                        $rows[] = [$role, basename($file), $tag, $c];
                    }
                }
                unset($xml);
            }
        }

        $this->table(['Bundle', 'File', 'Tag', 'Count'], $rows);

        $this->notes[] = 'Pay heads (payroll ledgers) + currencies import on live; ATTENDANCETYPE/EMPLOYEE still need dedicated HR rows if present.';
        $this->notes[] = 'Default is combined DMS+Books. Journal/Contra import into Books and appear in DMS Reports → Day Book.';
        $this->notes[] = 'Use --dms-only to skip Books. Delivery Challan still has no DMS commercial twin.';
        $this->notes[] = 'No data written (dry-run).';
        foreach ($this->notes as $note) {
            $this->warn($note);
        }
    }

    /**
     * @param  array<string, string>  $bundles
     */
    private function logUnsupportedBundles(array $bundles): void
    {
        foreach (['payroll' => 'Payroll', 'statutory' => 'Statutory'] as $role => $label) {
            if (! isset($bundles[$role])) {
                continue;
            }
            $xml = $this->readNormalized($bundles[$role]);
            $remaining = [];
            foreach (['ATTENDANCETYPE', 'EMPLOYEE', 'EMPLOYEEGROUP'] as $tag) {
                $c = $this->countTags($xml, $tag);
                if ($c > 0) {
                    $remaining[] = "{$tag}={$c}";
                }
            }
            unset($xml);
            if ($remaining === []) {
                continue;
            }
            $this->notes[] = $label.' tags without dedicated DMS models yet ('.implode(', ', $remaining).'). Pay heads/currencies are imported separately.';
        }
    }

    private function readNormalized(string $path): string
    {
        $cacheDir = storage_path('app/tally-imports/utf8_cache');
        File::ensureDirectoryExists($cacheDir);
        $cache = $cacheDir.'/'.md5($path.filesize($path).filemtime($path)).'.xml';
        if (is_file($cache) && filesize($cache) > 100) {
            return File::get($cache);
        }

        $raw = File::get($path);
        $xml = $this->normalizeXmlEncoding($raw);
        unset($raw);
        File::put($cache, $xml);

        return $xml;
    }

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

        if (str_contains($xml, "\0")) {
            $xml = str_replace("\0", '', $xml);
        }

        return TallyInbound::sanitizeTallyXml($xml);
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
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        if (! is_dir($dir)) {
            $this->error('Cannot create backup directory: '.$dir);

            return false;
        }

        // Dump to TEMP first — mysqldump on Windows often fails writing directly under storage/.
        $tmp = rtrim(sys_get_temp_dir(), '\\/').DIRECTORY_SEPARATOR.'dms_pre_tally_import_'.now()->format('Ymd_His').'.sql';
        $final = $dir.DIRECTORY_SEPARATOR.'dms_pre_tally_import_'.now()->format('Ymd_His').'.sql';

        $host = (string) config('database.connections.mysql.host');
        $port = (string) config('database.connections.mysql.port');
        $db = (string) config('database.connections.mysql.database');
        $user = (string) config('database.connections.mysql.username');
        $pass = (string) (config('database.connections.mysql.password') ?? '');

        $mysqldump = collect([
            'D:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'mysqldump',
        ])->first(fn ($bin) => $bin === 'mysqldump' || is_file($bin));

        // Do not use escapeshellarg() for host/user/db on Windows — it wraps in single quotes mysqldump rejects.
        $passwordArg = $pass !== '' ? ' -p'.$pass : '';
        $cmd = sprintf(
            '"%s" --host=%s --port=%s --user=%s%s --result-file="%s" %s',
            $mysqldump,
            $host,
            $port,
            $user,
            $passwordArg,
            $tmp,
            $db
        );

        if (! function_exists('exec')) {
            $this->error('exec() is disabled on this host. Take a manual DB backup and run without --backup.');

            return false;
        }

        $this->info("Creating backup → {$final}");
        $output = [];
        $code = 0;
        \exec($cmd.' 2>&1', $output, $code);
        if ($code !== 0 || ! is_file($tmp) || filesize($tmp) < 100) {
            $this->error(implode("\n", $output) ?: 'mysqldump produced an empty file');

            return false;
        }

        File::move($tmp, $final);
        $this->info('Backup OK ('.number_format(filesize($final)).' bytes)');

        return true;
    }

    private function ensureCompany(string $name, ?string $seedXml): Company
    {
        $company = Company::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($company) {
            $this->info("Using existing company #{$company->id} {$company->name}");

            return $company;
        }

        $gstin = null;
        $pan = null;
        if ($seedXml && preg_match('/<CMPGSTIN>([^<]+)</', $seedXml, $m)) {
            $gstin = trim($m[1]);
        }
        if ($seedXml && preg_match('/\b([A-Z]{5}[0-9]{4}[A-Z])\b/', $seedXml, $m)) {
            $pan = $m[1];
        }

        $company = Company::query()->create([
            'name' => $name,
            'code' => 'AVIT',
            'legal_name' => $name,
            'gstin' => $gstin ?: '03ABGPG1954N1ZN',
            'pan' => $pan ?: 'ABGPG1954N',
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
                ['company_id' => $company->id, 'starts_on' => $start],
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
        // Only create HO when no branch exists yet — cost centres become branches during import.
        if (Branch::query()->where('company_id', $company->id)->exists()) {
            return;
        }

        Branch::query()->create([
            'company_id' => $company->id,
            'code' => 'HO',
            'name' => 'Head Office',
            'address' => $company->address,
            'state' => $company->state,
            'pincode' => $company->pincode,
            'phone' => $company->phone,
            'gstin' => $company->gstin,
            'is_active' => true,
        ]);
    }

    private function companyHasTallyData(Company $company): bool
    {
        return DB::table('tally_sync_mappings')->exists()
            || DB::table('customers')->where('company_id', $company->id)->exists()
            || DB::table('products')->where('company_id', $company->id)->exists();
    }

    /**
     * Ensure Tally-imported account groups carry standard codes so purchase/sales
     * voucher rules (isSupplier / isPurchaseAccount) classify correctly.
     */
    private function backfillStandardGroupCodes(): void
    {
        $map = [
            'CREDITORS' => ['%sundry creditor%', '%creditor%'],
            'DEBTORS' => ['%sundry debtor%', '%debtor%'],
            'PURCHASE' => ['%purchase account%'],
            'SALES' => ['%sales account%'],
            'DUTIES' => ['Duties & Taxes', 'Duties and Taxes'],
            'BANK' => ['%bank account%', '%bank od%', '%bank occ%'],
            'CASH' => ['%cash-in-hand%', '%cash in hand%'],
        ];

        $updated = 0;
        foreach ($map as $code => $patterns) {
            // company_id+code is unique — never stamp a code that already exists.
            $taken = DB::table('acct_account_groups')
                ->where('code', $code)
                ->pluck('company_id')
                ->all();

            $rows = DB::table('acct_account_groups')
                ->whereNull('code')
                ->where(function ($q) use ($patterns) {
                    foreach ($patterns as $pattern) {
                        if (str_contains($pattern, '%')) {
                            $q->orWhere('name', 'like', $pattern);
                        } else {
                            $q->orWhere('name', $pattern);
                        }
                    }
                })
                ->when($taken !== [], fn ($q) => $q->whereNotIn('company_id', $taken))
                ->orderBy('id')
                ->get(['id', 'company_id']);

            $seenCompany = [];
            foreach ($rows as $row) {
                if (isset($seenCompany[$row->company_id])) {
                    continue;
                }
                DB::table('acct_account_groups')->where('id', $row->id)->update([
                    'code' => $code,
                    'updated_at' => now(),
                ]);
                $seenCompany[$row->company_id] = true;
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->line("Backfilled standard codes on {$updated} account group(s).");
        }
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
            'ledgers', 'bank_accounts', 'pay_heads' => 'LEDGER',
            'voucher_types' => 'VOUCHERTYPE',
            'stock_items' => 'STOCKITEM',
            'vouchers' => 'VOUCHER',
            'currencies' => 'CURRENCY',
            default => strtoupper($key),
        };
    }

    private function countTags(string $xml, string $tag): int
    {
        return preg_match_all('/<'.preg_quote($tag, '/').'[\s>]/i', $xml) ?: 0;
    }

    private function record(string $stage, int $source, int $created, int $updated, int $skipped, int $conflicts, string $file = ''): void
    {
        $this->report[$stage] = compact('source', 'created', 'updated', 'skipped', 'conflicts') + ['file' => $file];
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1024, 1).' KB';
    }

    private function writeReport(Company $company): void
    {
        $payload = [
            'at' => now()->toIso8601String(),
            'company_id' => $company->id,
            'company' => $company->name,
            'database' => config('database.connections.'.config('database.default').'.database'),
            'stages' => $this->report,
            'notes' => $this->notes,
            'counts' => [
                'users' => DB::table('users')->count(),
                'roles' => DB::table('roles')->count(),
                'customers' => DB::table('customers')->where('company_id', $company->id)->count(),
                'branches' => DB::table('branches')->where('company_id', $company->id)->count(),
                'warehouses' => DB::table('warehouses')->where('company_id', $company->id)->count(),
                'uoms' => DB::table('uoms')->count(),
                'products' => DB::table('products')->where('company_id', $company->id)->count(),
                'brands' => DB::table('brands')->where('company_id', $company->id)->count(),
                'payments' => DB::table('payments')->count(),
                'invoices' => DB::table('invoices')->count(),
                'purchase_invoices' => DB::table('purchase_invoices')->count(),
                'acct_ledgers' => DB::table('acct_ledgers')->count(),
                'acct_vouchers' => DB::table('acct_vouchers')->count(),
                'acct_products' => Schema::hasTable('acct_products') ? DB::table('acct_products')->count() : null,
                'tally_sync_mappings' => DB::table('tally_sync_mappings')->count(),
                'open_conflicts' => DB::table('tally_sync_conflicts')->where('status', 'open')->count(),
            ],
        ];

        $file = storage_path('app/tally-imports/import-report-'.now()->format('Ymd_His').'.json');
        File::put($file, json_encode($payload, JSON_PRETTY_PRINT));
        $this->info('Report written: '.$file);
    }
}
