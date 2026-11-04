@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Voucher Numbering' : 'Create Voucher Numbering')
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
          x-data="{
              prefix: '{{ old('prefix', $item->prefix ?? 'AVITPO') }}',
              suffix: '{{ old('suffix', $item->suffix ?? '') }}',
              includeFy: {{ old('include_financial_year', $item->include_financial_year ?? true) ? 'true' : 'false' }},
              fyStyle: '{{ old('financial_year_style', $item->financial_year_style ?? 'yy_yy') }}',
              separator: '{{ old('separator', $item->separator ?? '-') }}',
              padding: {{ (int) old('padding', $item->padding ?? 3) }},
              method: '{{ old('numbering_method', $item->numbering_method ?? 'automatic') }}',
              fySample() {
                  if (this.fyStyle === 'YYYY-YY') return '2026-27';
                  if (this.fyStyle === 'name') return '2026-27';
                  return '2627';
              },
              preview() {
                  const sep = this.separator === '' ? '-' : this.separator;
                  const parts = [];
                  const p = (this.prefix || '').toString().trim().toUpperCase();
                  if (p) parts.push(p);
                  if (this.includeFy) parts.push(this.fySample());
                  const pad = Math.max(1, Math.min(8, parseInt(this.padding, 10) || 3));
                  parts.push(String(1).padStart(pad, '0'));
                  return parts.join(sep) + (this.suffix || '');
              }
          }">
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
                <select name="numbering_method" x-model="method" class="block w-full rounded-lg border-gray-300 text-sm" required>
                    <option value="automatic">Automatic</option>
                    <option value="manual">Manual</option>
                </select>
                <p class="mt-1 text-xs text-slate-500" x-text="method === 'manual' ? 'Users enter the voucher number on each document.' : 'System generates the next number using the format below.'"></p>
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
                <select name="financial_year_style" x-model="fyStyle" class="block w-full rounded-lg border-gray-300 text-sm" required>
                    <option value="yy_yy">2627 (yy + yy)</option>
                    <option value="YYYY-YY">2026-27</option>
                    <option value="name">Use FY name</option>
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

        <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Preview</p>
            <p class="mt-1 font-mono text-lg font-semibold text-indigo-900" x-text="preview()"></p>
            <p class="mt-1 text-xs text-indigo-700">Example for Purchase Orders: AVITPO-2627-001</p>
        </div>

        <x-ui.button type="submit" variant="primary">Save Series</x-ui.button>
    </form>
</x-ui.card>
@endsection
