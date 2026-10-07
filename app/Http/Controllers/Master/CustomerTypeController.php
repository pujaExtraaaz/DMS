<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\CustomerType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class CustomerTypeController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = CustomerType::query()
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'code']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'code', 'is_active', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('masters.customer-types.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('masters.customer-types.form', array_merge(['item' => new CustomerType], $this->formData()));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return $this->quickStore($request);
        }

        $data = $this->validated($request);
        CustomerType::create($data);

        return $this->flashSuccess('Customer Type created successfully.', 'masters.customer-types.index');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return response()->json([
                'success' => false,
                'message' => 'Classification Name is required.',
            ], 422);
        }

        if (mb_strlen($name) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Classification Name cannot exceed 255 characters.',
            ], 422);
        }

        // Case-insensitive duplicate check, trimming spaces
        $exists = CustomerType::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A classification with this name already exists.',
            ], 422);
        }

        // Auto-generate unique code
        $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        if ($baseCode === '') {
            $baseCode = 'CLS';
        }
        $baseCode = substr($baseCode, 0, 15);
        $code = $baseCode;
        $suffix = 1;
        while (CustomerType::where('code', $code)->exists()) {
            $code = substr($baseCode, 0, 14) . '_' . $suffix;
            $suffix++;
        }

        try {
            $customerType = CustomerType::create([
                'name' => $name,
                'code' => $code,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Classification created successfully.',
                'item' => [
                    'id' => $customerType->id,
                    'name' => $customerType->name,
                    'code' => $customerType->code,
                ],
            ], 201);
        } catch (\Throwable $e) {
            if (
                str_contains($e->getMessage(), 'Integrity constraint violation') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint failed')
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'A classification with this name already exists.',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the classification: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function list(): JsonResponse
    {
        return response()->json([
            'items' => CustomerType::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
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
                $rules['code'] = 'required|string|max:20|unique:customer_types,code,'.$customer_type->id;
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