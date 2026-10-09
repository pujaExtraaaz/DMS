<?php

namespace Tally\Inventory;

use Tally\Models\Company;
use Tally\Models\Product;
use Tally\Models\ProductBarcode;
use Illuminate\Validation\ValidationException;

class BarcodeDirectory
{
    /**
     * @return array{product: Product, increment: true}
     */
    public function lookup(Company $company, string $code): array
    {
        $code = trim($code);

        if ($code === '') {
            throw ValidationException::withMessages([
                'barcode' => 'Scan or enter a barcode.',
            ]);
        }

        $product = Product::query()
            ->where('company_id', $company->id)
            ->whereRaw('lower(barcode) = ?', [mb_strtolower($code)])
            ->first();

        if (! $product) {
            $extra = ProductBarcode::query()
                ->where('company_id', $company->id)
                ->whereRaw('lower(barcode) = ?', [mb_strtolower($code)])
                ->first();
            $product = $extra?->product;
        }

        if (! $product || $product->company_id !== $company->id) {
            throw ValidationException::withMessages([
                'barcode' => 'No product uses barcode '.$code.'.',
            ]);
        }

        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'barcode' => $product->name.' is inactive and cannot be used on a new transaction.',
            ]);
        }

        return ['product' => $product, 'increment' => true];
    }

    /**
     * @param  list<string>  $extras
     */
    public function sync(Product $product, array $extras): void
    {
        $codes = [];

        foreach ($extras as $extra) {
            $code = trim((string) $extra);

            if ($code === '' || strcasecmp($code, (string) $product->barcode) === 0) {
                continue;
            }

            $key = mb_strtolower($code);

            if (isset($codes[$key])) {
                throw ValidationException::withMessages([
                    'extra_barcodes' => 'Barcode '.$code.' is repeated.',
                ]);
            }

            $this->assertAvailable($product, $code);
            $codes[$key] = $code;
        }

        $product->barcodes()->delete();

        foreach ($codes as $code) {
            $product->barcodes()->create([
                'company_id' => $product->company_id,
                'barcode' => $code,
            ]);
        }
    }

    public function assertAvailable(Product $product, string $code): void
    {
        $takenOnProduct = Product::query()
            ->where('company_id', $product->company_id)
            ->where('id', '!=', $product->id)
            ->whereRaw('lower(barcode) = ?', [mb_strtolower($code)])
            ->exists();
        $takenOnExtra = ProductBarcode::query()
            ->where('company_id', $product->company_id)
            ->where('product_id', '!=', $product->id)
            ->whereRaw('lower(barcode) = ?', [mb_strtolower($code)])
            ->exists();

        if ($takenOnProduct || $takenOnExtra) {
            throw ValidationException::withMessages([
                'barcode' => 'Barcode '.$code.' is already used in this company.',
            ]);
        }
    }
}
