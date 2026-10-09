<?php

namespace Tally\Http\Controllers;

use Tally\Audit\AuditLogger;
use Tally\DataExchange\Backup\BackupService;
use Tally\DataExchange\Backup\RestoreService;
use Tally\Models\DataBackup;
use Tally\Models\DataRestore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function index(): View
    {
        return view('tally::utilities.backup', [
            'backups' => DataBackup::query()->latest('id')->limit(25)->get(),
            'restores' => DataRestore::query()->with('backup')->latest('id')->limit(15)->get(),
            'confirmation' => RestoreService::CONFIRMATION,
        ]);
    }

    public function store(Request $request, BackupService $backups): RedirectResponse
    {
        try {
            $backup = $backups->create($request->user());
        } catch (Throwable $exception) {
            report($exception);
            app(AuditLogger::class)->record('backup_failed', 'backup', null, 'Backup failed.');

            return back()->with('error', 'The backup could not be completed.');
        }

        app(AuditLogger::class)->record('backup_completed', 'backup', $backup, 'Backup '.$backup->filename.' completed.');

        return redirect()
            ->route('books.tally.utilities.backup')
            ->with('status', 'Backup '.$backup->filename.' completed.');
    }

    public function download(DataBackup $backup): StreamedResponse
    {
        abort_unless($backup->status->value === 'completed', 404);
        abort_unless(Storage::disk($backup->disk)->exists($backup->path), 404);

        return Storage::disk($backup->disk)->download($backup->path, $backup->filename);
    }

    public function restore(DataBackup $backup, RestoreService $restores): View
    {
        return view('tally::utilities.restore', [
            'backup' => $backup,
            'inspection' => $restores->inspect($backup),
            'confirmation' => RestoreService::CONFIRMATION,
        ]);
    }

    public function apply(Request $request, DataBackup $backup, RestoreService $restores): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string'],
        ]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            return back()->withErrors(['password' => 'The password does not match the Super Admin account.'])->withInput();
        }

        app(AuditLogger::class)->record('restore_started', 'backup', $backup, 'Restore started for backup '.$backup->id.'.');

        try {
            $restores->restore($backup, $request->user(), $data['confirmation']);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            app(AuditLogger::class)->record('restore_failed', 'backup', $backup, 'Restore was rejected for backup '.$backup->id.'.');

            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            report($exception);
            app(AuditLogger::class)->record('restore_failed', 'backup', $backup, 'Restore failed and was rolled back for backup '.$backup->id.'.');

            return back()->with('error', 'The restore was rolled back. Existing data was left in place.');
        }

        app(AuditLogger::class)->record('restore_completed', 'backup', $backup, 'Restore completed for backup '.$backup->id.'.');

        return redirect()
            ->route('books.tally.utilities.backup')
            ->with('status', 'Restore completed. A safety backup was saved first. Select the company again if the workspace was cleared.');
    }
}
