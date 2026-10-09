<?php

namespace App\Domains\Sync\Http;

use App\Domains\Sync\Support\SyncGuard;
use App\Domains\Sync\Support\SyncRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncConflictController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'link_id' => ['required', 'integer'],
            'choice' => ['required', 'in:dms,books'],
        ]);

        $row = DB::table('sync_entity_links')->where('id', $data['link_id'])->first();
        abort_unless($row, 404);

        $keepBooks = $data['choice'] === 'books';
        $sourceClass = $keepBooks ? $row->acct_type : $row->dms_type;
        $sourceId = $keepBooks ? $row->acct_id : $row->dms_id;
        $otherClass = $keepBooks ? $row->dms_type : $row->acct_type;
        $otherId = $keepBooks ? $row->dms_id : $row->acct_id;

        if (! is_string($sourceClass) || ! class_exists($sourceClass) || ! is_subclass_of($sourceClass, Model::class)) {
            abort(404);
        }

        $source = $sourceClass::query()->find($sourceId);
        if (! $source) {
            $otherExists = is_string($otherClass) && class_exists($otherClass) && $otherClass::query()->whereKey($otherId)->exists();
            if ($otherExists) {
                DB::table('sync_entity_links')->where('id', $row->id)->update([
                    'sync_status' => 'synced',
                    'last_error' => null,
                    'last_synced_at' => now(),
                    'updated_at' => now(),
                ]);

                return back()->with('status', 'Kept the copy that is still on file.');
            }

            return back()->with('error', 'That copy no longer exists.');
        }

        try {
            SyncGuard::run(function () use ($source) {
                $handler = SyncRegistry::handler($source);
                if (! $handler) {
                    throw new \RuntimeException('This record has no sync handler.');
                }
                $handler->sync($source);
            });
        } catch (Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $fresh = DB::table('sync_entity_links')->where('id', $row->id)->first();
        if ($fresh && $fresh->sync_status === 'conflict') {
            return back()->with('error', $fresh->last_error ?: 'That copy is posted, so it was left unchanged.');
        }

        return back()->with('status', 'The two copies now match.');
    }
}
