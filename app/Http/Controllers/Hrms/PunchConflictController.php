<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\AttendancePunch;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Services\AttendanceCalculator;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PunchConflictController extends Controller
{
    public function __construct(protected AttendanceCalculator $calculator) {}

    public function index(Request $request): View
    {
        $from = Carbon::parse($request->input('from', now()->subDays(14)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $punches = AttendancePunch::query()
            ->with('employee')
            ->whereNotNull('employee_id')
            ->whereBetween('punched_at', [$from, $to])
            ->orderBy('punched_at')
            ->get()
            ->groupBy(fn (AttendancePunch $p) => $p->employee_id.'|'.$p->punched_at->toDateString());

        $conflicts = [];
        foreach ($punches as $key => $dayPunches) {
            $sources = $dayPunches->pluck('source')->unique()->values();
            $hasEssl = $sources->contains('essl');
            $hasOther = $sources->contains(fn ($s) => in_array($s, ['web', 'manual'], true));
            if (! ($hasEssl && $hasOther)) {
                continue;
            }

            [$employeeId, $date] = explode('|', $key, 2);
            $attendance = Attendance::query()
                ->where('employee_id', $employeeId)
                ->whereDate('attendance_date', $date)
                ->first();

            $conflicts[] = [
                'key' => $key,
                'employee' => $dayPunches->first()->employee,
                'date' => $date,
                'essl' => $dayPunches->where('source', 'essl')->values(),
                'other' => $dayPunches->whereIn('source', ['web', 'manual'])->values(),
                'attendance' => $attendance,
            ];
        }

        return view('hrms.punch-conflicts.index', [
            'conflicts' => $conflicts,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function resolve(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'prefer' => 'required|in:essl,web,manual',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $day = Carbon::parse($data['date']);
        $prefer = $data['prefer'] === 'essl' ? ['essl'] : ['web', 'manual'];

        $dayPunches = AttendancePunch::query()
            ->where('employee_id', $employee->id)
            ->whereDate('punched_at', $day->toDateString())
            ->whereIn('source', $prefer)
            ->orderBy('punched_at')
            ->get();

        if ($dayPunches->isEmpty()) {
            return $this->flashError('No punches found for the preferred source.');
        }

        $first = $dayPunches->first();
        $last = $dayPunches->count() > 1 ? $dayPunches->last() : null;

        DB::transaction(function () use ($employee, $day, $first, $last, $data) {
            $attendance = $this->calculator->upsertDay(
                $employee,
                $day,
                $first->punched_at->format('H:i:s'),
                $last?->punched_at->format('H:i:s'),
                $data['prefer'] === 'essl' ? 'essl' : 'manual'
            );
            $attendance->update([
                'notes' => trim(($attendance->notes ?? '').' [Resolved prefer='.$data['prefer'].']'),
                'recorded_by' => auth()->id(),
            ]);
        });

        return $this->flashSuccess('Punch conflict resolved using '.$data['prefer'].'.', 'hrms.punch-conflicts.index');
    }
}
