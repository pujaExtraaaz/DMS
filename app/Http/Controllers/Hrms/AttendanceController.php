<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Attendance;
use App\Domains\Hrms\Models\Department;
use App\Domains\Hrms\Models\Employee;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use App\Support\Traits\SortableAndSearchable;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    use SortableAndSearchable;

    public function __construct(protected AuditLogService $auditLogService) {}

    public function index(Request $request): View|StreamedResponse
    {
        $query = Attendance::query()
            ->with(['employee.department', 'recorder'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('attendance_date', $request->date))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $request->department_id));
            })
            ->when($request->filled('month'), function ($q) use ($request) {
                $parts = explode('-', $request->month);
                if (count($parts) === 2) {
                    $q->whereYear('attendance_date', $parts[0])->whereMonth('attendance_date', $parts[1]);
                }
            }, function ($q) use ($request) {
                if (! $request->filled('date')) {
                    $q->whereMonth('attendance_date', now()->month)->whereYear('attendance_date', now()->year);
                }
            });

        $this->applySearch(
            $query,
            $request->input('search'),
            ['status', 'notes'],
            ['employee' => ['name', 'employee_code']]
        );

        if ($request->input('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        // Summary stats
        $statsQuery = clone $query;
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'present' => (clone $statsQuery)->where('status', 'present')->count(),
            'absent' => (clone $statsQuery)->where('status', 'absent')->count(),
            'half_day' => (clone $statsQuery)->where('status', 'half_day')->count(),
            'late' => (clone $statsQuery)->where('status', 'late')->count(),
            'total_hours' => (float) (clone $statsQuery)->sum('hours_worked'),
        ];

        $allowedSorts = [
            'attendance_date' => 'attendance_date',
            'status' => 'status',
            'hours_worked' => 'hours_worked',
            'employee' => function ($q, $dir) {
                $q->join('employees', 'attendances.employee_id', '=', 'employees.id')
                  ->orderBy('employees.name', $dir)
                  ->select('attendances.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'attendance_date',
            defaultDirection: 'desc'
        );

        $items = $query->paginate(20)->withQueryString();

        $myEmployee = auth()->user()?->employee;
        $todayAttendance = $myEmployee
            ? Attendance::where('employee_id', $myEmployee->id)->whereDate('attendance_date', today())->first()
            : null;

        return view('hrms.attendances.index', [
            'items' => $items,
            'stats' => $stats,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'myEmployee' => $myEmployee,
            'todayAttendance' => $todayAttendance,
            'filters' => $request->only(['search', 'month', 'date', 'department_id', 'employee_id', 'status']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('hrms.attendances.form', [
            'item' => new Attendance([
                'attendance_date' => now()->toDateString(),
                'status' => 'present',
            ]),
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['recorded_by'] = auth()->id();

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'attendance_date' => $data['attendance_date'],
            ],
            $data
        );

        $this->auditLogService->record($attendance, 'saved');

        return $this->flashSuccess('Attendance record saved.', 'hrms.attendances.index');
    }

    public function edit(Attendance $attendance): View
    {
        return view('hrms.attendances.form', [
            'item' => $attendance,
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $this->validatedData($request, $attendance);
        $attendance->update($data);

        $this->auditLogService->record($attendance, 'updated');

        return $this->flashSuccess('Attendance record corrected.', 'hrms.attendances.index');
    }

    public function bulkCreate(Request $request): View
    {
        $date = $request->input('date', now()->toDateString());
        $departmentId = $request->input('department_id');

        $employees = Employee::query()
            ->where('status', 'active')
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('name')
            ->get();

        $existingAttendances = Attendance::whereDate('attendance_date', $date)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        return view('hrms.attendances.bulk', [
            'date' => $date,
            'departmentId' => $departmentId,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'employees' => $employees,
            'existingAttendances' => $existingAttendances,
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'attendance_date' => 'required|date',
            'records' => 'required|array',
            'records.*.employee_id' => 'required|exists:employees,id',
            'records.*.status' => 'required|string|in:present,absent,half_day,late,on_leave',
            'records.*.check_in' => 'nullable|date_format:H:i',
            'records.*.check_out' => 'nullable|date_format:H:i',
            'records.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['records'] as $record) {
                $hoursWorked = null;
                if (! empty($record['check_in']) && ! empty($record['check_out'])) {
                    $in = Carbon::parse($record['check_in']);
                    $out = Carbon::parse($record['check_out']);
                    if ($out->greaterThan($in)) {
                        $hoursWorked = round($in->diffInMinutes($out) / 60, 2);
                    }
                }

                Attendance::updateOrCreate(
                    [
                        'employee_id' => $record['employee_id'],
                        'attendance_date' => $data['attendance_date'],
                    ],
                    [
                        'status' => $record['status'],
                        'check_in' => $record['check_in'] ?? null,
                        'check_out' => $record['check_out'] ?? null,
                        'hours_worked' => $hoursWorked,
                        'notes' => $record['notes'] ?? null,
                        'recorded_by' => auth()->id(),
                    ]
                );
            }
        });

        return $this->flashSuccess('Daily attendance recorded for all employees.', 'hrms.attendances.index', ['date' => $data['attendance_date']]);
    }

    public function punch(): RedirectResponse
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            return $this->flashError('Your login account is not linked to any active employee profile.');
        }

        $today = today()->toDateString();
        $record = Attendance::where('employee_id', $employee->id)->whereDate('attendance_date', $today)->first();

        if (! $record) {
            // Punch In
            $checkIn = now()->format('H:i');
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => $today,
                'check_in' => $checkIn,
                'status' => 'present',
                'recorded_by' => auth()->id(),
            ]);

            $this->auditLogService->record($attendance, 'punch_in');

            return $this->flashSuccess('Punched in at ' . now()->format('h:i A') . '.', 'hrms.attendances.index');
        }

        if (empty($record->check_out)) {
            // Punch Out
            $checkOut = now()->format('H:i');
            $in = Carbon::parse($record->check_in);
            $out = Carbon::parse($checkOut);
            $hours = $out->greaterThan($in) ? round($in->diffInMinutes($out) / 60, 2) : 0;

            $record->update([
                'check_out' => $checkOut,
                'hours_worked' => $hours,
            ]);

            $this->auditLogService->record($record, 'punch_out');

            return $this->flashSuccess('Punched out at ' . now()->format('h:i A') . " ({$hours} hrs worked).", 'hrms.attendances.index');
        }

        return $this->flashError('You have already punched out for today.');
    }

    protected function validatedData(Request $request, ?Attendance $attendance = null): array
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|string|in:present,absent,half_day,late,on_leave',
            'hours_worked' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (empty($data['hours_worked']) && ! empty($data['check_in']) && ! empty($data['check_out'])) {
            $in = Carbon::parse($data['check_in']);
            $out = Carbon::parse($data['check_out']);
            if ($out->greaterThan($in)) {
                $data['hours_worked'] = round($in->diffInMinutes($out) / 60, 2);
            }
        }

        return $data;
    }

    protected function exportCsv($records): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_' . now()->format('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Code', 'Employee Name', 'Department', 'Date', 'Check In', 'Check Out', 'Status', 'Hours Worked', 'Notes']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->employee?->employee_code,
                    $r->employee?->name,
                    $r->employee?->department?->name,
                    $r->attendance_date->format('Y-m-d'),
                    $r->check_in,
                    $r->check_out,
                    $r->status,
                    $r->hours_worked,
                    $r->notes,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
