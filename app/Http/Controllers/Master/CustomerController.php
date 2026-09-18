<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyContact;
use App\Domains\Master\Models\Route;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $items = Customer::query()
            ->with(['customerType', 'area', 'route', 'salesperson'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            }))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->area_id))
            ->when($request->filled('customer_type_id'), fn ($q) => $q->where('customer_type_id', $request->customer_type_id))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('masters.customers.index', [
            'items' => $items,
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('masters.customers.form', array_merge(['item' => new Customer], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $customer = Customer::create($this->validated($request));
            $this->syncChildRows($request, $customer);
        });

        return $this->flashSuccess('Customer created successfully.', 'masters.customers.index');
    }

    public function show(Customer $customer): RedirectResponse
    {
        return redirect()->route('masters.customers.edit', $customer);
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['contacts', 'addresses']);

        return view('masters.customers.form', array_merge(['item' => $customer], $this->formData()));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        DB::transaction(function () use ($request, $customer) {
            $customer->update($this->validated($request, $customer));
            $this->syncChildRows($request, $customer);
        });

        return $this->flashSuccess('Customer updated successfully.', 'masters.customers.index');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return $this->flashSuccess('Customer deleted successfully.', 'masters.customers.index');
    }

    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30|unique:customers,code'.($customer ? ','.$customer->id : ''),
            'party_type' => 'required|in:dealer,customer,supplier,both',
            'customer_type_id' => 'required|exists:customer_types,id',
            'area_id' => 'nullable|exists:areas,id',
            'route_id' => 'nullable|exists:routes,id',
            'salesperson_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:12',
            'gstin' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'credit_period_basis' => 'nullable|in:monthly,quarterly,yearly,cumulative',
            'credit_status' => 'nullable|in:open,restricted,frozen',
            'credit_notes' => 'nullable|string',
            'is_active' => 'boolean',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'nullable|string|max:255',
            'contacts.*.level' => 'nullable|in:primary,secondary,finance,operations,site',
            'contacts.*.role' => 'nullable|string|max:100',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.alternate_phone' => 'nullable|string|max:20',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.location' => 'nullable|string|max:255',
            'contacts.*.is_primary' => 'nullable|boolean',
            'addresses' => 'nullable|array',
            'addresses.*.type' => 'nullable|in:billing,shipping,site,warehouse',
            'addresses.*.label' => 'nullable|string|max:255',
            'addresses.*.name' => 'nullable|string|max:255',
            'addresses.*.address' => 'nullable|string',
            'addresses.*.state' => 'nullable|string|max:100',
            'addresses.*.pincode' => 'nullable|string|max:12',
            'addresses.*.gstin' => 'nullable|string|max:20',
            'addresses.*.is_default' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['credit_days'] = $data['credit_days'] ?? 0;
        $data['interest_rate'] = $data['interest_rate'] ?? 18;
        $data['credit_period_basis'] = $data['credit_period_basis'] ?? 'cumulative';
        $data['credit_status'] = $data['credit_status'] ?? 'open';
        $data['company_id'] = $customer?->company_id ?? auth()->user()?->company_id;
        $data['branch_id'] = $customer?->branch_id ?? auth()->user()?->branch_id;

        // Strip repeaters so they aren't mass-assigned to Customer::update.
        unset($data['contacts'], $data['addresses']);

        return $data;
    }

    protected function syncChildRows(Request $request, Customer $customer): void
    {
        $contacts = collect($request->input('contacts', []))
            ->filter(fn ($row) => filled($row['name'] ?? null) || filled($row['phone'] ?? null) || filled($row['email'] ?? null))
            ->map(fn ($row) => [
                'level' => $row['level'] ?? 'secondary',
                'name' => $row['name'] ?? null,
                'role' => $row['role'] ?? null,
                'phone' => $row['phone'] ?? null,
                'alternate_phone' => $row['alternate_phone'] ?? null,
                'email' => $row['email'] ?? null,
                'location' => $row['location'] ?? null,
                'is_primary' => (bool) ($row['is_primary'] ?? false),
                'is_active' => true,
            ])
            ->values();

        $customer->contacts()->delete();
        foreach ($contacts as $row) {
            $customer->contacts()->create($row);
        }

        $addresses = collect($request->input('addresses', []))
            ->filter(fn ($row) => filled($row['address'] ?? null) || filled($row['label'] ?? null))
            ->map(fn ($row) => [
                'type' => $row['type'] ?? 'billing',
                'label' => $row['label'] ?? null,
                'name' => $row['name'] ?? null,
                'address' => $row['address'] ?? null,
                'state' => $row['state'] ?? null,
                'pincode' => $row['pincode'] ?? null,
                'gstin' => $row['gstin'] ?? null,
                'is_default' => (bool) ($row['is_default'] ?? false),
            ])
            ->values();

        $customer->addresses()->delete();
        foreach ($addresses as $row) {
            $customer->addresses()->create($row);
        }
    }

    protected function formData(): array
    {
        $salesRoles = ['salesperson', 'sales-manager'];

        return [
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'routes' => Route::where('is_active', true)->orderBy('name')->get(),
            'salespersons' => User::role($salesRoles)->orderBy('name')->get(),
        ];
    }
}
