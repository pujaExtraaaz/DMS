<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Inventory\BarcodeDirectory;
use Tally\Inventory\Code128;
use Tally\Models\Product;
use Tally\Preferences\PreferenceStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarcodeController extends Controller
{
    public function lookup(Request $request, WorkspaceContext $context, BarcodeDirectory $barcodes, PreferenceStore $preferences): JsonResponse
    {
        $company = $context->company();
        abort_if($company === null, 404);
        $found = $barcodes->lookup($company, (string) $request->query('code', ''));

        return response()->json([
            'product_id' => $found['product']->id,
            'name' => $found['product']->name,
            'code' => $found['product']->code,
            'rate' => (string) $found['product']->sales_rate,
            'increment' => (bool) $preferences->get($company, null, 'inventory.repeat_scan_increments'),
        ]);
    }

    public function print(Product $product): View
    {
        $codes = array_values(array_filter(array_merge(
            [$product->barcode],
            $product->barcodes()->pluck('barcode')->all(),
        )));

        return view('tally::products.barcode', [
            'product' => $product,
            'codes' => array_map(fn (string $code) => [
                'code' => $code,
                'svg' => (new Code128)->svg($code),
            ], $codes),
        ]);
    }
}
