@extends('layouts.dms')
@section('title', $item->exists ? 'Payroll · Edit Structure' : 'Payroll · Create Structure')

@php
    $payHeadMap = $payHeads->keyBy('id');
    $lineRows = collect($lines)->values()->all();
@endphp

@section('content')
<x-hrms.payroll-nav active="structures" />

<x-ui.card :title="$item->exists ? 'Edit Salary Structure' : 'Create Salary Structure'">
    <div class="mb-4">
        <a href="{{ route('hrms.salary-structures.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">← Back to salary structures</a>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $item->exists ? route('hrms.salary-structures.update', $item) : route('hrms.salary-structures.store') }}"
          class="space-y-5"
          x-data="salaryStructureForm(@js([
              'lines' => collect($lineRows)->map(fn ($row) => [
                  'pay_head_id' => (int) ($row['pay_head_id'] ?? 0),
                  'amount' => (string) ($row['amount'] ?? '0.00'),
              ])->values()->all(),
              'payHeads' => $payHeads->map(fn ($h) => [
                  'id' => $h->id,
                  'name' => $h->name,
                  'code' => $h->code,
                  'type' => $h->type,
                  'is_system' => (bool) $h->is_system,
              ])->values()->all(),
          ]))">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select employee</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(old('employee_id', $item->employee_id) == $e->id)>
                            {{ $e->name }} ({{ $e->employee_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <x-ui.input name="name" label="Label (optional)" :value="old('name', $item->name)" placeholder="e.g. FY 2026 package" />
            <x-ui.input name="effective_from" label="Effective From" type="date" :value="old('effective_from', optional($item->effective_from)->format('Y-m-d'))" />
            <x-ui.input name="effective_to" label="Effective To" type="date" :value="old('effective_to', optional($item->effective_to)->format('Y-m-d'))" />
            <label class="flex items-center gap-2 text-sm pt-7">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600">
                Active structure (deactivates other active ones for this employee)
            </label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $item->notes) }}</textarea>
        </div>

        <div class="rounded-xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between gap-3 bg-slate-50 px-4 py-3 border-b border-slate-200">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Pay Components</h3>
                    <p class="text-xs text-slate-500">Standard heads (Basic, HRA, Allowances) plus any custom pay heads you add.</p>
                </div>
                <button type="button" @click="addLine()"
                        class="inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"
                        x-show="availablePayHeads.length > 0">
                    + Add component
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white">
                        <tr class="border-b border-slate-100 text-left text-xs font-semibold text-slate-500">
                            <th class="px-4 py-2">Pay Head</th>
                            <th class="px-4 py-2">Type</th>
                            <th class="px-4 py-2 text-right w-40">Amount</th>
                            <th class="px-4 py-2 w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-slate-50">
                                <td class="px-4 py-2">
                                    <input type="hidden" :name="`lines[${index}][pay_head_id]`" :value="line.pay_head_id">
                                    <span class="font-medium text-slate-900" x-text="payHeadLabel(line.pay_head_id)"></span>
                                    <span class="ml-1 text-[10px] uppercase tracking-wide text-slate-400" x-text="payHeadCode(line.pay_head_id)" x-show="payHeadCode(line.pay_head_id)"></span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="rounded px-1.5 py-0.5 text-[11px] font-medium"
                                          :class="payHeadType(line.pay_head_id) === 'deduction' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                                          x-text="payHeadType(line.pay_head_id) === 'deduction' ? 'Deduction' : 'Earning'"></span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.01" min="0"
                                           class="w-36 rounded-lg border-gray-300 text-sm text-right focus:border-indigo-500 focus:ring-indigo-500"
                                           :name="`lines[${index}][amount]`"
                                           x-model="line.amount">
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <button type="button" class="text-xs text-red-600 hover:underline"
                                            x-show="!isSystemHead(line.pay_head_id)"
                                            @click="removeLine(index)">Remove</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-50 text-sm">
                        <tr>
                            <td class="px-4 py-2 font-medium text-slate-700" colspan="2">Gross earnings</td>
                            <td class="px-4 py-2 text-right font-semibold tabular-nums" x-text="formatMoney(grossEarnings())"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-medium text-slate-700" colspan="2">Total deductions</td>
                            <td class="px-4 py-2 text-right tabular-nums" x-text="formatMoney(totalDeductions())"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 font-semibold text-slate-900" colspan="2">Net</td>
                            <td class="px-4 py-2 text-right font-bold tabular-nums text-indigo-700" x-text="formatMoney(netAmount())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-100" x-show="availablePayHeads.length > 0">
                <label class="block text-xs font-medium text-slate-600 mb-1">Add pay head</label>
                <div class="flex flex-wrap items-center gap-2">
                    <select x-model="pendingPayHeadId" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select…</option>
                        <template x-for="head in availablePayHeads" :key="head.id">
                            <option :value="head.id" x-text="head.name + (head.code ? ' (' + head.code + ')' : '')"></option>
                        </template>
                    </select>
                    <button type="button" @click="addLine()" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">Add</button>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-ui.button type="submit" variant="primary">Save Structure</x-ui.button>
            <x-ui.button variant="secondary" :href="route('hrms.salary-structures.index')">Cancel</x-ui.button>
        </div>
    </form>
</x-ui.card>

@push('scripts')
<script>
function salaryStructureForm(config) {
    return {
        lines: config.lines || [],
        payHeads: config.payHeads || [],
        pendingPayHeadId: '',
        get availablePayHeads() {
            const used = this.lines.map(l => Number(l.pay_head_id));
            return this.payHeads.filter(h => !used.includes(Number(h.id)));
        },
        payHead(id) {
            return this.payHeads.find(h => Number(h.id) === Number(id));
        },
        payHeadLabel(id) {
            return this.payHead(id)?.name || '—';
        },
        payHeadCode(id) {
            return this.payHead(id)?.code || '';
        },
        payHeadType(id) {
            return this.payHead(id)?.type || 'earning';
        },
        isSystemHead(id) {
            return !!this.payHead(id)?.is_system;
        },
        addLine() {
            const id = Number(this.pendingPayHeadId);
            if (!id) return;
            if (this.lines.some(l => Number(l.pay_head_id) === id)) return;
            this.lines.push({ pay_head_id: id, amount: '0.00' });
            this.pendingPayHeadId = '';
        },
        removeLine(index) {
            const line = this.lines[index];
            if (line && this.isSystemHead(line.pay_head_id)) return;
            this.lines.splice(index, 1);
        },
        sumBy(predicate) {
            return this.lines.reduce((sum, line) => {
                const head = this.payHead(line.pay_head_id);
                if (!head || !predicate(head)) return sum;
                return sum + (parseFloat(line.amount) || 0);
            }, 0);
        },
        grossEarnings() {
            return this.sumBy(h => h.type === 'earning');
        },
        totalDeductions() {
            return this.sumBy(h => h.type === 'deduction');
        },
        netAmount() {
            return this.grossEarnings() - this.totalDeductions();
        },
        formatMoney(n) {
            return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
}
</script>
@endpush
@endsection
