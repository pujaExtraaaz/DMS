<x-tally::layouts.app title="Attendance">
    <x-tally::shell.page title="Attendance" section="Transactions" :description="$company->name">
        <form class="tally-vch" method="POST" action="{{ tally_route('books.tally.payroll.attendance.store') }}">
            @csrf
            <header class="tally-vch-bar">
                <strong>Attendance Voucher</strong>
                <span>{{ $company->name }}</span>
            </header>
            <div class="tally-vch-badge">Attendance</div>
            <x-tally::form.field name="attendance_date" label="Date" required>
                <input class="input" type="date" name="attendance_date" value="{{ old('attendance_date', now()->toDateString()) }}" required>
            </x-form.field>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Employee</th><th>Attendance</th></tr></thead>
                    <tbody>
                        @foreach ($employees as $index => $employee)
                            <tr>
                                <td>
                                    {{ $employee->name }}
                                    <input type="hidden" name="rows[{{ $index }}][employee_id]" value="{{ $employee->id }}">
                                </td>
                                <td>
                                    <select class="input" name="rows[{{ $index }}][attendance_type]">
                                        <option value="">Skip</option>
                                        @foreach (['present' => 'Present', 'absent' => 'Absent', 'leave' => 'Leave', 'half' => 'Half day'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('rows.'.$index.'.attendance_type') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <a class="btn" data-esc href="{{ tally_route('books.tally.payroll.index') }}">Q: Quit</a>
                <button class="btn btn-primary" type="submit">A: Accept</button>
            </div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Employee</th><th>Type</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->attendance_date->format('d M Y') }}</td>
                            <td>{{ $row->employee?->name }}</td>
                            <td>{{ ucfirst($row->attendance_type) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No attendance entered.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
