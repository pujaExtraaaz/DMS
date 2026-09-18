<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Services\SerialBatchService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SerialLookupController extends Controller
{
    public function __construct(protected SerialBatchService $serialBatchService) {}

    public function index(Request $request): View
    {
        $serial = null;
        if ($request->filled('serial_number')) {
            $serial = $this->serialBatchService->findSerial(trim($request->string('serial_number')->toString()));
        }

        $recent = ProductSerial::query()
            ->with(['product', 'warehouse'])
            ->latest('id')
            ->limit(25)
            ->get();

        return view('inventory.serials.index', [
            'serial' => $serial,
            'query' => $request->string('serial_number')->toString(),
            'recent' => $recent,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function reserve(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'serial_numbers' => 'required|string',
            'owner_label' => 'required|string|max:100',
        ]);

        $this->serialBatchService->reserveSerials(
            $this->parseSerials($data['serial_numbers']),
            auth()->user(),
            $data['owner_label'],
        );

        return $this->flashSuccess('Serials reserved.', 'inventory.serials.index');
    }

    public function deliver(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'serial_numbers' => 'required|string',
        ]);

        $this->serialBatchService->deliverSerials($this->parseSerials($data['serial_numbers']));

        return $this->flashSuccess('Serials marked delivered/sold.', 'inventory.serials.index');
    }

    public function returnSerials(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'serial_numbers' => 'required|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $this->serialBatchService->returnSerials(
            $this->parseSerials($data['serial_numbers']),
            $data['warehouse_id'] ?? null,
        );

        return $this->flashSuccess('Serials returned to stock.', 'inventory.serials.index');
    }

    /**
     * @return array<int, string>
     */
    protected function parseSerials(string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', $raw) ?: [])
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
