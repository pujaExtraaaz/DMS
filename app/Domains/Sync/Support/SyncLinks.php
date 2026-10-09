<?php

namespace App\Domains\Sync\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class SyncLinks
{
    public static function acctId(string $entityKey, Model $dms): ?int
    {
        $id = DB::table('sync_entity_links')
            ->where('entity_key', $entityKey)
            ->where('dms_type', $dms::class)
            ->where('dms_id', $dms->getKey())
            ->value('acct_id');

        return $id ? (int) $id : null;
    }

    public static function rowFor(Model $model): ?object
    {
        return DB::table('sync_entity_links')
            ->where(function ($query) use ($model) {
                $query->where('dms_type', $model::class)->where('dms_id', $model->getKey());
            })
            ->orWhere(function ($query) use ($model) {
                $query->where('acct_type', $model::class)->where('acct_id', $model->getKey());
            })
            ->first();
    }

    public static function conflictOnPage(): ?object
    {
        $route = request()->route();
        if (! $route) {
            return null;
        }

        foreach ($route->parameters() as $value) {
            if (! $value instanceof Model) {
                continue;
            }
            $row = self::rowFor($value);
            if ($row && $row->sync_status === 'conflict') {
                return $row;
            }
        }

        return null;
    }

    public static function forget(string $entityKey, Model $model): void
    {
        DB::table('sync_entity_links')
            ->where('entity_key', $entityKey)
            ->where(function ($query) use ($model) {
                $query->where(fn ($inner) => $inner->where('dms_type', $model::class)->where('dms_id', $model->getKey()))
                    ->orWhere(fn ($inner) => $inner->where('acct_type', $model::class)->where('acct_id', $model->getKey()));
            })
            ->delete();
    }

    public static function dmsId(string $entityKey, Model $acct): ?int
    {
        $id = DB::table('sync_entity_links')
            ->where('entity_key', $entityKey)
            ->where('acct_type', $acct::class)
            ->where('acct_id', $acct->getKey())
            ->value('dms_id');

        return $id ? (int) $id : null;
    }

    public static function store(string $entityKey, Model $dms, Model $acct, string $status = 'synced', ?string $error = null): void
    {
        $now = now();
        $values = [
            'dms_type' => $dms::class,
            'dms_id' => $dms->getKey(),
            'acct_type' => $acct::class,
            'acct_id' => $acct->getKey(),
            'sync_status' => $status,
            'last_error' => $error,
            'last_synced_at' => $status === 'synced' ? $now : null,
            'updated_at' => $now,
        ];

        $existing = DB::table('sync_entity_links')
            ->where('entity_key', $entityKey)
            ->where('dms_type', $dms::class)
            ->where('dms_id', $dms->getKey())
            ->first();

        if ($existing) {
            DB::table('sync_entity_links')->where('id', $existing->id)->update($values);

            return;
        }

        DB::table('sync_entity_links')->insert($values + [
            'entity_key' => $entityKey,
            'sync_version' => 1,
            'created_at' => $now,
        ]);
    }
}
