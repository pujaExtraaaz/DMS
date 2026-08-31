<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\CustomerType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerTypeController extends Controller
{
    public function index(Request $request): View
    {
        $items = CustomerType::query()->latest()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->paginate(15)
            ->withQueryString();

        return view('masters.customer-types.index', compact('items'));
    }

    public function create(): View
    {
        return view('masters.customer-types.form', array_merge(['item' => new CustomerType], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        CustomerType::create($data);

        return $this->flashSuccess('Customer Type created successfully.', 'masters.customer-types.index');
    }

    public function show(CustomerType $customer_type): RedirectResponse
    {
        return redirect()->route('masters.customer-types.edit', $customer_type);
    }

    public function edit(CustomerType $customer_type): View
    {
        return view('masters.customer-types.form', array_merge(['item' => $customer_type], $this->formData()));
    }

    public function update(Request $request, CustomerType $customer_type): RedirectResponse
    {
        $data = $this->validated($request, $customer_type);
        $customer_type->update($data);

        return $this->flashSuccess('Customer Type updated successfully.', 'masters.customer-types.index');
    }

    public function destroy(CustomerType $customer_type): RedirectResponse
    {
        $customer_type->delete();

        return $this->flashSuccess('Customer Type deleted successfully.', 'masters.customer-types.index');
    }

    protected function validated(Request $request, ?CustomerType $customer_type = null): array
    {
        $rules = ['name' => 'required|string|max:255', 'code' => 'required|string|max:20|unique:customer_types,code', 'description' => 'nullable|string', 'is_active' => 'boolean'];
        if ($customer_type) {
            if (isset($rules['code'])) {
                $rules['code'] = 'required|string|max:20|unique:customer-types,code,'.$customer_type->id;
            }
            if (isset($rules['registration_no'])) {
                $rules['registration_no'] = 'required|string|max:20|unique:vehicles,registration_no,'.$customer_type->id;
            }
        }
        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function formData(): array
    {
        return [];
    }
}