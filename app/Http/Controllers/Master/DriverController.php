<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Driver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $items = Driver::query()->latest()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->paginate(15)
            ->withQueryString();

        return view('masters.drivers.index', compact('items'));
    }

    public function create(): View
    {
        return view('masters.drivers.form', array_merge(['item' => new Driver], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Driver::create($data);

        return $this->flashSuccess('Driver created successfully.', 'masters.drivers.index');
    }

    public function show(Driver $driver): RedirectResponse
    {
        return redirect()->route('masters.drivers.edit', $driver);
    }

    public function edit(Driver $driver): View
    {
        return view('masters.drivers.form', array_merge(['item' => $driver], $this->formData()));
    }

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $data = $this->validated($request, $driver);
        $driver->update($data);

        return $this->flashSuccess('Driver updated successfully.', 'masters.drivers.index');
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        $driver->delete();

        return $this->flashSuccess('Driver deleted successfully.', 'masters.drivers.index');
    }

    protected function validated(Request $request, ?Driver $driver = null): array
    {
        $rules = ['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:20', 'license_no' => 'nullable|string|max:30', 'is_active' => 'boolean'];
        if ($driver) {
            if (isset($rules['code'])) {
                $rules['code'] = 'required|string|max:20|unique:drivers,code,'.$driver->id;
            }
            if (isset($rules['registration_no'])) {
                $rules['registration_no'] = 'required|string|max:20|unique:vehicles,registration_no,'.$driver->id;
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