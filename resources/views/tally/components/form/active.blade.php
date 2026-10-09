@props(['checked' => true])
<label {{ $attributes->class(['check']) }}>
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', $checked ? '1' : '0') === '1')>
    <span>Active</span>
</label>
