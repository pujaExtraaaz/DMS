<script>
function lineItemRow(index) {
    const products = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name]));
    const uoms = @json($uoms->map(fn($u) => ['id' => $u->id, 'code' => $u->code]));
    const productOpts = products.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
    const uomOpts = uoms.map(u => `<option value="${u.id}">${u.code}</option>`).join('');
    return `<tr>
        <td class="px-3 py-2"><select name="items[${index}][product_id]" class="product-select block w-full rounded-lg border-gray-300 text-sm" required>${productOpts}</select></td>
        <td class="px-3 py-2"><select name="items[${index}][uom_id]" class="uom-select block w-full rounded-lg border-gray-300 text-sm" required>${uomOpts}</select></td>
        <td class="px-3 py-2"><input type="number" step="0.0001" min="0.0001" name="items[${index}][quantity]" class="qty-input block w-full rounded-lg border-gray-300 text-sm" required value="1"></td>
        <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="items[${index}][unit_price]" class="price-input block w-full rounded-lg border-gray-300 bg-gray-50 text-sm" readonly required></td>
        <td class="px-3 py-2 text-right"><output class="line-total text-sm font-medium text-gray-900">₹0.00</output></td>
        <td class="px-3 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-red-600 text-sm">Remove</button></td>
    </tr>`;
}
let rowIndex = 1;
function addRow() {
    document.getElementById('line-items').insertAdjacentHTML('beforeend', lineItemRow(rowIndex++));
    bindPriceFetch();
}
async function fetchPriceForRow(row) {
    const customerId = document.getElementById('customer_id')?.value;
    const productId = row.querySelector('.product-select')?.value;
    const uomId = row.querySelector('.uom-select')?.value;
    const qty = row.querySelector('.qty-input')?.value;
    const priceInput = row.querySelector('.price-input');

    if (!priceInput) return;

    if (!customerId || !productId || !uomId || !qty || Number(qty) <= 0) {
        priceInput.value = '';
        priceInput.title = 'Select customer, product, UOM, and a valid quantity to resolve price.';
        updateRowTotal(row);
        return;
    }

    const url = @json(route('orders.resolve-price')) + `?customer_id=${customerId}&product_id=${productId}&uom_id=${uomId}&quantity=${qty}`;
    priceInput.classList.add('opacity-60');

    try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const resolvedPrice = data.found ? Number(data.unit_price) : 0;

        priceInput.value = data.found ? resolvedPrice.toFixed(2) : '';
        priceInput.title = data.found ? 'Price resolved from Price Master' : 'No price configured for this customer tier and quantity';
        updateRowTotal(row);
    } catch (error) {
        priceInput.value = '';
        priceInput.title = 'Unable to resolve price. Please check the Price Master.';
        updateRowTotal(row);
    } finally {
        priceInput.classList.remove('opacity-60');
    }
}

function updateRowTotal(row) {
    const quantity = Number(row.querySelector('.qty-input')?.value || 0);
    const unitPrice = Number(row.querySelector('.price-input')?.value || 0);
    const output = row.querySelector('.line-total');

    if (output) {
        const total = Number.isFinite(quantity) && Number.isFinite(unitPrice) ? quantity * unitPrice : 0;
        output.textContent = `₹${total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
}

function bindPriceFetch() {
    document.querySelectorAll('#line-items tr').forEach(row => {
        if (row.dataset.priceBound === 'true') return;
        row.dataset.priceBound = 'true';
        row.querySelector('.product-select')?.addEventListener('change', () => fetchPriceForRow(row));
        row.querySelector('.uom-select')?.addEventListener('change', () => fetchPriceForRow(row));

        let quantityTimer;
        row.querySelector('.qty-input')?.addEventListener('input', () => {
            updateRowTotal(row);
            clearTimeout(quantityTimer);
            quantityTimer = setTimeout(() => fetchPriceForRow(row), 250);
        });

        row.querySelector('.qty-input')?.addEventListener('change', () => fetchPriceForRow(row));
        row.querySelector('.price-input')?.addEventListener('input', () => updateRowTotal(row));
        updateRowTotal(row);
    });
}

document.addEventListener('DOMContentLoaded', bindPriceFetch);
document.getElementById('customer_id')?.addEventListener('change', () => {
    document.querySelectorAll('#line-items tr').forEach(fetchPriceForRow);
});
</script>
