@extends('layouts.dms')
@section('title', $employee->name)
@section('content')
<x-ui.page-header :title="$employee->name" :description="'Employee Code: ' . $employee->employee_code">
<x-slot name="actions">
    <x-ui.button variant="secondary" :href="route('hrms.employees.index')">Back to Employees</x-ui.button>
    <x-ui.button variant="primary" :href="route('hrms.employees.edit', $employee)">Edit Profile</x-ui.button>
</x-slot>
</x-ui.page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Primary Details & Reporting Hierarchy -->
    <div class="space-y-6 lg:col-span-1">
        <x-ui.card title="Employee Details">
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Department</dt><dd class="font-medium">{{ $employee->department?->name ?? '—' }}</dd></div>
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Designation</dt><dd class="font-medium">{{ $employee->designation?->name ?? '—' }}</dd></div>
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Branch</dt><dd class="font-medium">{{ $employee->branch?->name ?? '—' }}</dd></div>
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $employee->email ?? '—' }}</dd></div>
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ $employee->phone ?? '—' }}</dd></div>
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Joining Date</dt><dd class="font-medium">{{ optional($employee->joining_date)->format('d M Y') ?? '—' }}</dd></div>
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Date of Birth</dt><dd class="font-medium">{{ optional($employee->date_of_birth)->format('d M Y') ?? '—' }}</dd></div>
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Linked User</dt><dd class="font-medium">{{ $employee->user?->name ?? 'None' }}</dd></div>
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Status</dt><dd><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $employee->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800' }}">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</span></dd></div>
                <div class="py-2 flex justify-between"><dt class="text-slate-500">Role</dt><dd class="font-medium">{{ $employee->is_salesperson ? 'Salesperson' : 'General Staff' }}</dd></div>
                <div class="py-2"><dt class="text-slate-500 mb-1">Address</dt><dd class="font-medium text-slate-700 whitespace-pre-line">{{ $employee->address ?? '—' }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Reporting Hierarchy">
            <div class="text-sm space-y-4">
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-400 block mb-1">Reporting Manager</span>
                    @if($employee->manager)
                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between">
                            <div>
                                <a href="{{ route('hrms.employees.show', $employee->manager) }}" class="font-medium text-indigo-600 hover:underline">{{ $employee->manager->name }}</a>
                                <div class="text-xs text-slate-500">{{ $employee->manager->designation?->name ?? 'Manager' }}</div>
                            </div>
                            <span class="text-xs text-slate-400 font-mono">{{ $employee->manager->employee_code }}</span>
                        </div>
                    @else
                        <p class="text-slate-500 text-xs">No direct reporting manager assigned.</p>
                    @endif
                </div>

                @if($employee->directReports->isNotEmpty())
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-400 block mb-1">Direct Reports ({{ $employee->directReports->count() }})</span>
                    <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 overflow-hidden">
                        @foreach($employee->directReports as $report)
                            <li class="p-2 bg-white flex items-center justify-between hover:bg-slate-50 text-xs">
                                <div>
                                    <a href="{{ route('hrms.employees.show', $report) }}" class="font-medium text-slate-700 hover:text-indigo-600">{{ $report->name }}</a>
                                    <div class="text-slate-400">{{ $report->designation?->name ?? 'Staff' }}</div>
                                </div>
                                <span class="font-mono text-slate-400">{{ $report->employee_code }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </x-ui.card>
    </div>

    <!-- Right Column: Leave Balances, Documents Vault & Recent Attendance -->
    <div class="space-y-6 lg:col-span-2">
        <!-- Leave Balances -->
        <x-ui.card title="Leave Balances">
            @if($employee->leaveBalances->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Leave Type</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500 uppercase">Year</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500 uppercase">Opening</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500 uppercase">Used</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500 uppercase">Closing / Available</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($employee->leaveBalances as $bal)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $bal->leaveType?->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-center text-slate-600">{{ $bal->year }}</td>
                                    <td class="px-3 py-2 text-right font-mono text-slate-600">{{ number_format($bal->opening_balance, 1) }}</td>
                                    <td class="px-3 py-2 text-right font-mono text-amber-600">{{ number_format($bal->used_balance, 1) }}</td>
                                    <td class="px-3 py-2 text-right font-mono font-semibold {{ $bal->closing_balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($bal->closing_balance, 1) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-sm text-slate-500 py-3 text-center">No leave balance allocated yet.</div>
            @endif
        </x-ui.card>

        <!-- Document Vault -->
        <x-ui.card title="Employee Document Vault">
            <div class="space-y-4">
                <!-- Upload Form -->
                <form method="POST" action="{{ route('hrms.employees.documents.store', $employee) }}" enctype="multipart/form-data" class="p-4 bg-slate-50 rounded-lg border border-slate-200 space-y-3">
                    @csrf
                    <div class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Upload New Document</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Document Type *</label>
                            <input type="text" name="document_type" required placeholder="e.g. Aadhaar, PAN, Contract, Degree" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Document Number</label>
                            <input type="text" name="document_number" placeholder="e.g. ABCDE1234F" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Expiry Date</label>
                            <input type="date" name="expires_on" class="block w-full rounded-md border-gray-300 text-xs shadow-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Select File (PDF, DOC, JPG, PNG up to 10MB) *</label>
                            <input type="file" name="document_file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                        <div class="text-right">
                            <x-ui.button type="submit" size="sm" variant="primary">Upload Document</x-ui.button>
                        </div>
                    </div>
                </form>

                <!-- Document List -->
                @if($employee->documents->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase">Document</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase">Doc #</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase">Expiry</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase">Status</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($employee->documents as $doc)
                                    @php
                                        $isExpired = $doc->expires_on && $doc->expires_on->isPast();
                                        $isExpiringSoon = $doc->expires_on && !$isExpired && $doc->expires_on->diffInDays(now()) <= 30;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800">{{ $doc->document_type }}</td>
                                        <td class="px-3 py-2 text-slate-600 font-mono">{{ $doc->document_number ?? '—' }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ optional($doc->expires_on)->format('d M Y') ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            @if($isExpired)
                                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-700">Expired</span>
                                            @elseif($isExpiringSoon)
                                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">Expiring Soon</span>
                                            @else
                                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700">Valid</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right space-x-2">
                                            <a href="{{ route('hrms.documents.download', $doc) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Download</a>
                                            <form method="POST" action="{{ route('hrms.documents.destroy', $doc) }}" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-xs text-slate-500 text-center py-2">No documents stored in the vault yet.</p>
                @endif
            </div>
        </x-ui.card>

        <!-- Recent Attendance -->
        <x-ui.card title="Recent Attendance Records">
            @if($employee->attendances->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase">Date</th>
                                <th class="px-3 py-2 text-center font-semibold text-slate-500 uppercase">In</th>
                                <th class="px-3 py-2 text-center font-semibold text-slate-500 uppercase">Out</th>
                                <th class="px-3 py-2 text-center font-semibold text-slate-500 uppercase">Status</th>
                                <th class="px-3 py-2 text-right font-semibold text-slate-500 uppercase">Hours</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($employee->attendances as $att)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $att->attendance_date->format('d M Y') }}</td>
                                    <td class="px-3 py-2 text-center font-mono">{{ $att->check_in ?? '—' }}</td>
                                    <td class="px-3 py-2 text-center font-mono">{{ $att->check_out ?? '—' }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $att->status === 'present' ? 'bg-emerald-100 text-emerald-700' : ($att->status === 'absent' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                            {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right font-mono">{{ $att->hours_worked ? number_format($att->hours_worked, 2) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-xs text-slate-500 text-center py-2">No attendance records logged.</p>
            @endif
        </x-ui.card>
    </div>
</div>
@endsection
