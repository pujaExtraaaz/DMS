<?php

namespace App\Domains\Hrms\Services;

use App\Domains\Hrms\Models\AttendancePunch;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\EsslSyncLog;
use App\Domains\Hrms\Services\Essl\EsslAttendanceDriver;
use App\Domains\Hrms\Services\Essl\HttpEsslDriver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EsslSyncService
{
    public function __construct(
        protected AttendanceCalculator $attendanceCalculator,
        protected ?EsslAttendanceDriver $driver = null
    ) {
        $this->driver ??= new HttpEsslDriver;
    }

    public function sync(?Carbon $from = null, ?Carbon $to = null): EsslSyncLog
    {
        $from ??= now()->subDay()->startOfDay();
        $to ??= now()->endOfDay();

        $log = EsslSyncLog::create([
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $punches = $this->driver->pullPunches($from, $to);
            $imported = 0;
            $unmatched = 0;
            $employeesTouched = [];

            DB::transaction(function () use ($punches, &$imported, &$unmatched, &$employeesTouched) {
                foreach ($punches as $punch) {
                    $code = (string) ($punch['biometric_code'] ?? '');
                    $at = Carbon::parse($punch['punched_at']);
                    $externalId = (string) ($punch['external_id'] ?? ($code.'|'.$at->format('Y-m-d H:i:s')));

                    $employee = $code !== ''
                        ? Employee::query()->where('biometric_code', $code)->first()
                        : null;

                    if (! $employee) {
                        $unmatched++;
                    } else {
                        $employeesTouched[$employee->id] = $employee;
                    }

                    AttendancePunch::query()->updateOrCreate(
                        [
                            'source' => 'essl',
                            'external_id' => $externalId,
                        ],
                        [
                            'employee_id' => $employee?->id,
                            'biometric_code' => $code ?: null,
                            'punched_at' => $at,
                            'direction' => $punch['direction'] ?? 'unknown',
                            'device_id' => $punch['device_id'] ?? null,
                        ]
                    );
                    $imported++;
                }
            });

            foreach ($employeesTouched as $employee) {
                $this->rebuildAttendanceFromPunches($employee, $from, $to);
            }

            $log->update([
                'finished_at' => now(),
                'fetched' => count($punches),
                'imported' => $imported,
                'unmatched' => $unmatched,
                'status' => 'ok',
                'message' => 'Sync completed',
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'finished_at' => now(),
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);
        }

        return $log->fresh();
    }

    public function rebuildAttendanceFromPunches(Employee $employee, Carbon $from, Carbon $to): void
    {
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $dayPunches = AttendancePunch::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('punched_at', [$dayStart, $dayEnd])
                ->orderBy('punched_at')
                ->get();

            $first = $dayPunches->first();
            $last = $dayPunches->count() > 1 ? $dayPunches->last() : null;

            $this->attendanceCalculator->upsertDay(
                $employee,
                $cursor->copy(),
                $first?->punched_at?->format('H:i:s'),
                $last?->punched_at?->format('H:i:s'),
                'essl'
            );

            $cursor->addDay();
        }
    }

    /**
     * Accept pushed punches from an office-side agent (Tally-connector style).
     *
     * @param  list<array{biometric_code:string,punched_at:string,direction?:string,device_id?:string,external_id?:string}>  $rows
     */
    public function ingestPushedPunches(array $rows): array
    {
        $imported = 0;
        $unmatched = 0;
        $touched = [];

        foreach ($rows as $row) {
            $code = (string) ($row['biometric_code'] ?? '');
            $at = Carbon::parse($row['punched_at']);
            $externalId = (string) ($row['external_id'] ?? ('push|'.$code.'|'.$at->format('Y-m-d H:i:s')));
            $employee = $code !== '' ? Employee::query()->where('biometric_code', $code)->first() : null;
            if (! $employee) {
                $unmatched++;
            } else {
                $touched[$employee->id] = [$employee, $at->copy()->startOfDay()];
            }

            AttendancePunch::query()->updateOrCreate(
                ['source' => 'essl', 'external_id' => $externalId],
                [
                    'employee_id' => $employee?->id,
                    'biometric_code' => $code ?: null,
                    'punched_at' => $at,
                    'direction' => $row['direction'] ?? 'unknown',
                    'device_id' => $row['device_id'] ?? null,
                ]
            );
            $imported++;
        }

        foreach ($touched as [$employee, $day]) {
            $this->rebuildAttendanceFromPunches($employee, $day, $day->copy()->endOfDay());
        }

        return compact('imported', 'unmatched');
    }
}
