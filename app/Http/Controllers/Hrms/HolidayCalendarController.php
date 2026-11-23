<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Holiday;
use App\Domains\Hrms\Models\HolidayCalendar;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $items = HolidayCalendar::query()
            ->with(['branch', 'company'])
            ->withCount('holidays')
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->integer('year')))
            ->orderByDesc('year')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('hrms.holidays.index', [
            'items' => $items,
            'filters' => $request->only(['year']),
        ]);
    }

    public function create(): View
    {
        return view('hrms.holidays.calendar-form', [
            'item' => new HolidayCalendar([
                'year' => (int) now()->year,
                'company_id' => auth()->user()?->company_id,
            ]),
            'companies' => Company::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'year' => 'required|integer|min:2020|max:2100',
            'name' => 'required|string|max:120',
        ]);

        $calendar = HolidayCalendar::create($data);

        return $this->flashSuccess('Holiday calendar created.', 'hrms.holidays.show', [$calendar]);
    }

    public function show(HolidayCalendar $holiday): View
    {
        $holiday->load(['holidays', 'branch', 'company']);

        return view('hrms.holidays.show', [
            'calendar' => $holiday,
        ]);
    }

    public function edit(HolidayCalendar $holiday): View
    {
        return view('hrms.holidays.calendar-form', [
            'item' => $holiday,
            'companies' => Company::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, HolidayCalendar $holiday): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'year' => 'required|integer|min:2020|max:2100',
            'name' => 'required|string|max:120',
        ]);
        $holiday->update($data);

        return $this->flashSuccess('Holiday calendar updated.', 'hrms.holidays.show', [$holiday]);
    }

    public function destroy(HolidayCalendar $holiday): RedirectResponse
    {
        $holiday->delete();

        return $this->flashSuccess('Holiday calendar deleted.', 'hrms.holidays.index');
    }

    public function publish(HolidayCalendar $holiday): RedirectResponse
    {
        $holiday->update(['is_published' => true, 'published_at' => now()]);

        return $this->flashSuccess('Holiday calendar published for ESS.', 'hrms.holidays.show', [$holiday]);
    }

    public function storeHoliday(Request $request, HolidayCalendar $holiday): RedirectResponse
    {
        $request->merge([
            'category' => strtolower((string) $request->input('category')),
            'kind' => strtolower((string) $request->input('kind')),
        ]);
        $data = $request->validate([
            'holiday_date' => 'required|date',
            'name' => 'required|string|max:120',
            'category' => 'required|in:national,state,festival,other',
            'kind' => 'required|in:compulsory,optional,restricted',
            'notes' => 'nullable|string',
        ]);
        $holiday->holidays()->create($data);

        return $this->flashSuccess('Holiday added.', 'hrms.holidays.show', [$holiday]);
    }

    public function updateHoliday(Request $request, Holiday $holidayItem): RedirectResponse
    {
        $request->merge([
            'category' => strtolower((string) $request->input('category')),
            'kind' => strtolower((string) $request->input('kind')),
        ]);
        $data = $request->validate([
            'holiday_date' => 'required|date',
            'name' => 'required|string|max:120',
            'category' => 'required|in:national,state,festival,other',
            'kind' => 'required|in:compulsory,optional,restricted',
            'notes' => 'nullable|string',
        ]);
        $holidayItem->update($data);

        return $this->flashSuccess('Holiday updated.', 'hrms.holidays.show', [$holidayItem->holiday_calendar_id]);
    }

    public function destroyHoliday(Holiday $holidayItem): RedirectResponse
    {
        $calendarId = $holidayItem->holiday_calendar_id;
        $holidayItem->delete();

        return $this->flashSuccess('Holiday removed.', 'hrms.holidays.show', [$calendarId]);
    }
}
