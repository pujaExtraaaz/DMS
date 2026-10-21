<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\CodeGenerator;
use App\Support\TitleCase;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductTypeController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View|JsonResponse
    {
        if ($request->routeIs('masters.product-types.list')) {
            return $this->list();
        }

        $query = ProductType::query()
            ->withCount('products')
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'code', 'description']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'code', 'description', 'is_active', 'products_count', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        $totalCount = ProductType::count();
        $activeCount = ProductType::where('is_active', true)->count();
        $inactiveCount = ProductType::where('is_active', false)->count();

        return view('masters.product-types.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'previewCode' => CodeGenerator::forProductType(),
        ]);
    }

    public function list(): JsonResponse
    {
        return response()->json([
            'items' => ProductType::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'description', 'is_active']),
        ]);
    }

    public function create(): View
    {
        return view('masters.product-types.form', [
            'item' => new ProductType(['is_active' => true]),
            'previewCode' => CodeGenerator::forProductType(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));

        if ($rawName === '') {
            $msg = 'Product Type Name is required.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        if (mb_strlen($rawName) > 100) {
            $msg = 'Product Type Name cannot exceed 100 characters.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        // Case-insensitive, whitespace-trimmed duplicate check
        $exists = ProductType::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])->exists();
        if ($exists) {
            $msg = 'A product type with this name already exists.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        $formattedName = TitleCase::format($rawName);

        // Generate or clean code
        $rawCode = trim((string) $request->input('code', ''));
        if ($rawCode === '') {
            $code = CodeGenerator::forProductType();
        } else {
            $code = strtoupper($rawCode);
        }

        $companyId = auth()->user()?->company_id ?? Company::defaultCompanyId() ?? null;
        $description = trim((string) $request->input('description', '')) ?: null;
        $isActive = $request->boolean('is_active', true);

        try {
            $productType = ProductType::create([
                'company_id' => $companyId,
                'name' => $formattedName,
                'code' => $code,
                'description' => $description,
                'is_active' => $isActive,
            ]);

            $successMsg = "Product Type '{$formattedName}' created successfully.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'item' => [
                        'id' => $productType->id,
                        'name' => $productType->name,
                        'code' => $productType->code,
                        'description' => $productType->description,
                        'is_active' => $productType->is_active,
                        'created_at' => $productType->created_at?->format('d M Y'),
                    ],
                ], 201);
            }

            return redirect()->route('masters.product-types.index')->with('success', $successMsg);
        } catch (\Throwable $e) {
            if (
                str_contains($e->getMessage(), 'Integrity constraint violation') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint failed')
            ) {
                $msg = 'A product type with this name or code already exists.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                        'errors' => ['name' => [$msg]],
                    ], 422);
                }
                return back()->withInput()->withErrors(['name' => $msg]);
            }

            report($e);
            $msg = 'An error occurred while saving the product type: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return back()->withInput()->with('error', $msg);
        }
    }

    public function show(Request $request, ProductType $productType): View|JsonResponse
    {
        $productType->loadCount('products');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'item' => [
                    'id' => $productType->id,
                    'name' => $productType->name,
                    'code' => $productType->code,
                    'description' => $productType->description,
                    'is_active' => $productType->is_active,
                    'products_count' => $productType->products_count,
                    'created_at' => $productType->created_at?->format('d M Y, h:i A'),
                    'updated_at' => $productType->updated_at?->format('d M Y, h:i A'),
                ],
            ]);
        }

        return view('masters.product-types.form', [
            'item' => $productType,
            'readOnly' => true,
        ]);
    }

    public function edit(ProductType $productType): View
    {
        return view('masters.product-types.form', [
            'item' => $productType,
            'previewCode' => $productType->code,
        ]);
    }

    public function update(Request $request, ProductType $productType): RedirectResponse|JsonResponse
    {
        $rawName = trim((string) $request->input('name', ''));

        if ($rawName === '') {
            $msg = 'Product Type Name is required.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        if (mb_strlen($rawName) > 100) {
            $msg = 'Product Type Name cannot exceed 100 characters.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        // Duplicate check excluding current
        $exists = ProductType::where('id', '!=', $productType->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])
            ->exists();

        if ($exists) {
            $msg = 'A product type with this name already exists.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return back()->withInput()->withErrors(['name' => $msg]);
        }

        $formattedName = TitleCase::format($rawName);
        $description = trim((string) $request->input('description', '')) ?: null;
        $isActive = $request->boolean('is_active', true);

        try {
            $productType->update([
                'name' => $formattedName,
                'description' => $description,
                'is_active' => $isActive,
            ]);

            $successMsg = "Product Type '{$formattedName}' updated successfully.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'item' => [
                        'id' => $productType->id,
                        'name' => $productType->name,
                        'code' => $productType->code,
                        'description' => $productType->description,
                        'is_active' => $productType->is_active,
                        'updated_at' => $productType->updated_at?->format('d M Y'),
                    ],
                ]);
            }

            return redirect()->route('masters.product-types.index')->with('success', $successMsg);
        } catch (\Throwable $e) {
            report($e);
            $msg = 'An error occurred while updating the product type: ' . $e->getMessage();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }
            return back()->withInput()->with('error', $msg);
        }
    }

    public function destroy(Request $request, ProductType $productType): RedirectResponse|JsonResponse
    {
        $typeName = $productType->name;

        try {
            // Check if any products reference this product type (by ID or legacy name)
            $hasProducts = $productType->products()->exists() ||
                Product::where('product_type_id', $productType->id)
                    ->orWhereRaw('LOWER(TRIM(product_type)) = ?', [mb_strtolower(trim($productType->name))])
                    ->exists();

            if ($hasProducts) {
                // Safe deactivation to preserve product catalog integrity and reporting
                $productType->update(['is_active' => false]);

                $message = "Product Type '{$typeName}' is assigned to existing products and has been deactivated to preserve product records.";

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'archived' => true,
                    ]);
                }

                return redirect()->route('masters.product-types.index')->with('success', $message);
            }

            // Safe permanent delete when no products reference this type
            $productType->delete();

            $message = "Product Type '{$typeName}' deleted successfully.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'archived' => false,
                ]);
            }

            return redirect()->route('masters.product-types.index')->with('success', $message);
        } catch (\Throwable $e) {
            report($e);
            $msg = 'Failed to delete product type: ' . $e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }

            return redirect()->route('masters.product-types.index')->with('error', $msg);
        }
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

        $formattedName = TitleCase::format($rawName);

        // Generate or clean code
        $rawCode = trim((string) $request->input('code', ''));
        if ($rawCode === '') {
            $code = CodeGenerator::forProductType();
        } else {
            $code = strtoupper($rawCode);
        }

        $companyId = auth()->user()?->company_id ?? Company::defaultCompanyId() ?? null;

        try {
            $productType = ProductType::create([
                'company_id' => $companyId,
                'name' => $formattedName,
                'code' => $code,
                'description' => trim((string) $request->input('description', '')) ?: null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product Type created successfully.',
                'item' => [
                    'id' => $productType->id,
                    'name' => $productType->name,
                    'code' => $productType->code,
                    'description' => $productType->description,
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
