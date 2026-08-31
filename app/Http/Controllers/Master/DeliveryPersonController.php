<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\DeliveryPerson;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryPersonController extends Controller
{
    public function index(Request $request): View
    {
        $items = DeliveryPerson::query()->latest()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->paginate(15)
            ->withQueryString();

        return view('masters.delivery-persons.index', compact('items'));
    }

    public function create(): View
    {
        return view('masters.delivery-persons.form', array_merge(['item' => new DeliveryPerson], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DeliveryPerson::create($data);

        return $this->flashSuccess('Delivery Person created successfully.', 'masters.delivery-persons.index');
    }

    public function show(DeliveryPerson $delivery_person): RedirectResponse
    {
        return redirect()->route('masters.delivery-persons.edit', $delivery_person);
    }

    public function edit(DeliveryPerson $delivery_person): View
    {
        return view('masters.delivery-persons.form', array_merge(['item' => $delivery_person], $this->formData()));
    }

    public function update(Request $request, DeliveryPerson $delivery_person): RedirectResponse
    {
        $data = $this->validated($request, $delivery_person);
        $delivery_person->update($data);

        return $this->flashSuccess('Delivery Person updated successfully.', 'masters.delivery-persons.index');
    }

    public function destroy(DeliveryPerson $delivery_person): RedirectResponse
    {
        $delivery_person->delete();

        return $this->flashSuccess('Delivery Person deleted successfully.', 'masters.delivery-persons.index');
    }

    protected function validated(Request $request, ?DeliveryPerson $delivery_person = null): array
    {
        $rules = ['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:20', 'is_active' => 'boolean'];
        if ($delivery_person) {
            if (isset($rules['code'])) {
                $rules['code'] = 'required|string|max:20|unique:delivery-persons,code,'.$delivery_person->id;
            }
            if (isset($rules['registration_no'])) {
                $rules['registration_no'] = 'required|string|max:20|unique:vehicles,registration_no,'.$delivery_person->id;
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