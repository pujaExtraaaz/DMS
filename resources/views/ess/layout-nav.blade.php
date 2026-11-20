<nav class="mb-5 flex flex-wrap gap-2 rounded-2xl border bg-white p-2 text-sm">
    @foreach([
        ['ess.home', 'Home', ['ess.home']],
        ['ess.holidays', 'Holidays', ['ess.holidays']],
        ['ess.leave', 'Leave', ['ess.leave', 'ess.leave.*']],
        ['ess.attendance', 'Attendance', ['ess.attendance', 'ess.regularisation.*']],
        ['ess.payslips', 'Payslips', ['ess.payslips', 'ess.payslips.*']],
        ['ess.approvals', 'Approvals', ['ess.approvals', 'ess.approvals.*']],
    ] as [$route, $label, $match])
        <a href="{{ route($route) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs(...$match) ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
    @endforeach
</nav>
