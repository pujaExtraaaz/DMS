<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\CustomerType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

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

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $data = $this->validated($request);
            $customerType = CustomerType::create($data);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => true,
                    'customer_type' => [
                        'id' => $customerType->id,
                        'name' => $customerType->name,
                        'code' => $customerType->code,
                    ],
                ]);
            }

            return $this->flashSuccess('Customer Type created successfully.', 'masters.customer-types.index');
        } catch (ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->validator->errors()->first() ?: 'Validation failed.',
                    'errors' => $e->validator->errors()->toArray(),
                ], 422);
            }
            throw $e;
        } catch (Throwable $e) {
            Log::error('Classification create failed: '.$e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Failed to create classification. Please try again.',
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create classification. Please try again.');
        }
    }

    public function show(CustomerType $customer_type): RedirectResponse
    {
        return redirect()->route('masters.customer-types.edit', $customer_type);
    }

    public function edit(CustomerType $customer_type): View
    {
        return view('masters.customer-types.form', array_merge(['item' => $customer_type], $this->formData()));
    }

    public function update(Request $request, CustomerType $customer_type): RedirectResponse|JsonResponse
    {
        try {
            $data = $this->validated($request, $customer_type);
            $customer_type->update($data);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => true,
                    'customer_type' => [
                        'id' => $customer_type->id,
                        'name' => $customer_type->name,
                        'code' => $customer_type->code,
                    ],
                ]);
            }

            return $this->flashSuccess('Customer Type updated successfully.', 'masters.customer-types.index');
        } catch (ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->validator->errors()->first() ?: 'Validation failed.',
                    'errors' => $e->validator->errors()->toArray(),
                ], 422);
            }
            throw $e;
        } catch (Throwable $e) {
            Log::error('Classification update failed: '.$e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Failed to update classification. Please try again.',
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update classification. Please try again.');
        }
    }

    public function destroy(CustomerType $customer_type): RedirectResponse
    {
        $customer_type->delete();

        return $this->flashSuccess('Customer Type deleted successfully.', 'masters.customer-types.index');
    }

    protected function validated(Request $request, ?CustomerType $customer_type = null): array
    {
        $name = trim((string) $request->input('name', ''));
        $request->merge(['name' => $name]);

        if (blank($request->input('code')) && filled($name)) {
            $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
            $code = substr($baseCode ?: 'CT', 0, 10);
            $seq = 1;
            while (CustomerType::query()->where('code', $code)->when($customer_type, fn ($q) => $q->where('id', '!=', $customer_type->id))->exists()) {
                $code = substr($baseCode ?: 'CT', 0, 7) . $seq;
                $seq++;
            }
            $request->merge(['code' => $code]);
        }

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($customer_type) {
                    $trimmed = trim((string) $value);
                    if ($trimmed === '') {
                        $fail('Classification name is required.');
                        return;
                    }
                    $exists = CustomerType::query()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($trimmed)])
                        ->when($customer_type, fn ($q) => $q->where('id', '!=', $customer_type->id))
                        ->exists();

                    if ($exists) {
                        $fail('Classification already exists.');
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customer_types', 'code')->ignore($customer_type?->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        $messages = [
            'name.required' => 'Classification name is required.',
            'code.unique' => 'Classification code already exists.',
        ];

        $data = $request->validate($rules, $messages);
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        return $data;
    }

    protected function formData(): array
    {
        return [];
    }
}