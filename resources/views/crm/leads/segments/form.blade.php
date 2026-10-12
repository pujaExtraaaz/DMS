@extends('layouts.dms')
@section('title', $segment->exists ? 'Edit Lead Segment: ' . $segment->name : 'Create Lead Segment')
@section('content')
<div x-data="segmentFormManager({{ json_encode($segment->criteria ?? []) }})" class="space-y-6 max-w-7xl mx-auto">
    <x-ui.page-header :title="$segment->exists ? 'Edit Lead Segment: ' . $segment->name : 'Create Targeted Lead Segment'">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('crm.leads.segments.index')">
                <svg class="w-4 h-4 mr-1.5 -ml-0.5 inline-block text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Segments
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 p-4 border border-red-200">
            <h4 class="text-sm font-semibold text-red-800">Please correct the following errors:</h4>
            <ul class="mt-2 list-disc list-inside text-xs text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $segment->exists ? route('crm.leads.segments.update', $segment) : route('crm.leads.segments.store') }}"
          class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @if($segment->exists)
            @method('PUT')
        @endif

        <!-- Left 2 Cols: Segment Definition & Rules Builder -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Details -->
            <x-ui.card title="Segment Identity">
                <div class="space-y-4">
                    <x-ui.input
                        name="name"
                        label="Segment Name"
                        :value="old('name', $segment->name)"
                        required
                        placeholder="e.g. Maharashtra Manufacturers - Inactive 90 Days"
                    />

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Description (Optional)</label>
                        <textarea
                            name="description"
                            rows="2"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="State the campaign objective for this audience..."
                        >{{ old('description', $segment->description) }}</textarea>
                    </div>
                </div>
            </x-ui.card>

            <!-- Criteria Section 1: Industry & Region -->
            <x-ui.card>
                <div class="flex items-center gap-2 pb-3 mb-4 border-b border-slate-100">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-100 text-blue-700 text-xs font-bold">1</span>
                    <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider">Industry &amp; Regional Filters</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Industry / Business Type</label>
                        <input
                            type="text"
                            name="criteria[industry]"
                            list="segment-industry-presets"
                            x-model="criteria.industry"
                            @input.debounce.400ms="fetchPreview()"
                            placeholder="e.g. Manufacturing, DEALER, Solar"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                        <datalist id="segment-industry-presets">
                            @foreach($options['industries'] as $ind)
                                <option value="{{ $ind }}">
                            @endforeach
                            <option value="Manufacturing">
                            <option value="Logistics">
                            <option value="Solar">
                            <option value="Wholesale">
                            <option value="Retailer">
                        </datalist>
                        <p class="text-[11px] text-slate-400 mt-1">Matches lead industry, tags, or company type.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">State / Territory</label>
                        <select
                            name="criteria[state]"
                            x-model="criteria.state"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Any State</option>
                            @foreach($options['states'] as $st)
                                <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">City</label>
                        <input
                            type="text"
                            name="criteria[city]"
                            list="segment-city-presets"
                            x-model="criteria.city"
                            @input.debounce.400ms="fetchPreview()"
                            placeholder="e.g. Mumbai, Pune, Delhi"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                        <datalist id="segment-city-presets">
                            @foreach($options['cities'] as $ct)
                                <option value="{{ $ct }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Branch</label>
                        <select
                            name="criteria[branch_id]"
                            x-model="criteria.branch_id"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Branches</option>
                            @foreach($options['branches'] as $br)
                                <option value="{{ $br->id }}">{{ $br->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-ui.card>

            <!-- Criteria Section 2: CRM Classification -->
            <x-ui.card>
                <div class="flex items-center gap-2 pb-3 mb-4 border-b border-slate-100">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 text-xs font-bold">2</span>
                    <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider">CRM Lead Classification</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Lead Category</label>
                        <select
                            name="criteria[category]"
                            x-model="criteria.category"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Categories</option>
                            <option value="prospect">Prospect</option>
                            <option value="customer">Customer</option>
                            <option value="partner">Partner</option>
                            <option value="uncategorized">Uncategorized</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Lead Status</label>
                        <select
                            name="criteria[status]"
                            x-model="criteria.status"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Statuses</option>
                            @foreach($options['statuses'] as $st)
                                <option value="{{ $st }}">{{ $st === 'follow_up' ? 'Follow-up' : ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Priority</label>
                        <select
                            name="criteria[priority]"
                            x-model="criteria.priority"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Priorities</option>
                            @foreach($options['priorities'] as $pr)
                                <option value="{{ $pr }}">{{ ucfirst($pr) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Assigned Salesperson</label>
                        <select
                            name="criteria[assigned_to]"
                            x-model="criteria.assigned_to"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Any Salesperson</option>
                            @foreach($options['users'] as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Lead Source</label>
                        <select
                            name="criteria[lead_source_id]"
                            x-model="criteria.lead_source_id"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Any Source</option>
                            @foreach($options['sources'] as $src)
                                <option value="{{ $src->id }}">{{ $src->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Lead Campaign</label>
                        <select
                            name="criteria[lead_campaign_id]"
                            x-model="criteria.lead_campaign_id"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Any Campaign</option>
                            @foreach($options['campaigns'] as $cmp)
                                <option value="{{ $cmp->id }}">{{ $cmp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-ui.card>

            <!-- Criteria Section 3: Purchase History -->
            <x-ui.card>
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700 text-xs font-bold">3</span>
                        <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider">Purchase History (Orders &amp; Invoices)</h3>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">Evaluated from actual sales transactions</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Purchase Status</label>
                        <select
                            name="criteria[purchase_status]"
                            x-model="criteria.purchase_status"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Any / Ignore Purchase Filter</option>
                            <option value="has_purchased">Has Purchased (Customer with ≥ 1 eligible sales order)</option>
                            <option value="never_purchased">Never Purchased (Linked Customer with 0 orders)</option>
                            <option value="unavailable">Purchase History Unavailable (Not linked to a Party)</option>
                            <option value="repeat_customer">Repeat Customers (≥ 2 orders placed)</option>
                            <option value="no_purchase_in_days">Inactive: No Purchase in specified days</option>
                        </select>
                    </div>

                    <div x-show="criteria.purchase_status === 'no_purchase_in_days' || criteria.last_purchase_before_days" x-cloak>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Inactive Period (Days)</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                name="criteria[no_purchase_days]"
                                x-model="criteria.no_purchase_days"
                                @input.debounce.400ms="fetchPreview()"
                                placeholder="90"
                                min="1"
                                class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <span class="text-slate-500 shrink-0">days</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">e.g. 90 days = Customers who haven't ordered in 90+ days.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Recent Purchase Within (Days)</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                name="criteria[last_purchase_within_days]"
                                x-model="criteria.last_purchase_within_days"
                                @input.debounce.400ms="fetchPreview()"
                                placeholder="e.g. 30"
                                min="1"
                                class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <span class="text-slate-500 shrink-0">days</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Minimum Orders Count</label>
                        <input
                            type="number"
                            name="criteria[min_orders]"
                            x-model="criteria.min_orders"
                            @input.debounce.400ms="fetchPreview()"
                            placeholder="e.g. 2"
                            min="1"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Min Total Spend (₹)</label>
                        <input
                            type="number"
                            name="criteria[min_total_value]"
                            x-model="criteria.min_total_value"
                            @input.debounce.400ms="fetchPreview()"
                            placeholder="e.g. 50000"
                            min="0"
                            step="500"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>
                </div>
            </x-ui.card>

            <!-- Criteria Section 4: Engagement Level -->
            <x-ui.card>
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-purple-100 text-purple-700 text-xs font-bold">4</span>
                        <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider">Engagement &amp; Activity Level</h3>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">Evaluated from follow-ups &amp; recorded activities</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Engagement Group</label>
                        <select
                            name="criteria[engagement_level]"
                            x-model="criteria.engagement_level"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Any / Ignore Engagement Filter</option>
                            <option value="high">Highly Engaged (≥3 interactions or active in last 14 days)</option>
                            <option value="medium">Moderately Engaged (active in last 30 days)</option>
                            <option value="low">Low Engagement (has history, but inactive >30 days)</option>
                            <option value="no_recent_activity">No Recent Activity (no follow-up in X days)</option>
                            <option value="unknown">No Activity Recorded (0 follow-ups/interactions)</option>
                        </select>
                    </div>

                    <div x-show="criteria.engagement_level === 'no_recent_activity' || criteria.no_activity_within_days" x-cloak>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">No Activity In (Days)</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                name="criteria[no_activity_days]"
                                x-model="criteria.no_activity_days"
                                @input.debounce.400ms="fetchPreview()"
                                placeholder="30"
                                min="1"
                                class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <span class="text-slate-500 shrink-0">days</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">e.g. 30 days = Qualified prospects with no follow-up in last 30 days.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Minimum Follow-ups Count</label>
                        <input
                            type="number"
                            name="criteria[min_followups]"
                            x-model="criteria.min_followups"
                            @input.debounce.400ms="fetchPreview()"
                            placeholder="e.g. 1"
                            min="1"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Follow-up Channel Recorded</label>
                        <select
                            name="criteria[followup_channel]"
                            x-model="criteria.followup_channel"
                            @change="fetchPreview()"
                            class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Any Channel</option>
                            @foreach($options['channels'] as $ch)
                                <option value="{{ $ch }}">{{ ucfirst($ch) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-ui.card>

            <!-- Submit Controls -->
            <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                <x-ui.button type="submit" variant="primary">
                    {{ $segment->exists ? 'Update Segment' : 'Save Segment' }}
                </x-ui.button>
                <x-ui.button variant="secondary" :href="route('crm.leads.segments.index')">
                    Cancel
                </x-ui.button>
            </div>
        </div>

        <!-- Right 1 Col: Live Preview Panel -->
        <div class="space-y-6">
            <x-ui.card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wider">Live Audience Preview</h3>
                    <button type="button" @click="fetchPreview()" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" :class="isLoading && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </button>
                </div>

                <!-- Stats summary -->
                <div class="my-4 grid grid-cols-3 gap-2 text-center">
                    <div class="p-2.5 rounded-lg bg-indigo-50 border border-indigo-100">
                        <div class="text-xs font-semibold uppercase text-indigo-600">Total Match</div>
                        <div class="text-xl font-bold text-indigo-900 mt-0.5" x-text="previewData.total_count">0</div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-100">
                        <div class="text-xs font-semibold uppercase text-emerald-600">With Mobile</div>
                        <div class="text-xl font-bold text-emerald-900 mt-0.5" x-text="previewData.with_mobile_count">0</div>
                    </div>
                    <div class="p-2.5 rounded-lg bg-purple-50 border border-purple-100">
                        <div class="text-xs font-semibold uppercase text-purple-600">With Email</div>
                        <div class="text-xl font-bold text-purple-900 mt-0.5" x-text="previewData.with_email_count">0</div>
                    </div>
                </div>

                <!-- Active Rules Pills -->
                <div class="space-y-1.5 pb-4 border-b border-slate-100">
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Rules:</h4>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="(rule, idx) in previewData.summary" :key="idx">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200" x-text="rule"></span>
                        </template>
                    </div>
                </div>

                <!-- Sample Leads list -->
                <div class="pt-3">
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Sample Matching Leads</h4>
                    <div class="space-y-2 max-h-96 overflow-y-auto pr-1 text-xs">
                        <template x-if="isLoading">
                            <div class="py-8 text-center text-slate-400">
                                <svg class="w-6 h-6 animate-spin mx-auto text-indigo-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Evaluating matching database records...</span>
                            </div>
                        </template>

                        <template x-if="!isLoading && previewData.sample_leads.length === 0">
                            <div class="py-6 text-center text-slate-400">
                                <span>No leads currently match this combination of rules.</span>
                            </div>
                        </template>

                        <template x-for="lead in previewData.sample_leads" :key="lead.id">
                            <div class="p-2.5 rounded-lg border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-900" x-text="lead.name"></span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded font-medium bg-slate-200 text-slate-700" x-text="lead.status"></span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5" x-text="lead.company"></div>
                                <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-600">
                                    <span x-text="lead.location"></span>
                                    <span class="font-medium text-indigo-700" x-text="lead.purchase_status"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </form>
</div>

<script>
function segmentFormManager(initialCriteria) {
    return {
        criteria: initialCriteria || {},
        isLoading: false,
        previewData: {
            total_count: 0,
            with_mobile_count: 0,
            with_email_count: 0,
            summary: [],
            sample_leads: []
        },

        init() {
            this.fetchPreview();
        },

        async fetchPreview() {
            this.isLoading = true;

            try {
                const res = await fetch('{{ route('crm.leads.segments.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ criteria: this.criteria })
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.success) {
                        this.previewData = data;
                    }
                }
            } catch (err) {
                // Ignore network interruption
            } finally {
                this.isLoading = false;
            }
        }
    };
}
</script>
@endsection

