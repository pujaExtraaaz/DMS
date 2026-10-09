@props(['invoice'])
<label class="tally-co-row is-address">
    <span>Narration</span>
    <span>:</span>
    <textarea class="input" name="narration" maxlength="1000">{{ old('narration', $invoice->narration) }}</textarea>
</label>
