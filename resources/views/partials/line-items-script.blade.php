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
        <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="items[${index}][unit_price]" class="price-input block w-full rounded-lg border-gray-300 text-sm" required></td>
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
    if (!customerId || !productId || !uomId || !qty) return;
    const url = @json(route('orders.resolve-price')) + `?customer_id=${customerId}&product_id=${productId}&uom_id=${uomId}&quantity=${qty}`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.unit_price) row.querySelector('.price-input').value = data.unit_price;
}
function bindPriceFetch() {
    document.querySelectorAll('#line-items tr').forEach(row => {
        row.querySelector('.product-select')?.addEventListener('change', () => fetchPriceForRow(row));
        row.querySelector('.uom-select')?.addEventListener('change', () => fetchPriceForRow(row));
        row.querySelector('.qty-input')?.addEventListener('change', () => fetchPriceForRow(row));
    });
}
document.addEventListener('DOMContentLoaded', bindPriceFetch);
document.getElementById('customer_id')?.addEventListener('change', () => {
    document.querySelectorAll('#line-items tr').forEach(fetchPriceForRow);
});
</script>
