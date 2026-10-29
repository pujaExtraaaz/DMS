@extends('layouts.dms')

@php
    $isEdit = isset($salesTarget) && $salesTarget->exists;
    $target = $isEdit ? $salesTarget : ($copyTarget ?? new \App\Domains\Target\Models\SalesTarget());
    $title = $isEdit ? 'Revise Target: ' . $target->name : ($copyTarget ? 'Copy Target to New Period' : 'Assign Sales Target');
@endphp

@section('title', $title)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('sales-targets.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1">
                    &larr; Back to Targets
                </a>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight mt-1">{{ $title }}</h1>
            <p class="text-xs sm:text-sm text-slate-500">Configure target thresholds, owner attribution, and sales criteria.</p>
        </div>

        @if($isEdit)
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                    Revision #{{ $target->revision_number }}
                </span>
            </div>
        @endif
    </div>

    {{-- Form --}}
    <form method="POST"
          action="{{ $isEdit ? route('sales-targets.update', $target) : route('sales-targets.store') }}"
          class="space-y-6"
          x-data="salesTargetForm({
              ownerType: '{{ old('owner_type', $target->owner_type ?? request('owner_type', 'party')) }}',
              targetType: '{{ old('target_type', $target->target_type ?? 'value') }}',
              periodType: '{{ old('period_type', $target->period_type ?? 'monthly') }}',
              startsOn: '{{ old('starts_on', optional($target->starts_on)->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d')) }}',
              endsOn: '{{ old('ends_on', optional($target->ends_on)->format('Y-m-d') ?? now()->endOfMonth()->format('Y-m-d')) }}',
              items: {{ json_encode(old('items', $target->items->map(fn($i) => [
                  'target_type' => $i->target_type,
                  'brand_id' => $i->brand_id,
                  'product_id' => $i->product_id,
                  'sku' => $i->sku,
                  'category_id' => $i->category_id,
                  'sub_category_id' => $i->sub_category_id,
                  'target_amount' => $i->target_amount,
                  'target_quantity' => $i->target_quantity,
                  'notes' => $i->notes
              ])->toArray() ?? [])) }}
          })">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Section 1: Basic Target Details --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
                1. Target Identity &amp; Type
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Target Name --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Target Name / Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $target->name) }}"
                           placeholder="e.g. Q3 Sony Pro Audio Growth Target"
                           required
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-rose-300 text-rose-900 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Target Type --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Target Type <span class="text-rose-500">*</span>
                    </label>
                    <select name="target_type"
                            x-model="targetType"
                            required
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="value">Value-Wise Target (Overall Sales ₹)</option>
                        <option value="product">Product-Wise Target (Quantity &amp; Value)</option>
                        <option value="sku">Item / SKU-Wise Target</option>
                        <option value="brand">Brand-Wise Target (e.g. Sony, Bose)</option>
                        <option value="category">Category / Subcategory-Wise Target</option>
                        <option value="combined">Combined Value + Product/Brand Target</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Determines how matching invoice lines are aggregated.</p>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Status <span class="text-rose-500">*</span>
                    </label>
                    <select name="status"
                            required
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="active" @selected(old('status', $target->status ?? 'active') === 'active')>Active (Tracking Invoices)</option>
                        <option value="pending_approval" @selected(old('status', $target->status) === 'pending_approval')>Pending Approval</option>
                        <option value="draft" @selected(old('status', $target->status) === 'draft')>Draft</option>
                        <option value="completed" @selected(old('status', $target->status) === 'completed')>Completed</option>
                        <option value="cancelled" @selected(old('status', $target->status) === 'cancelled')>Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Section 2: Owner Attribution --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
                2. Target Owner Attribution
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Owner Type --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Assign Target To <span class="text-rose-500">*</span>
                    </label>
                    <select name="owner_type"
                            x-model="ownerType"
                            required
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="party">Party / Customer</option>
                        <option value="individual">Individual Salesman</option>
                        <option value="team">Sales Team</option>
                        <option value="branch">Branch</option>
                    </select>
                </div>

                {{-- Party Dropdown --}}
                <div x-show="ownerType === 'party'" x-cloak>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Select Party / Customer <span class="text-rose-500">*</span>
                    </label>
                    <select name="customer_id"
                            :required="ownerType === 'party'"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('customer_id') border-rose-300 text-rose-900 @enderror">
                        <option value="">Choose Party...</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}" @selected(old('customer_id', $target->customer_id) == $party->id)>
                                {{ $party->name }} {{ $party->code ? '('.$party->code.')' : '' }} {{ $party->trade_name ? '- '.$party->trade_name : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Individual Salesman Dropdown --}}
                <div x-show="ownerType === 'individual'" x-cloak>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Select Salesman <span class="text-rose-500">*</span>
                    </label>
                    <select name="salesperson_id"
                            :required="ownerType === 'individual'"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Choose Salesman...</option>
                        @foreach($salespeople as $sp)
                            <option value="{{ $sp->id }}" @selected(old('salesperson_id', $target->salesperson_id) == $sp->id)>
                                {{ $sp->name }} ({{ $sp->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Team Dropdown --}}
                <div x-show="ownerType === 'team'" x-cloak>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Select Sales Team <span class="text-rose-500">*</span>
                    </label>
                    <select name="sales_team_id"
                            :required="ownerType === 'team'"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Choose Team...</option>
                        @foreach($teams as $t)
                            <option value="{{ $t->id }}" @selected(old('sales_team_id', $target->sales_team_id) == $t->id)>
                                {{ $t->name }} ({{ $t->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Branch Dropdown --}}
                <div x-show="ownerType === 'branch'" x-cloak>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Select Branch <span class="text-rose-500">*</span>
                    </label>
                    <select name="branch_id"
                            :required="ownerType === 'branch'"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Choose Branch...</option>
                        @foreach($branches as $br)
                            <option value="{{ $br->id }}" @selected(old('branch_id', $target->branch_id) == $br->id)>
                                {{ $br->name }} ({{ $br->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Section 3: Target Period & Dates --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
                3. Period &amp; Timeframe
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                {{-- Period Type --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Period Frequency <span class="text-rose-500">*</span>
                    </label>
                    <select name="period_type"
                            x-model="periodType"
                            @change="onPeriodTypeChange()"
                            required
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="half_yearly">Half-Yearly</option>
                        <option value="annual">Annually</option>
                        <option value="custom">Custom Date Range</option>
                    </select>
                </div>

                {{-- Financial Year --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Financial Year
                    </label>
                    <select name="financial_year_id"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Current FY</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" @selected(old('financial_year_id', $target->financial_year_id ?? $currentFy?->id) == $fy->id)>
                                {{ $fy->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Start Date --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Start Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date"
                           name="starts_on"
                           x-model="startsOn"
                           required
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- End Date --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        End Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date"
                           name="ends_on"
                           x-model="endsOn"
                           required
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Section 4: Target Values & Master Criteria --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
                4. Target Values &amp; Scope
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Target Sales Amount (INR) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Target Sales Value (₹) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative rounded-lg shadow-xs">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-slate-500 text-sm font-bold">₹</span>
                        </div>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="target_amount"
                               value="{{ old('target_amount', $target->target_amount ?? 0) }}"
                               required
                               placeholder="1000000.00"
                               class="block w-full rounded-lg border-gray-300 pl-7 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Net invoiced sales value threshold in INR.</p>
                </div>

                {{-- Target Quantity (Units) --}}
                <div x-show="targetType !== 'value'">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Target Quantity (Units)
                    </label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           name="target_quantity"
                           value="{{ old('target_quantity', $target->target_quantity ?? 0) }}"
                           placeholder="e.g. 50"
                           class="block w-full rounded-lg border-gray-300 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="text-[11px] text-slate-400 mt-1">Required number of units/pieces sold.</p>
                </div>
            </div>

            {{-- Conditional Master Criteria Selection (Brand, Product, SKU, Category) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pt-2 border-t border-slate-100"
                 x-show="targetType !== 'value' && targetType !== 'combined'">
                
                {{-- Brand Selector --}}
                <div x-show="targetType === 'brand' || targetType === 'product' || targetType === 'sku'">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Brand</label>
                    <select name="brand_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Any / All Brands</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" @selected(old('brand_id', $target->brand_id) == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Product Selector --}}
                <div x-show="targetType === 'product' || targetType === 'sku'">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Product</label>
                    <select name="product_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Any / All Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id', $target->product_id) == $p->id)>{{ $p->name }} (SKU: {{ $p->sku ?? '—' }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- SKU Selector --}}
                <div x-show="targetType === 'sku'">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">SKU / Code</label>
                    <input type="text"
                           name="sku"
                           value="{{ old('sku', $target->sku) }}"
                           placeholder="Enter SKU..."
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Category Selector --}}
                <div x-show="targetType === 'category'">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Category</label>
                    <select name="category_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Any Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $target->category_id) == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Subcategory Selector --}}
                <div x-show="targetType === 'category'">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Subcategory</label>
                    <select name="sub_category_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Any Subcategory</option>
                        @foreach($subCategories as $sub)
                            <option value="{{ $sub->id }}" @selected(old('sub_category_id', $target->sub_category_id) == $sub->id)>{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Multi-Criteria Items (For Combined Target or Multiple Criteria Rows) --}}
            <div x-show="targetType === 'combined'" x-cloak class="pt-4 border-t border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Multi-Criteria Item Lines</h3>
                        <p class="text-[11px] text-slate-500">Add specific product, brand, or category lines to this combined target.</p>
                    </div>
                    <button type="button"
                            @click="addItemRow()"
                            class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                        + Add Criteria Line
                    </button>
                </div>

                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                        <thead class="bg-slate-50 text-slate-600 font-semibold">
                            <tr>
                                <th class="px-3 py-2">Criteria Type</th>
                                <th class="px-3 py-2">Brand</th>
                                <th class="px-3 py-2">Product</th>
                                <th class="px-3 py-2">Target Qty</th>
                                <th class="px-3 py-2">Target Value (₹)</th>
                                <th class="px-3 py-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(row, idx) in items" :key="idx">
                                <tr>
                                    <td class="px-3 py-2">
                                        <select :name="`items[${idx}][target_type]`" x-model="row.target_type" class="rounded border-gray-300 text-xs">
                                            <option value="product">Product</option>
                                            <option value="brand">Brand</option>
                                            <option value="category">Category</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <select :name="`items[${idx}][brand_id]`" x-model="row.brand_id" class="rounded border-gray-300 text-xs">
                                            <option value="">Any Brand</option>
                                            @foreach($brands as $b)
                                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <select :name="`items[${idx}][product_id]`" x-model="row.product_id" class="rounded border-gray-300 text-xs">
                                            <option value="">Any Product</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" min="0" :name="`items[${idx}][target_quantity]`" x-model="row.target_quantity" placeholder="0" class="w-20 rounded border-gray-300 text-xs font-semibold">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" min="0" :name="`items[${idx}][target_amount]`" x-model="row.target_amount" placeholder="0.00" class="w-28 rounded border-gray-300 text-xs font-semibold">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" @click="removeItemRow(idx)" class="text-rose-600 hover:text-rose-800 text-xs font-bold">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Section 5: Notes & Revision Reason --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-100 pb-2">
                5. Notes &amp; Governance
            </h2>

            @if($isEdit)
                <div>
                    <label class="block text-xs font-semibold text-rose-700 mb-1">
                        Revision Reason <span class="text-rose-500">*</span> (Mandatory for Audit Trail)
                    </label>
                    <textarea name="revision_reason"
                              rows="2"
                              required
                              placeholder="Describe the reason for revising this target (e.g. Territory expansion, quota adjustment approved by VP Sales)..."
                              class="block w-full rounded-lg border-rose-300 text-sm focus:border-rose-500 focus:ring-rose-500">{{ old('revision_reason') }}</textarea>
                    @error('revision_reason')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Notes &amp; Remarks (Optional)
                </label>
                <textarea name="notes"
                          rows="2"
                          placeholder="Special commission rules, eligibility criteria, or internal instructions..."
                          class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $target->notes) }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('sales-targets.index') }}"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                {{ $isEdit ? 'Save Revision & Recalculate' : 'Save & Assign Target' }}
            </button>
        </div>
    </form>
</div>

<script>
function salesTargetForm(initialData) {
    return {
        ownerType: initialData.ownerType || 'party',
        targetType: initialData.targetType || 'value',
        periodType: initialData.periodType || 'monthly',
        startsOn: initialData.startsOn || '',
        endsOn: initialData.endsOn || '',
        items: initialData.items || [],

        addItemRow() {
            this.items.push({
                target_type: 'product',
                brand_id: '',
                product_id: '',
                sku: '',
                category_id: '',
                sub_category_id: '',
                target_amount: 0,
                target_quantity: 0,
                notes: ''
            });
        },

        removeItemRow(idx) {
            this.items.splice(idx, 1);
        },

        onPeriodTypeChange() {
            const now = new Date();
            const y = now.getFullYear();
            const m = now.getMonth(); // 0-based

            if (this.periodType === 'monthly') {
                const firstDay = new Date(y, m, 1);
                const lastDay = new Date(y, m + 1, 0);
                this.startsOn = firstDay.toISOString().split('T')[0];
                this.endsOn = lastDay.toISOString().split('T')[0];
            } else if (this.periodType === 'quarterly') {
                const q = Math.floor(m / 3);
                const firstDay = new Date(y, q * 3, 1);
                const lastDay = new Date(y, (q + 1) * 3, 0);
                this.startsOn = firstDay.toISOString().split('T')[0];
                this.endsOn = lastDay.toISOString().split('T')[0];
            } else if (this.periodType === 'annual') {
                // Indian FY starts Apr 1
                const fyYear = m >= 3 ? y : y - 1;
                this.startsOn = `${fyYear}-04-01`;
                this.endsOn = `${fyYear + 1}-03-31`;
            }
        }
    };
}
</script>
@endsection

