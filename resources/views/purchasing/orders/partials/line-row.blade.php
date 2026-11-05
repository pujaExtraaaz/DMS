@php
    $idx = $idx ?? 0;
    $selectedBrandId = $selectedBrandId ?? null;
    $selectedProductId = $selectedProductId ?? null;
    $selectedProductName = $selectedProductName ?? '';
    $batchNo = $batchNo ?? '';
    $uomId = $uomId ?? null;
    $quantity = $quantity ?? 1;
    $unitCost = (float) ($unitCost ?? 0);
    $taxPercent = (float) ($taxPercent ?? 0);
    $mrp = $mrp ?? 0;
    $priceInclusiveTax = $priceInclusiveTax ?? $purchasePriceInclusive ?? (
        $taxPercent > 0
            ? round($unitCost * (1 + ($taxPercent / 100)), 2)
            : round($unitCost, 2)
    );
    $mrpValue = (float) $mrp;
    $purchaseDiscountPercent = $purchaseDiscountPercent ?? (
        $mrpValue > 0
            ? round((($mrpValue - (float) $priceInclusiveTax) / $mrpValue) * 100, 2)
            : ''
    );
    $cgstPercent = $cgstPercent ?? 0;
    $sgstPercent = $sgstPercent ?? 0;
    $showRemove = $showRemove ?? false;
    $isBrandSelected = !empty($selectedBrandId);
@endphp
<tr class="po-line-row">
    <td class="px-3 py-2">
        <select name="items[{{ $idx }}][brand_id]" class="po-brand block w-44 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white" required>
            <option value="">Select brand</option>
            @foreach($brands as $b)
                <option value="{{ $b->id }}" @selected((string) $selectedBrandId === (string) $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
    </td>
    <td class="px-3 py-2">
        <div class="relative po-product-picker-container w-64">
            <select name="items[{{ $idx }}][product_id]" class="po-product hidden">
                <option value="">Select product</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" data-brand="{{ $p->brand_id }}" data-tax="{{ $p->tax_rate ?? 0 }}" data-cost="{{ $p->purchase_price ?? 0 }}" data-mrp="{{ $p->mrp ?? 0 }}" data-uom="{{ $p->base_uom_id }}" @selected((string) $selectedProductId === (string) $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
            <div class="relative">
                <input type="text"
                       class="po-product-search block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-14 {{ $isBrandSelected ? 'bg-white' : 'bg-slate-100 text-slate-400 cursor-not-allowed' }}"
                       placeholder="{{ $isBrandSelected ? 'Select product...' : 'Select brand first' }}"
                       autocomplete="off"
                       value="{{ $selectedProductName }}"
                       {{ $isBrandSelected ? '' : 'disabled' }}
                       onfocus="openPoProductDropdown(this)"
                       onclick="openPoProductDropdown(this)"
                       oninput="handlePoProductSearchInput(this)"
                       onkeydown="handlePoProductSearchKeydown(event, this)">
                <div class="absolute inset-y-0 right-0 flex items-center pr-1.5 gap-0.5">
                    <button type="button"
                            class="po-clear-product p-1 text-gray-400 hover:text-gray-600 transition"
                            title="Clear product"
                            style="{{ $selectedProductName !== '' ? '' : 'display: none;' }}"
                            onclick="clearPoProductSelection(this)">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <button type="button"
                            class="po-dropdown-toggle p-1 text-gray-400 hover:text-gray-600 transition {{ $isBrandSelected ? '' : 'opacity-40 pointer-events-none' }}"
                            tabindex="-1"
                            {{ $isBrandSelected ? '' : 'disabled' }}
                            onclick="togglePoProductDropdown(this)">
                        <svg class="h-4 w-4 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </td>
    <td class="px-3 py-2">
        <input type="text" name="items[{{ $idx }}][batch_no]" list="po-batch-list-{{ $idx }}" class="po-batch-input block w-28 rounded-lg border-gray-300 text-sm" placeholder="Optional" value="{{ $batchNo }}">
        <datalist id="po-batch-list-{{ $idx }}"></datalist>
    </td>
    <td class="px-3 py-2">
        <select name="items[{{ $idx }}][uom_id]" class="po-uom block w-24 rounded-lg border-gray-300 text-sm" required>
            @foreach($uoms as $u)
                <option value="{{ $u->id }}" @selected((string) $uomId === (string) $u->id)>{{ $u->code }}</option>
            @endforeach
        </select>
    </td>
    <td class="px-3 py-2"><input type="number" step="0.0001" name="items[{{ $idx }}][quantity]" class="block w-20 rounded-lg border-gray-300 text-sm" value="{{ $quantity }}" required></td>
    <td class="px-3 py-2">
        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][mrp]" class="po-mrp block w-28 rounded-lg border-gray-300 text-sm" value="{{ $mrpValue }}" title="MRP">
    </td>
    <td class="px-3 py-2">
        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][price_inclusive_tax]" class="po-price-inclusive block w-28 rounded-lg border-gray-300 text-sm" value="{{ $priceInclusiveTax }}" title="Purchase Price Including GST">
    </td>
    <td class="px-3 py-2">
        <input type="number" step="0.01" name="items[{{ $idx }}][purchase_discount_percent]" class="po-purchase-discount block w-24 rounded-lg border-gray-300 text-sm bg-slate-50" value="{{ $purchaseDiscountPercent }}" title="Purchase Discount %" readonly tabindex="-1">
    </td>
    <td class="px-3 py-2"><input type="number" step="0.0001" name="items[{{ $idx }}][unit_cost]" class="po-cost block w-28 rounded-lg border-gray-300 text-sm" value="{{ $unitCost }}" required></td>
    <td class="px-3 py-2">
        <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][tax_percent]" class="po-tax block w-20 rounded-lg border-gray-300 text-sm" value="{{ $taxPercent }}">
    </td>
    <td class="px-3 py-2">
        <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][cgst_percent]" class="po-cgst block w-20 rounded-lg border-gray-300 text-sm" value="{{ $cgstPercent }}">
    </td>
    <td class="px-3 py-2">
        <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][sgst_percent]" class="po-sgst block w-20 rounded-lg border-gray-300 text-sm" value="{{ $sgstPercent }}">
    </td>
    <td class="px-3 py-2 text-right">
        <button type="button" onclick="removePoRow(this)" class="text-xs text-red-600 hover:text-red-800 po-remove-btn" @if(! $showRemove) style="display:none;" @endif>Remove</button>
    </td>
</tr>
