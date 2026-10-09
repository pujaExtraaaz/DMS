@props(['kind', 'invoice', 'nextNumber'])
<div class="tally-co-head">
    <label class="tally-co-row">
        <span>{{ $kind->documentLabel() }}</span>
        <span>:</span>
        <input class="input" value="{{ $nextNumber }}" readonly>
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
    <span>{{ $kind->referenceLabel() }}</span>
    <span>:</span>
    <input class="input" name="reference_number" value="{{ old('reference_number', $invoice->reference_number) }}" maxlength="50">
</label>
@unless ($invoice->exists)
    <p class="form-note">Assigned when you save. This preview does not reserve the number.</p>
@endunless
