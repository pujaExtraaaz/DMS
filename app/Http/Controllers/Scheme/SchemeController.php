<?php

namespace App\Http\Controllers\Scheme;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Product;
use App\Domains\Scheme\Models\Scheme;
use App\Domains\Scheme\Services\SchemeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SchemeController extends Controller
{
    public function __construct(
        protected SchemeService $schemeService,
    ) {}

    public function index(Request $request): View
    {
        $items = Scheme::query()
            ->with('brand')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('schemes.index', compact('items'));
    }

    public function create(): View
    {
        return view('schemes.form', [
            'item' => new Scheme([
                'status' => 'draft',
                'basis' => 'value',
                'net_credit_notes' => true,
                'starts_on' => now()->toDateString(),
                'ends_on' => now()->endOfMonth()->toDateString(),
            ]),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:40|unique:schemes,code',
            'name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'status' => 'nullable|in:draft,active,closed,cancelled',
            'basis' => 'required|in:quantity,value',
            'net_credit_notes' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'slabs' => 'nullable|array',
            'slabs.*.from_value' => 'required_with:slabs|numeric|min:0',
            'slabs.*.to_value' => 'nullable|numeric|min:0',
            'slabs.*.benefit_percent' => 'nullable|numeric|min:0',
            'slabs.*.benefit_amount' => 'nullable|numeric|min:0',
        ]);

        $validated['net_credit_notes'] = $request->boolean('net_credit_notes');
        $scheme = $this->schemeService->create($validated, $request->user());

        return $this->flashSuccess('Scheme created.', 'schemes.show', ['scheme' => $scheme]);
    }

    public function show(Scheme $scheme): View
    {
        $scheme->load([
            'brand',
            'slabs',
            'products.product',
            'achievements.customer',
            'achievements.invoice',
            'settlements.customer',
        ]);

        return view('schemes.show', ['item' => $scheme]);
    }

    public function activate(Scheme $scheme): RedirectResponse
    {
        if (! in_array($scheme->status, ['draft', 'cancelled'], true)) {
            return $this->flashError('Only draft schemes can be activated.');
        }

        $scheme->update(['status' => 'active']);

        return $this->flashSuccess('Scheme activated.');
    }

    public function finalize(Scheme $scheme): RedirectResponse
    {
        try {
            $this->schemeService->finalizeScheme($scheme);
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Scheme finalized from provisional snapshots.');
    }
}
