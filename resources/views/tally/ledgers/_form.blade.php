@php
    use Tally\Accounting\OpeningBalanceType;
@endphp
<div class="form-grid">
    <x-tally::form.field name="name" label="Name" required>
        <x-tally::form.input name="name" value="{{ old('name', $ledger->name) }}" required :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.field name="code" label="Code">
        <x-tally::form.input name="code" value="{{ old('code', $ledger->code) }}" maxlength="32" :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.field name="account_group_id" label="Group" required class="span-2">
        <select id="account_group_id" name="account_group_id" class="input" required @disabled($ledger->is_system)>
            <option value="">Select group</option>
            @foreach ($groups as $row)
                <option value="{{ $row['group']->id }}" @selected((string) old('account_group_id', $ledger->account_group_id) === (string) $row['group']->id)>
                    {{ str_repeat('· ', $row['depth']) }}{{ $row['group']->name }}
                </option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="opening_balance" label="Opening balance" required>
        <x-tally::form.input name="opening_balance" type="number" min="0" step="0.01" value="{{ old('opening_balance', $ledger->opening_balance ?? '0.00') }}" required :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.field name="opening_balance_type" label="Balance type" required>
        <select id="opening_balance_type" name="opening_balance_type" class="input" @disabled($ledger->is_system)>
            @foreach (OpeningBalanceType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('opening_balance_type', $ledger->opening_balance_type?->value ?? 'debit') === $type->value)>{{ $type === OpeningBalanceType::Debit ? 'Debit' : 'Credit' }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="address" label="Address" class="span-2">
        <x-tally::form.textarea name="address" :value="old('address', $ledger->address)" :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.field name="phone" label="Phone">
        <x-tally::form.input name="phone" value="{{ old('phone', $ledger->phone) }}" :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.field name="email" label="Email">
        <x-tally::form.input name="email" type="email" value="{{ old('email', $ledger->email) }}" :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.field name="state" label="State">
        <x-tally::form.input name="state" value="{{ old('state', $ledger->state) }}" maxlength="80" list="indian-states" :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.field name="gstin" label="GSTIN">
        <div class="gstin-fetch">
            <x-tally::form.input name="gstin" value="{{ old('gstin', $ledger->gstin) }}" maxlength="15" data-gstin :disabled="$ledger->is_system" />
            @unless ($ledger->is_system)
                <button class="btn" type="button" data-gstin-fetch="{{ tally_route('books.tally.gstin.lookup') }}">Fetch</button>
            @endunless
        </div>
        <p class="form-note" data-gstin-note></p>
    </x-form.field>
    <x-tally::form.field name="gst_registration_type" label="GST registration">
        <select id="gst_registration_type" name="gst_registration_type" class="input" @disabled($ledger->is_system)>
            <option value="">Not set</option>
            @foreach (\Tally\Tax\GstRegistrationType::cases() as $registration)
                <option value="{{ $registration->value }}" @selected(old('gst_registration_type', $ledger->gst_registration_type?->value) === $registration->value)>{{ $registration->label() }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="pan" label="PAN">
        <x-tally::form.input name="pan" value="{{ old('pan', $ledger->pan) }}" maxlength="10" :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.field name="credit_limit" label="Credit limit">
        <x-tally::form.input name="credit_limit" type="number" min="0" step="0.01" value="{{ old('credit_limit', $ledger->credit_limit) }}" :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.field name="deduction_section_id" label="TDS / TCS section">
        <select id="deduction_section_id" name="deduction_section_id" class="input" @disabled($ledger->is_system)>
            <option value="">None</option>
            @foreach (($deductionSections ?? \Tally\Models\DeductionSection::query()->where('company_id', $ledger->company_id ?: app(\Tally\Context\WorkspaceContext::class)->companyId())->where('is_active', true)->orderBy('section_code')->get()) as $section)
                <option value="{{ $section->id }}" @selected((string) old('deduction_section_id', $ledger->deduction_section_id) === (string) $section->id)>{{ $section->section_code }} · {{ $section->name }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="cash_flow_class" label="Cash flow class">
        <select id="cash_flow_class" name="cash_flow_class" class="input" @disabled($ledger->is_system)>
            <option value="">From account group</option>
            @foreach (['Operating', 'Investing', 'Financing'] as $flow)
                <option value="{{ $flow }}" @selected(old('cash_flow_class', $ledger->cash_flow_class) === $flow)>{{ $flow }}</option>
            @endforeach
        </select>
    </x-form.field>
    <x-tally::form.field name="credit_days" label="Credit days">
        <x-tally::form.input name="credit_days" type="number" min="0" step="1" value="{{ old('credit_days', $ledger->credit_days) }}" :disabled="$ledger->is_system" />
    </x-form.field>
    <x-tally::form.active class="span-2" :checked="$ledger->is_active ?? true" />
</div>
@php
    $bankGroupIds = $bankGroupIds ?? [];
    $bankAccount = $ledger->exists
        ? ($ledger->relationLoaded('bankAccount') ? $ledger->bankAccount : $ledger->bankAccount()->first())
        : null;
@endphp
<section id="bank-account-fields" class="voucher-section" @if (! in_array((int) old('account_group_id', $ledger->account_group_id), $bankGroupIds, true)) hidden @endif>
    <h2>Bank account</h2>
    <p class="form-note">These details apply to a ledger under Bank Accounts. The opening balance above is the opening bank balance.</p>
    <div class="form-grid">
        <x-tally::form.field name="bank_name" label="Bank name" required>
            <x-tally::form.input name="bank_name" value="{{ old('bank_name', $bankAccount->bank_name ?? '') }}" maxlength="120" :disabled="$ledger->is_system" />
        </x-form.field>
        <x-tally::form.field name="account_number" label="Account number" required>
            <x-tally::form.input name="account_number" value="{{ old('account_number', $bankAccount->account_number ?? '') }}" maxlength="30" :disabled="$ledger->is_system" />
        </x-form.field>
        <x-tally::form.field name="ifsc" label="IFSC" required>
            <x-tally::form.input name="ifsc" value="{{ old('ifsc', $bankAccount->ifsc ?? '') }}" maxlength="11" :disabled="$ledger->is_system" />
        </x-form.field>
    </div>
</section>
<p class="form-note muted">Opening balance is stored as Debit or Credit. A ledger used by vouchers cannot be deleted. Party state decides CGST/SGST or IGST on invoices.</p>
<datalist id="indian-states">
    @foreach (config('states') as $state)
        <option value="{{ $state }}"></option>
    @endforeach
</datalist>
<script>
(function () {
    const ids = new Set(@json(array_map('intval', $bankGroupIds ?? [])));
    const group = document.getElementById('account_group_id');
    const panel = document.getElementById('bank-account-fields');
    if (!group || !panel) {
        return;
    }
    function refresh() {
        panel.hidden = !ids.has(Number(group.value));
    }
    group.addEventListener('change', refresh);
    refresh();
})();
</script>
