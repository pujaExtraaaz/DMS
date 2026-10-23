@props(['kind', 'invoice', 'nextNumber', 'numberingMethod' => 'automatic'])
<div class="tally-co-head">
    <label class="tally-co-row">
        <span>{{ $kind->documentLabel() }}</span>
        <span>:</span>
        @if (! $invoice->exists && in_array($numberingMethod, ['manual', 'none', 'automatic_manual'], true))
            <input class="input" name="invoice_number" value="{{ old('invoice_number', $nextNumber) }}" maxlength="30" @required(in_array($numberingMethod, ['manual', 'none'], true)) aria-label="Invoice number" placeholder="{{ in_array($numberingMethod, ['manual', 'none'], true) ? 'Enter number' : 'Next number' }}">
        @elseif ($numberingMethod === 'multi_user')
            <input class="input" value="Allotted on save" readonly>
        @else
            <input class="input" value="{{ $invoice->exists ? $invoice->invoice_number : $nextNumber }}" readonly>
        @endif
    </label>
    <label class="tally-co-row">
        <span>Date</span>
        <span>:</span>
        <input class="input" name="invoice_date" type="date" data-voucher-date value="{{ old('invoice_date', optional($invoice->invoice_date)->toDateString() ?: app(\Tally\Context\WorkingCalendar::class)->date($invoice->company ?? app(\Tally\Context\WorkspaceContext::class)->company(), auth()->user(), app(\Tally\Context\WorkspaceContext::class)->financialYear())) }}" required>
    </label>
    <label class="tally-co-row">
        <span>Company</span>
        <span>:</span>
        <span class="tally-co-value">{{ $invoice->company?->name ?: app(\Tally\Context\WorkspaceContext::class)->company()?->name }}</span>
    </label>
    <label class="tally-co-row">
        <span>Voucher class</span>
        <span>:</span>
        <span class="tally-co-value">Not Applicable</span>
    </label>
</div>
<label class="tally-co-row">
    <span>Bill name</span>
    <span>:</span>
    <input class="input" name="bill_name" value="{{ old('bill_name', $invoice->bill_name) }}" maxlength="40" placeholder="Uses the voucher number when blank">
</label>
<label class="tally-co-row">
    <span>{{ $kind->referenceLabel() }}</span>
    <span>:</span>
    <input class="input" name="reference_number" value="{{ old('reference_number', $invoice->reference_number) }}" maxlength="50">
</label>
@unless ($invoice->exists)
    <p class="form-note">Assigned when you save. This preview does not reserve the number.</p>
@endunless
