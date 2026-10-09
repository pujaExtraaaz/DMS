<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Controllers\Concerns\ActivatesRecords;
use Tally\Inventory\Quantity;
use Tally\Models\BillOfMaterial;
use Tally\Models\Company;
use Tally\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BillOfMaterialController extends Controller
{
    use ActivatesRecords;

    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Bills of materials',
                'message' => 'Select a company before managing bills of materials.',
            ]);
        }

        $status = (string) $request->query('status', '');
        $bills = $company->billsOfMaterials()
            ->with('finishedProduct')
            ->withCount('orders')
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('tally::manufacturing.boms.index', [
            'company' => $company,
            'bills' => $bills,
            'filters' => ['status' => $status],
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $this->company($context);

        return $company instanceof Company
            ? view('tally::manufacturing.boms.form', $this->formData($company, new BillOfMaterial(['is_active' => true, 'wastage_percent' => '0'])))
            : $company;
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $this->company($context);

        if (! $company instanceof Company) {
            return $company;
        }

        $bill = $company->billsOfMaterials()->create($this->validated($request, $company));
        $this->syncLines($bill, $company, $request);

        return redirect()->route('books.tally.boms.show', $bill)->with('status', 'Bill of materials saved.');
    }

    public function show(BillOfMaterial $bom): View
    {
        $bom->load(['finishedProduct.primaryUnit', 'lines.product.primaryUnit', 'lines.unit', 'byproducts.product', 'orders']);

        return view('tally::manufacturing.boms.show', ['bill' => $bom]);
    }

    public function edit(WorkspaceContext $context, BillOfMaterial $bom): View
    {
        $bom->load(['lines', 'byproducts']);

        return view('tally::manufacturing.boms.form', $this->formData($context->company(), $bom));
    }

    public function update(Request $request, WorkspaceContext $context, BillOfMaterial $bom): RedirectResponse
    {
        $company = $context->company();
        $bom->update($this->validated($request, $company, $bom));
        $bom->lines()->delete();
        $bom->byproducts()->delete();
        $this->syncLines($bom, $company, $request);

        return redirect()->route('books.tally.boms.show', $bom)->with('status', 'Bill of materials updated.');
    }

    public function updateActivation(Request $request, BillOfMaterial $bom): RedirectResponse
    {
        return $this->setActive($request, $bom, 'Bill of materials');
    }

    public function destroy(BillOfMaterial $bom): RedirectResponse
    {
        if (! $bom->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($bom, 'This bill of materials has manufacturing history and cannot be deleted.');
        }

        $bom->delete();

        return redirect()->route('books.tally.boms.index')->with('status', 'Bill of materials deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Company $company, BillOfMaterial $bill): array
    {
        return [
            'company' => $company,
            'bill' => $bill,
            'products' => $company->products()->with('primaryUnit')->where('is_active', true)->orderBy('name')->get(),
            'units' => $company->units()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Company $company, ?BillOfMaterial $bill = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('bills_of_materials', 'name')->where(fn ($query) => $query->where('company_id', $company->id))->ignore($bill)],
            'finished_product_id' => ['required', 'integer', Rule::exists('products', 'id')->where(fn ($query) => $query->where('company_id', $company->id)->where('is_active', true))],
            'wastage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['wastage_percent'] = $data['wastage_percent'] === null || $data['wastage_percent'] === '' ? '0' : $data['wastage_percent'];
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function syncLines(BillOfMaterial $bill, Company $company, Request $request): void
    {
        $errors = [];
        $count = 0;

        foreach (array_values($request->input('lines', [])) as $index => $line) {
            if (! is_array($line) || trim((string) ($line['product_id'] ?? '')) === '') {
                continue;
            }

            $product = Product::query()->with('primaryUnit')->where('company_id', $company->id)->whereKey((int) $line['product_id'])->first();

            if (! $product || $product->id === (int) $bill->finished_product_id) {
                $errors["lines.$index.product_id"] = 'Choose a component other than the finished product.';

                continue;
            }

            try {
                $quantity = Quantity::scale(trim((string) ($line['quantity'] ?? '')), (int) ($product->primaryUnit->decimal_places ?? 4));
            } catch (\InvalidArgumentException $exception) {
                $errors["lines.$index.quantity"] = $exception->getMessage();

                continue;
            }

            if ($quantity <= 0) {
                $errors["lines.$index.quantity"] = 'Enter a quantity greater than zero.';

                continue;
            }

            $bill->lines()->create([
                'product_id' => $product->id,
                'unit_id' => ($line['unit_id'] ?? '') !== '' ? (int) $line['unit_id'] : $product->primary_unit_id,
                'quantity' => Quantity::format($quantity),
                'wastage_percent' => trim((string) ($line['wastage_percent'] ?? '')) === '' ? '0' : $line['wastage_percent'],
            ]);
            $count++;
        }

        foreach (array_values($request->input('byproducts', [])) as $line) {
            if (! is_array($line) || trim((string) ($line['product_id'] ?? '')) === '') {
                continue;
            }

            $product = Product::query()->with('primaryUnit')->where('company_id', $company->id)->whereKey((int) $line['product_id'])->first();

            if (! $product) {
                continue;
            }

            try {
                $quantity = Quantity::scale(trim((string) ($line['quantity'] ?? '0')) ?: '0', (int) ($product->primaryUnit->decimal_places ?? 4));
            } catch (\InvalidArgumentException $exception) {
                $errors['byproducts'] = $exception->getMessage();

                continue;
            }

            $bill->byproducts()->create([
                'product_id' => $product->id,
                'quantity' => Quantity::format($quantity),
            ]);
        }

        if ($count === 0) {
            $errors['lines'] = 'Add at least one component.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function company(WorkspaceContext $context): Company|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.companies.index')->with('error', 'Select a company first.');
        }

        return $company;
    }
}
