<?php

namespace Tally\DataExchange\Backup;

use Tally\DataExchange\DataOperationStatus;
use Tally\Models\DataBackup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackupService
{
    public function __construct(private readonly DatabaseSnapshot $snapshot) {}

    public function create(User $user): DataBackup
    {
        $disk = (string) config('operations.backup_disk', 'local');
        $filename = 'tally-backup-'.now()->format('Ymd-His').'-'.strtolower(\Illuminate\Support\Str::random(6)).'.json';
        $path = 'backups/'.$filename;
        $backup = DataBackup::query()->create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'filename' => $filename,
            'status' => DataOperationStatus::Pending,
            'started_at' => now(),
            'metadata' => [
                'format' => DatabaseSnapshot::FORMAT,
                'version' => DatabaseSnapshot::VERSION,
                'driver' => DB::getDriverName(),
                'database' => (string) config('database.connections.'.config('database.default').'.database'),
                'excluded_tables' => DatabaseSnapshot::EXCLUDED,
            ],
        ]);

        try {
            $tables = $this->snapshot->capture();
            $counts = [];

            foreach ($tables as $name => $rows) {
                $counts[$name] = count($rows);
            }

            $document = [
                'format' => DatabaseSnapshot::FORMAT,
                'version' => DatabaseSnapshot::VERSION,
                'created_at' => now()->toIso8601String(),
                'driver' => DB::getDriverName(),
                'excluded_tables' => DatabaseSnapshot::EXCLUDED,
                'table_counts' => $counts,
                'checksum' => $this->snapshot->checksum($tables),
                'tables' => $tables,
            ];
            $contents = json_encode($document, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            Storage::disk($disk)->put($path, $contents);

            $backup->update([
                'checksum' => $document['checksum'],
                'size_bytes' => strlen($contents),
                'status' => DataOperationStatus::Completed,
                'table_counts' => $counts,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Backup failed.', [
                'backup_id' => $backup->id,
                'disk' => $disk,
                'error' => $exception->getMessage(),
            ]);
            $backup->update([
                'status' => DataOperationStatus::Failed,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        }

        return $backup->fresh();
    }
}
