@props([
    'targetSelect' => 'supplier_id',
])

<div x-data="quickAddSupplierModal('{{ $targetSelect }}')"
     x-on:open-quick-add-supplier.window="openModal()"
     x-cloak>
    <div x-show="open"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-slate-200"
             @click.outside="closeModal()">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="text-base font-semibold text-slate-800">Quick Add Supplier</h3>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <template x-if="errorMessage">
                <div class="p-3 bg-red-50 text-red-700 text-xs rounded-lg border border-red-200" x-text="errorMessage"></div>
            </template>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">GSTIN</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="form.gstin" maxlength="15" placeholder="e.g. 27AAAAA0000A1Z5"
                               class="block w-full uppercase rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="button" @click="fetchGstDetails()" :disabled="searchingGst"
                                class="inline-flex items-center px-3 py-2 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-100 disabled:opacity-50">
                            <span x-show="!searchingGst">Fetch</span>
                            <span x-show="searchingGst">...</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Supplier Name *</label>
                    <input type="text" x-model="form.name" required placeholder="Business or Company Name"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone</label>
                        <input type="text" x-model="form.phone" placeholder="Contact number"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email</label>
                        <input type="email" x-model="form.email" placeholder="vendor@example.com"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">State</label>
                        <input type="text" x-model="form.state" placeholder="State"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pincode</label>
                        <input type="text" x-model="form.pincode" placeholder="Pincode"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Address</label>
                    <textarea x-model="form.address" rows="2" placeholder="Full billing address"
                              class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t">
                <button type="button" @click="closeModal()"
                        class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-xs font-medium hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" @click="saveSupplier()" :disabled="saving"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-medium hover:bg-indigo-700 disabled:opacity-50">
                    <span x-show="!saving">Save Supplier</span>
                    <span x-show="saving">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function quickAddSupplierModal(targetSelectName) {
    return {
        open: false,
        saving: false,
        searchingGst: false,
        errorMessage: '',
        form: {
            name: '',
            gstin: '',
            phone: '',
            email: '',
            state: '',
            pincode: '',
            address: '',
            party_type: 'sundry_creditors'
        },
        openModal() {
            this.reset();
            this.open = true;
        },
        closeModal() {
            this.open = false;
        },
        reset() {
            this.form = {
                name: '',
                gstin: '',
                phone: '',
                email: '',
                state: '',
                pincode: '',
                address: '',
                party_type: 'sundry_creditors'
            };
            this.errorMessage = '';
            this.saving = false;
            this.searchingGst = false;
        },
        async fetchGstDetails() {
            const gstin = (this.form.gstin || '').trim().toUpperCase();
            this.form.gstin = gstin;
            if (gstin.length !== 15) {
                this.errorMessage = 'Please enter a valid 15-character GSTIN.';
                return;
            }
            this.searchingGst = true;
            this.errorMessage = '';
            try {
                const response = await fetch(`{{ route('masters.customers.gst-lookup') }}?gstin=${encodeURIComponent(gstin)}`);
                const data = await response.json();
                if (data.success && data.party) {
                    this.form.name = data.party.name || this.form.name;
                    this.form.state = data.party.state || this.form.state;
                    this.form.pincode = data.party.pincode || this.form.pincode;
                    this.form.address = data.party.address || this.form.address;
                } else {
                    this.errorMessage = data.message || 'GST details not found.';
                }
            } catch (err) {
                this.errorMessage = 'Failed to connect to GST lookup service.';
            } finally {
                this.searchingGst = false;
            }
        },
        async saveSupplier() {
            if (!this.form.name.trim()) {
                this.errorMessage = 'Supplier name is required.';
                return;
            }
            this.saving = true;
            this.errorMessage = '';
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                                  document.querySelector('input[name="_token"]')?.value;
                const response = await fetch('{{ route("masters.customers.quick-add") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(this.form)
                });
                const res = await response.json();
                if (res.success && res.party) {
                    const select = document.querySelector(`select[name="${targetSelectName}"]`);
                    if (select) {
                        const opt = document.createElement('option');
                        opt.value = res.party.id;
                        opt.textContent = res.party.name;
                        opt.selected = true;
                        select.appendChild(opt);
                        select.value = res.party.id;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    window.dispatchEvent(new CustomEvent('supplier-quick-added', { detail: res.party }));
                    this.closeModal();
                } else {
                    this.errorMessage = res.message || 'Failed to save supplier.';
                }
            } catch (err) {
                this.errorMessage = 'An error occurred while saving supplier.';
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>