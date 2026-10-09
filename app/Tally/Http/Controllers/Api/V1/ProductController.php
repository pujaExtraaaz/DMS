<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Http\Requests\ProductRequest;
use Tally\Models\Company;
use Tally\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $query = $company->products()->with(['primaryUnit', 'integrationReference'])->orderBy('name');
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('barcode', 'like', $like);
            });
        }

        return $this->page($query->paginate($this->perPage($request)), fn (Product $product) => $this->transform($product));
    }

    public function store(ProductRequest $request, Company $company): JsonResponse
    {
        $product = $company->products()->create($request->validated());
        $this->rememberReference($request, $product, $company);

        return $this->data($this->transform($product), 201);
    }

    public function show(Company $company, Product $product): JsonResponse
    {
        $product->load(['primaryUnit', 'productGroup', 'integrationReference']);

        return $this->data($this->transform($product));
    }

    public function update(ProductRequest $request, Company $company, Product $product): JsonResponse
    {
        $product->update($request->validated());
        $this->rememberReference($request, $product, $company);

        return $this->data($this->transform($product));
    }

    public function destroy(Company $company, Product $product): JsonResponse
    {
        if (method_exists($product, 'canBeDeleted') && ! $product->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->denyJson($product, 'This product cannot be deleted.');
        }

        $product->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Product $product): array
    {
        return $this->withIntegration($product, [
            'id' => $product->id,
            'company_id' => $product->company_id,
            'name' => $product->name,
            'code' => $product->code,
            'barcode' => $product->barcode,
            'barcodes' => $product->barcodes()->pluck('barcode')->all(),
            'primary_unit_id' => $product->primary_unit_id,
            'product_group_id' => $product->product_group_id,
            'opening_quantity' => (string) $product->opening_quantity,
            'is_active' => $product->is_active,
        ]);
    }
}
