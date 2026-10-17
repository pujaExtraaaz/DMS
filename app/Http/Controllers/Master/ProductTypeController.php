<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\ProductType;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductTypeController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return $this->list();
        }

        $query = ProductType::query()
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

        return view('masters.product-types.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function list(): JsonResponse
    {
        return response()->json([
            'items' => ProductType::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return $this->quickStore($request);
        }

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:product_types,name',
            'code' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        ProductType::create([
            'name' => trim((string) $data['name']),
            'code' => trim((string) ($data['code'] ?? '')) ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->back()->with('success', 'Product Type created successfully.');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));

        if ($rawName === '') {
            return response()->json([
                'success' => false,
                'message' => 'Product Type Name is required.',
                'errors' => [
                    'name' => ['Product Type Name is required.'],
                ],
            ], 422);
        }

        if (mb_strlen($rawName) > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Product Type Name cannot exceed 100 characters.',
                'errors' => [
                    'name' => ['Product Type Name cannot exceed 100 characters.'],
                ],
            ], 422);
        }

        // Case-insensitive, whitespace-trimmed duplicate check
        $exists = ProductType::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A product type with this name already exists.',
                'errors' => [
                    'name' => ['A product type with this name already exists.'],
                ],
            ], 422);
        }

        // Generate or clean code
        $rawCode = trim((string) $request->input('code', ''));
        if ($rawCode === '') {
            $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rawName));
            $baseCode = substr($baseCode ?: 'PT', 0, 10);
            $code = $baseCode;
            $suffix = 1;
            while (ProductType::where('code', $code)->exists()) {
                $code = substr($baseCode, 0, 8) . '_' . $suffix;
                $suffix++;
            }
        } else {
            $code = $rawCode;
        }

        $companyId = auth()->user()?->company_id ?? Company::defaultCompanyId() ?? null;

        try {
            $productType = ProductType::create([
                'company_id' => $companyId,
                'name' => $rawName,
                'code' => $code,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product Type created successfully.',
                'item' => [
                    'id' => $productType->id,
                    'name' => $productType->name,
                    'code' => $productType->code,
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
                    'message' => 'A product type with this name already exists.',
                    'errors' => [
                        'name' => ['A product type with this name already exists.'],
                    ],
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the product type: ' . $e->getMessage(),
            ], 500);
        }
    }
}

