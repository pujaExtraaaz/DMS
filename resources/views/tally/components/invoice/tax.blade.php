@props(['name', 'value' => ''])
<input {{ $attributes->merge(['class' => 'input', 'inputmode' => 'decimal', 'data-tax' => true, 'title' => 'Used only when the line has no tax rate. A tax rate posts CGST, SGST, or IGST.']) }} name="{{ $name }}" value="{{ $value }}">
