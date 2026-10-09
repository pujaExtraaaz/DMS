<?php

namespace Tally\DataExchange\Backup;

use Tally\Context\WorkspaceContext;
use Tally\DataExchange\DataOperationStatus;
use Tally\Models\DataBackup;
use Tally\Models\DataRestore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class RestoreService
{
    public const CONFIRMATION = 'RESTORE DATA';

    public function __construct(
        private readonly DatabaseSnapshot $snapshot,
        private readonly BackupService $backups,
        private readonly WorkspaceContext $context,
    ) {}

    /**
     * @return array{valid: bool, errors: list<string>, backup_counts: array<string, int>, current_counts: array<string, int>, created_at: ?string}
     */
    public function inspect(DataBackup $backup): array
    {
        $errors = [];
        $document = $this->document($backup, $errors);
        $backupCounts = is_array($document['table_counts'] ?? null) ? $document['table_counts'] : [];
        $current = [];

        foreach ($this->snapshot->tables() as $table) {
            $current[$table] = DB::table($table)->count();
        }

        if ($backup->status !== DataOperationStatus::Completed) {
            $errors[] = 'Only a completed backup can be restored.';
        }

        return [
            'valid' => $errors === [] && is_array($document),
            'errors' => array_values(array_unique($errors)),
            'backup_counts' => $backupCounts,
            'current_counts' => $current,
            'created_at' => isset($document['created_at']) ? (string) $document['created_at'] : $backup->created_at?->toIso8601String(),
        ];
    }

    public function restore(DataBackup $backup, User $user, string $confirmation): DataRestore
    {
        if ($confirmation !== self::CONFIRMATION) {
            throw ValidationException::withMessages([
                'confirmation' => 'Type '.self::CONFIRMATION.' to confirm the restore.',
            ]);
        }

        $inspection = $this->inspect($backup);

        if (! $inspection['valid']) {
            throw ValidationException::withMessages([
                'backup' => $inspection['errors'][0] ?? 'This backup cannot be restored.',
            ]);
        }

        $safety = $this->backups->create($user);
        $log = DataRestore::query()->create([
            'data_backup_id' => $backup->id,
            'user_id' => $user->id,
            'safety_backup_id' => $safety->id,
            'status' => DataOperationStatus::Pending,
            'confirmation' => $confirmation,
            'table_counts' => [
                'before' => $inspection['current_counts'],
                'backup' => $inspection['backup_counts'],
            ],
            'metadata' => [
                'backup_filename' => $backup->filename,
                'safety_backup_id' => $safety->id,
            ],
            'started_at' => now(),
        ]);

        try {
            $errors = [];
            $document = $this->document($backup, $errors);

            if ($errors !== []) {
                throw ValidationException::withMessages([
                    'backup' => $errors[0],
                ]);
            }

            $tables = is_array($document['tables'] ?? null) ? $document['tables'] : [];

            DB::transaction(function () use ($tables, $user) {
                foreach (array_reverse($this->snapshot->tables()) as $table) {
                    foreach ($this->selfColumns($table) as $column) {
                        DB::table($table)->update([$column => null]);
                    }

                    DB::table($table)->delete();
                }

                foreach ($this->snapshot->tables() as $table) {
                    $this->insert($table, $tables[$table] ?? [], $user);
                }
            });

            $this->syncSequences();
            $this->context->clear();

            $log->update([
                'status' => DataOperationStatus::Completed,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $log->update([
                'status' => DataOperationStatus::Failed,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        }

        return $log->fresh();
    }

    /**
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function document(DataBackup $backup, array &$errors): array
    {
        if (! Storage::disk($backup->disk)->exists($backup->path)) {
            $errors[] = 'The backup file is missing.';

            return [];
        }

        $contents = Storage::disk($backup->disk)->get($backup->path);
        $document = json_decode((string) $contents, true);

        if (! is_array($document)) {
            $errors[] = 'The backup file is not valid JSON.';

            return [];
        }

        if (($document['format'] ?? null) !== DatabaseSnapshot::FORMAT || (int) ($document['version'] ?? 0) !== DatabaseSnapshot::VERSION) {
            $errors[] = 'This backup was made by a different format and cannot be restored.';
        }

        $tables = $document['tables'] ?? null;

        if (! is_array($tables)) {
            $errors[] = 'The backup does not contain a table snapshot.';

            return $document;
        }

        $checksum = $this->snapshot->checksum($tables);

        if (! hash_equals((string) ($document['checksum'] ?? ''), $checksum) || ($backup->checksum && ! hash_equals($backup->checksum, $checksum))) {
            $errors[] = 'The backup checksum does not match. The file was not restored.';
        }

        $known = $this->snapshot->tables();

        foreach ($tables as $name => $rows) {
            if (! in_array($name, $known, true)) {
                $errors[] = 'The backup contains unknown table "'.$name.'".';

                continue;
            }

            if (! is_array($rows)) {
                $errors[] = 'Table "'.$name.'" is not a list of rows.';

                continue;
            }

            $columns = Schema::getColumns($name);
            $allowed = array_column($columns, 'name');
            $present = $rows === [] ? $allowed : array_keys((array) $rows[0]);

            foreach ($columns as $column) {
                if (in_array($column['name'], $present, true)) {
                    continue;
                }

                if (! $column['nullable'] && $column['default'] === null && empty($column['auto_increment'])) {
                    $errors[] = 'Table "'.$name.'" is missing required column "'.$column['name'].'".';
                }
            }

            foreach ($present as $column) {
                if (! in_array($column, $allowed, true)) {
                    $errors[] = 'Table "'.$name.'" contains unknown column "'.$column.'".';
                }
            }
        }

        return $document;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insert(string $table, array $rows, User $user): void
    {
        if ($rows === []) {
            return;
        }

        $allowed = array_column(Schema::getColumns($table), 'name');
        $userColumns = $this->userColumns($table);
        $selfColumns = $this->selfColumns($table);
        $prepared = [];
        $deferred = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw ValidationException::withMessages([
                    'backup' => 'A row in '.$table.' is not valid.',
                ]);
            }

            $clean = array_intersect_key($row, array_flip($allowed));

            foreach ($userColumns as $column) {
                if (! array_key_exists($column, $clean) || $clean[$column] === null) {
                    continue;
                }

                $exists = DB::table('users')->whereKey($clean[$column])->exists();

                if (! $exists) {
                    $clean[$column] = $user->id;
                }
            }

            $pending = [];

            foreach ($selfColumns as $column) {
                $pending[$column] = $clean[$column] ?? null;
                $clean[$column] = null;
            }

            $prepared[] = $clean;
            $deferred[] = ['id' => $clean['id'] ?? null, 'values' => $pending];
        }

        foreach (array_chunk($prepared, 100) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        foreach ($deferred as $item) {
            $values = array_filter($item['values'], fn ($value) => $value !== null);

            if ($item['id'] === null || $values === []) {
                continue;
            }

            DB::table($table)->where('id', $item['id'])->update($values);
        }
    }

    /**
     * @return list<string>
     */
    private function selfColumns(string $table): array
    {
        $columns = [];

        foreach (Schema::getForeignKeys($table) as $key) {
            if (($key['foreign_table'] ?? '') !== $table) {
                continue;
            }

            foreach ($key['columns'] as $column) {
                $columns[] = (string) $column;
            }
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    private function userColumns(string $table): array
    {
        $columns = [];

        foreach (Schema::getForeignKeys($table) as $key) {
            if (($key['foreign_table'] ?? '') !== 'users') {
                continue;
            }

            foreach ($key['columns'] as $column) {
                $columns[] = (string) $column;
            }
        }

        return $columns;
    }

    private function syncSequences(): void
    {
        $driver = DB::getDriverName();

        foreach ($this->snapshot->tables() as $table) {
            if (! Schema::hasColumn($table, 'id')) {
                continue;
            }

            $max = (int) DB::table($table)->max('id');

            if ($driver === 'sqlite' && Schema::hasTable('sqlite_sequence')) {
                DB::table('sqlite_sequence')->updateOrInsert(['name' => $table], ['seq' => $max]);
            }

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `'.$table.'` AUTO_INCREMENT = '.($max + 1));
            }
        }
    }
}
