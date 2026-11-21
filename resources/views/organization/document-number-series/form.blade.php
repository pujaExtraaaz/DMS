@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Voucher Numbering' : 'Create Voucher Numbering')
@php
    $fyStyleValue = old('financial_year_style', $item->financial_year_style ?? 'yy_yy');
@endphp
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Voucher Numbering' : 'Create Voucher Numbering'">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('organization.document-number-series.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST"
          action="{{ $item->exists ? route('organization.document-number-series.update', $item) : route('organization.document-number-series.store') }}"
          class="space-y-5 max-w-3xl"
          x-data="documentNumberSeriesForm(@js([
              'prefix' => old('prefix', $item->prefix ?? 'AVITPO'),
              'suffix' => old('suffix', $item->suffix ?? ''),
              'includeFy' => (bool) old('include_financial_year', $item->include_financial_year ?? true),
              'fyStyle' => $fyStyleValue,
              'separator' => old('separator', $item->separator ?? '-'),
              'padding' => (int) old('padding', $item->padding ?? 4),
              'startingNumber' => (int) old('starting_number', $item->starting_number ?? 1),
              'prefixZero' => filter_var(old('prefix_zero', $item->prefix_zero ?? true), FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
              'method' => old('numbering_method', $item->numbering_method ?? 'automatic'),
              'restartRules' => old('restart_rules', $item->restart_rules ?? []),
              'prefixRules' => old('prefix_rules', $item->prefix_rules ?? []),
              'suffixRules' => old('suffix_rules', $item->suffix_rules ?? []),
          ]))">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui.select name="document_type" label="Document Type" required>
                @foreach($documentTypes as $code => $label)
                    <option value="{{ $code }}" @selected(old('document_type', $item->document_type) == $code)>{{ $label }} ({{ $code }})</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="company_id" label="Company / Applicability">
                <option value="">All companies (default)</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" @selected(old('company_id', $item->company_id) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input name="name" label="Series Name" :value="old('name', $item->name)" placeholder="Purchase Order" />

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Numbering Method *</label>
                <select name="numbering_method" x-model="method" data-no-search class="block w-full rounded-lg border-gray-300 text-sm" required>
                    <option value="automatic" @selected(old('numbering_method', $item->numbering_method ?? 'automatic') === 'automatic')>Automatic</option>
                    <option value="automatic_manual_override" @selected(old('numbering_method', $item->numbering_method ?? 'automatic') === 'automatic_manual_override')>Automatic (Manual Override)</option>
                    <option value="manual" @selected(old('numbering_method', $item->numbering_method ?? 'automatic') === 'manual')>Manual</option>
                    <option value="multi_user_auto" @selected(old('numbering_method', $item->numbering_method ?? 'automatic') === 'multi_user_auto')>Multi-user Auto</option>
                </select>
                <p class="mt-1 text-xs text-slate-500" x-text="methodHelp()"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Prefix</label>
                <input type="text" name="prefix" x-model="prefix" maxlength="40" class="block w-full rounded-lg border-gray-300 text-sm uppercase" placeholder="AVITPO">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Suffix</label>
                <input type="text" name="suffix" x-model="suffix" maxlength="40" class="block w-full rounded-lg border-gray-300 text-sm" placeholder="Optional">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Separator</label>
                <input type="text" name="separator" x-model="separator" maxlength="5" class="block w-full rounded-lg border-gray-300 text-sm" placeholder="-">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Number Padding *</label>
                <input type="number" name="padding" x-model.number="padding" min="1" max="8" class="block w-full rounded-lg border-gray-300 text-sm" required>
                <p class="mt-1 text-xs text-slate-500">Example: 3 → 001</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Financial Year Style *</label>
                {{-- data-no-search: searchable select desyncs Alpine x-model (trigger shows yy_yy while value is YYYY-YY). --}}
                <select name="financial_year_style" x-model="fyStyle" data-no-search class="block w-full rounded-lg border-gray-300 text-sm" required>
                    <option value="yy_yy" @selected($fyStyleValue === 'yy_yy')>2627 (yy + yy)</option>
                    <option value="YYYY-YY" @selected($fyStyleValue === 'YYYY-YY')>2026-27</option>
                    <option value="name" @selected($fyStyleValue === 'name')>Use FY name</option>
                </select>
            </div>
        </div>

        <div class="flex flex-wrap gap-6">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="include_financial_year" value="1" x-model="includeFy" class="rounded border-gray-300 text-indigo-600">
                Include financial period in number
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="restart_each_fy" value="1" @checked(old('restart_each_fy', $item->restart_each_fy ?? true)) class="rounded border-gray-300 text-indigo-600">
                Restart numbering each financial year
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600">
                Active
            </label>
        </div>

        <!-- Date-Wise Numbering Configuration Section -->
        <div class="mt-8 border-t border-slate-200 pt-6 space-y-6">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Date-Wise Numbering Configuration</h3>
                <p class="text-xs text-slate-500 mt-0.5">Configure starting numbers, zero-padding, reset frequency, and effective date rules for prefixes, suffixes, and sequences.</p>
                @error('restart_rules')
                    <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
                @error('prefix_rules')
                    <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
                @error('suffix_rules')
                    <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- A. General Numbering Settings -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-sm font-semibold text-slate-800 mb-3">A. General Numbering Settings</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Starting Number *</label>
                        <input type="number" name="starting_number" x-model.number="startingNumber" min="1" class="block w-full rounded-lg border-gray-300 text-sm" required>
                        <p class="mt-1 text-xs text-slate-500">Default starting sequence (e.g. 1)</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Width of Numerical Part *</label>
                        <input type="number" name="padding" x-model.number="padding" min="1" max="8" class="block w-full rounded-lg border-gray-300 text-sm" required>
                        <p class="mt-1 text-xs text-slate-500">Number of digits (e.g. 4 → 0001)</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Prefix with Zero *</label>
                        <select name="prefix_zero" x-model="prefixZero" data-no-search class="block w-full rounded-lg border-gray-300 text-sm">
                            {{-- Use 1/0 — Laravel boolean validation rejects the strings "true"/"false". --}}
                            <option value="1">Yes (e.g. 0001)</option>
                            <option value="0">No (e.g. 1)</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Whether numerical part is zero-padded</p>
                    </div>
                </div>
            </div>

            <!-- B. Restart Numbering Configuration -->
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800">B. Restart Numbering Configuration</h4>
                        <p class="text-xs text-slate-500">Periodicity auto-fills Applicable From/To as an n-day window starting today.</p>
                    </div>
                    <button type="button" @click="addRestartRule()" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">+ Add Period</button>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead>
                            <tr class="text-left text-slate-500 uppercase tracking-wider">
                                <th class="pb-2">Applicable From *</th>
                                <th class="pb-2">Applicable To</th>
                                <th class="pb-2">Starting Number *</th>
                                <th class="pb-2">Periodicity *</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(rule, idx) in restartRules" :key="idx">
                                <tr>
                                    <td class="py-2 pr-2">
                                        <input type="date" :name="`restart_rules[${idx}][applicable_from]`" x-model="rule.applicable_from" class="w-full rounded-md border-gray-300 text-xs" required>
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="date" :name="`restart_rules[${idx}][applicable_to]`" x-model="rule.applicable_to" class="w-full rounded-md border-gray-300 text-xs">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" min="1" :name="`restart_rules[${idx}][starting_number]`" x-model.number="rule.starting_number" class="w-24 rounded-md border-gray-300 text-xs" required>
                                    </td>
                                    <td class="py-2 pr-2">
                                        <select :name="`restart_rules[${idx}][periodicity]`"
                                                x-model="rule.periodicity"
                                                @change="applyPeriodicityDates(rule)"
                                                data-no-search
                                                class="w-full rounded-md border-gray-300 text-xs"
                                                required>
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="yearly">Yearly</option>
                                            <option value="never">Never</option>
                                        </select>
                                    </td>
                                    <td class="py-2 text-right">
                                        <button type="button" @click="removeRestartRule(idx)" class="text-red-600 hover:text-red-800 font-medium">Remove</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="restartRules.length === 0">
                                <td colspan="5" class="py-4 text-center text-slate-400">No date-wise restart periods configured. Series will restart based on default settings.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- C. Prefix Details -->
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800">C. Prefix Details</h4>
                        <p class="text-xs text-slate-500">Configure different prefixes for different date ranges (e.g. AVIT26-27, AVIT27-28).</p>
                    </div>
                    <button type="button" @click="addPrefixRule()" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">+ Add Prefix</button>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead>
                            <tr class="text-left text-slate-500 uppercase tracking-wider">
                                <th class="pb-2">Applicable From *</th>
                                <th class="pb-2">Applicable To</th>
                                <th class="pb-2">Prefix Particulars *</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(rule, idx) in prefixRules" :key="idx">
                                <tr>
                                    <td class="py-2 pr-2">
                                        <input type="date" :name="`prefix_rules[${idx}][applicable_from]`" x-model="rule.applicable_from" class="w-full rounded-md border-gray-300 text-xs" required>
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="date" :name="`prefix_rules[${idx}][applicable_to]`" x-model="rule.applicable_to" class="w-full rounded-md border-gray-300 text-xs">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="text" maxlength="40" :name="`prefix_rules[${idx}][prefix]`" x-model="rule.prefix" placeholder="AVIT26-27" class="w-full rounded-md border-gray-300 text-xs uppercase" required>
                                    </td>
                                    <td class="py-2 text-right">
                                        <button type="button" @click="removePrefixRule(idx)" class="text-red-600 hover:text-red-800 font-medium">Remove</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="prefixRules.length === 0">
                                <td colspan="4" class="py-4 text-center text-slate-400">No date-wise prefixes configured. The general prefix above will be used.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- D. Suffix Details -->
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800">D. Suffix Details</h4>
                        <p class="text-xs text-slate-500">Configure different suffixes for different date ranges.</p>
                    </div>
                    <button type="button" @click="addSuffixRule()" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">+ Add Suffix</button>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead>
                            <tr class="text-left text-slate-500 uppercase tracking-wider">
                                <th class="pb-2">Applicable From *</th>
                                <th class="pb-2">Applicable To</th>
                                <th class="pb-2">Suffix Particulars *</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(rule, idx) in suffixRules" :key="idx">
                                <tr>
                                    <td class="py-2 pr-2">
                                        <input type="date" :name="`suffix_rules[${idx}][applicable_from]`" x-model="rule.applicable_from" class="w-full rounded-md border-gray-300 text-xs" required>
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="date" :name="`suffix_rules[${idx}][applicable_to]`" x-model="rule.applicable_to" class="w-full rounded-md border-gray-300 text-xs">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="text" maxlength="40" :name="`suffix_rules[${idx}][suffix]`" x-model="rule.suffix" placeholder="Optional suffix" class="w-full rounded-md border-gray-300 text-xs" required>
                                    </td>
                                    <td class="py-2 text-right">
                                        <button type="button" @click="removeSuffixRule(idx)" class="text-red-600 hover:text-red-800 font-medium">Remove</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="suffixRules.length === 0">
                                <td colspan="4" class="py-4 text-center text-slate-400">No date-wise suffixes configured. The general suffix above will be used.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Preview</p>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                      :class="{
                          'bg-sky-100 text-sky-800': method === 'automatic',
                          'bg-indigo-100 text-indigo-800': method === 'automatic_manual_override',
                          'bg-amber-100 text-amber-800': method === 'manual',
                          'bg-emerald-100 text-emerald-800': method === 'multi_user_auto'
                      }"
                      x-text="method === 'manual' ? 'Manual Entry Required' : (method === 'automatic_manual_override' ? 'Auto (Manual Override)' : (method === 'multi_user_auto' ? 'Multi-user Concurrency Safe' : 'Automatic'))">
                </span>
            </div>
            <p class="mt-1 font-mono text-lg font-semibold text-indigo-900" x-text="method === 'manual' ? '[MANUAL VOUCHER NUMBER]' : preview()"></p>
            <p class="mt-1 text-xs text-indigo-700" x-show="method !== 'manual'">Live preview updates when prefix / FY style / padding change.</p>
            <p class="mt-1 text-xs text-amber-700" x-show="method === 'manual'">Users must enter the voucher number on each transaction form.</p>
        </div>

        <x-ui.button type="submit" variant="primary">Save Series</x-ui.button>
    </form>
</x-ui.card>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('documentNumberSeriesForm', (initial = {}) => ({
        prefix: initial.prefix ?? 'AVITPO',
        suffix: initial.suffix ?? '',
        includeFy: initial.includeFy ?? true,
        fyStyle: initial.fyStyle ?? 'yy_yy',
        separator: initial.separator ?? '-',
        padding: Number(initial.padding ?? 4),
        startingNumber: Number(initial.startingNumber ?? 1),
        prefixZero: String(initial.prefixZero ?? '1'),
        method: initial.method ?? 'automatic',
        restartRules: Array.isArray(initial.restartRules) ? initial.restartRules : [],
        prefixRules: Array.isArray(initial.prefixRules) ? initial.prefixRules : [],
        suffixRules: Array.isArray(initial.suffixRules) ? initial.suffixRules : [],
        methodDescriptions: {
            automatic: 'Generate voucher numbers automatically using the configured prefix, separator and sequence.',
            automatic_manual_override: 'Generate numbers automatically by default, but allow authorized users to enter or modify the voucher number manually.',
            manual: 'Require users to enter the voucher number themselves on each document.',
            multi_user_auto: 'Generate unique voucher numbers automatically for multiple users creating vouchers simultaneously with database-level concurrency protection.'
        },

        methodHelp() {
            return this.methodDescriptions[this.method] || '';
        },

        formatDate(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },

        addDays(date, days) {
            const next = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            next.setDate(next.getDate() + days);
            return next;
        },

        /**
         * Map periodicity → n-day window from today (inclusive).
         * daily=1, weekly=7, monthly=30, yearly=365, never=open-ended.
         */
        applyPeriodicityDates(rule) {
            if (!rule) return;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const period = String(rule.periodicity || 'yearly').toLowerCase();
            const daysByPeriod = {
                daily: 1,
                weekly: 7,
                monthly: 30,
                yearly: 365,
            };

            rule.applicable_from = this.formatDate(today);

            if (period === 'never') {
                rule.applicable_to = '';
                return;
            }

            const days = daysByPeriod[period] ?? 365;
            rule.applicable_to = this.formatDate(this.addDays(today, days - 1));
        },

        addRestartRule() {
            const rule = {
                applicable_from: '',
                applicable_to: '',
                starting_number: 1,
                periodicity: 'yearly',
            };
            this.applyPeriodicityDates(rule);
            this.restartRules.push(rule);
        },

        removeRestartRule(idx) {
            this.restartRules.splice(idx, 1);
        },

        addPrefixRule() {
            this.prefixRules.push({ applicable_from: '', applicable_to: '', prefix: '' });
        },

        removePrefixRule(idx) {
            this.prefixRules.splice(idx, 1);
        },

        addSuffixRule() {
            this.suffixRules.push({ applicable_from: '', applicable_to: '', suffix: '' });
        },

        removeSuffixRule(idx) {
            this.suffixRules.splice(idx, 1);
        },

        fySample() {
            if (this.fyStyle === 'YYYY-YY') return '2026-27';
            if (this.fyStyle === 'name') return '2026-27';
            return '2627';
        },

        preview() {
            const sep = this.separator === '' ? '-' : this.separator;
            const parts = [];
            let p = (this.prefix || '').toString().trim().toUpperCase();
            if (this.prefixRules && this.prefixRules.length > 0 && this.prefixRules[0].prefix) {
                p = this.prefixRules[0].prefix.toString().trim().toUpperCase();
            }
            if (p) parts.push(p);

            const fy = this.fySample();
            const cleanP = p.replace(/[-/]/g, '');
            const cleanFy = fy.replace(/[-/]/g, '');
            if (this.includeFy && (!p || !cleanP.includes(cleanFy))) {
                parts.push(fy);
            }

            const pad = Math.max(1, Math.min(8, parseInt(this.padding, 10) || 4));
            let startNo = parseInt(this.startingNumber, 10) || 1;
            if (this.restartRules && this.restartRules.length > 0 && this.restartRules[0].starting_number) {
                startNo = parseInt(this.restartRules[0].starting_number, 10) || startNo;
            }

            const padZeros = this.prefixZero === true || this.prefixZero === 1 || this.prefixZero === '1';
            if (padZeros) {
                parts.push(String(startNo).padStart(pad, '0'));
            } else {
                parts.push(String(startNo));
            }

            let s = (this.suffix || '').toString();
            if (this.suffixRules && this.suffixRules.length > 0 && this.suffixRules[0].suffix) {
                s = this.suffixRules[0].suffix.toString();
            }

            return parts.join(sep) + s;
        },
    }));
});
</script>
@endpush
