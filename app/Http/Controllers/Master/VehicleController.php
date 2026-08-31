<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Vehicle;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $items = Vehicle::query()->latest()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->paginate(15)
            ->withQueryString();

        return view('masters.vehicles.index', compact('items'));
    }

    public function create(): View
    {
        return view('masters.vehicles.form', array_merge(['item' => new Vehicle], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Vehicle::create($data);

        return $this->flashSuccess('Vehicle created successfully.', 'masters.vehicles.index');
    }

    public function show(Vehicle $vehicle): RedirectResponse
    {
        return redirect()->route('masters.vehicles.edit', $vehicle);
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('masters.vehicles.form', array_merge(['item' => $vehicle], $this->formData()));
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $this->validated($request, $vehicle);
        $vehicle->update($data);

        return $this->flashSuccess('Vehicle updated successfully.', 'masters.vehicles.index');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return $this->flashSuccess('Vehicle deleted successfully.', 'masters.vehicles.index');
    }

    protected function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        $rules = ['name' => 'required|string|max:255', 'registration_no' => 'required|string|max:20|unique:vehicles,registration_no', 'type' => 'nullable|string|max:50', 'capacity' => 'nullable|numeric|min:0', 'is_active' => 'boolean'];
        if ($vehicle) {
            if (isset($rules['code'])) {
                $rules['code'] = 'required|string|max:20|unique:vehicles,code,'.$vehicle->id;
            }
            if (isset($rules['registration_no'])) {
                $rules['registration_no'] = 'required|string|max:20|unique:vehicles,registration_no,'.$vehicle->id;
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