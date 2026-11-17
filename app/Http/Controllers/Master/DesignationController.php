<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Designation;
use App\Domains\Master\Models\PartyContact;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DesignationController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Designation::query()
            ->withCount(['partyContacts', 'employees'])
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
            ['name', 'code', 'is_active', 'party_contacts_count', 'employees_count', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('masters.designations.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('masters.designations.form', [
            'item' => new Designation(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return $this->quickStore($request);
        }

        $name = trim((string) $request->input('name', ''));
        $code = trim((string) $request->input('code', ''));
        $description = trim((string) $request->input('description', ''));
        $isActive = $request->boolean('is_active', true);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:1000',
        ]);

        // Duplicate check (case-insensitive)
        $exists = Designation::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['name' => 'A designation with this name already exists.']);
        }

        if ($code === '') {
            $code = $this->generateUniqueCode($name);
        } else {
            // Check code uniqueness
            if (Designation::whereRaw('LOWER(TRIM(code)) = ?', [mb_strtolower($code)])->exists()) {
                return back()->withInput()->withErrors(['code' => 'A designation with this code already exists.']);
            }
        }

        Designation::create([
            'name' => $name,
            'code' => $code,
            'description' => $description ?: null,
            'is_active' => $isActive,
        ]);

        return $this->flashSuccess('Designation created successfully.', 'masters.designations.index');
    }

    public function edit(Designation $designation): View
    {
        return view('masters.designations.form', [
            'item' => $designation,
        ]);
    }

    public function update(Request $request, Designation $designation): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return $this->quickUpdate($request, $designation);
        }

        $name = trim((string) $request->input('name', ''));
        $code = trim((string) $request->input('code', ''));
        $description = trim((string) $request->input('description', ''));
        $isActive = $request->boolean('is_active', true);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:1000',
        ]);

        // Duplicate check (case-insensitive) excluding self
        $exists = Designation::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->where('id', '!=', $designation->id)
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['name' => 'A designation with this name already exists.']);
        }

        if ($code === '') {
            $code = $designation->code ?: $this->generateUniqueCode($name, $designation->id);
        } else {
            $codeExists = Designation::whereRaw('LOWER(TRIM(code)) = ?', [mb_strtolower($code)])
                ->where('id', '!=', $designation->id)
                ->exists();
            if ($codeExists) {
                return back()->withInput()->withErrors(['code' => 'A designation with this code already exists.']);
            }
        }

        DB::transaction(function () use ($designation, $name, $code, $description, $isActive) {
            $designation->update([
                'name' => $name,
                'code' => $code,
                'description' => $description ?: null,
                'is_active' => $isActive,
            ]);

            // Sync updated name to party contacts assigned to this designation
            PartyContact::where('designation_id', $designation->id)->update(['role' => $name]);
        });

        return $this->flashSuccess('Designation updated successfully.', 'masters.designations.index');
    }

    public function destroy(Request $request, Designation $designation): RedirectResponse|JsonResponse
    {
        $partyContactCount = $designation->partyContacts()->count();
        $employeeCount = $designation->employees()->count();

        // Check if in use
        if ($partyContactCount > 0 || $employeeCount > 0) {
            $inUseMsg = "Cannot delete designation '{$designation->name}' because it is currently assigned to {$partyContactCount} party contact(s) and {$employeeCount} employee(s). You can deactivate it instead.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'in_use' => true,
                    'message' => $inUseMsg,
                    'can_deactivate' => true,
                ], 422);
            }

            return $this->flashError($inUseMsg);
        }

        $designation->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Designation deleted successfully.',
            ]);
        }

        return $this->flashSuccess('Designation deleted successfully.', 'masters.designations.index');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return response()->json([
                'success' => false,
                'message' => 'Designation Name is required.',
            ], 422);
        }

        if (mb_strlen($name) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Designation Name cannot exceed 255 characters.',
            ], 422);
        }

        // Case-insensitive duplicate check
        $exists = Designation::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A designation with this name already exists.',
            ], 422);
        }

        $code = trim((string) $request->input('code', ''));
        if ($code === '') {
            $code = $this->generateUniqueCode($name);
        }

        try {
            $designation = Designation::create([
                'name' => $name,
                'code' => $code,
                'description' => trim((string) $request->input('description', '')) ?: null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Designation created successfully.',
                'item' => [
                    'id' => $designation->id,
                    'name' => $designation->name,
                    'code' => $designation->code,
                    'is_active' => $designation->is_active,
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
                    'message' => 'A designation with this name already exists.',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the designation: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function quickUpdate(Request $request, Designation $designation): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return response()->json([
                'success' => false,
                'message' => 'Designation Name is required.',
            ], 422);
        }

        if (mb_strlen($name) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Designation Name cannot exceed 255 characters.',
            ], 422);
        }

        // Case-insensitive duplicate check excluding this record
        $exists = Designation::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->where('id', '!=', $designation->id)
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A designation with this name already exists.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($designation, $name, $request) {
                $code = trim((string) $request->input('code', '')) ?: $designation->code;
                $designation->update([
                    'name' => $name,
                    'code' => $code,
                    'description' => $request->has('description') ? (trim((string) $request->input('description', '')) ?: null) : $designation->description,
                ]);

                // Sync party contacts role string
                PartyContact::where('designation_id', $designation->id)->update(['role' => $name]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Designation updated successfully.',
                'item' => [
                    'id' => $designation->id,
                    'name' => $designation->name,
                    'code' => $designation->code,
                    'is_active' => $designation->is_active,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the designation: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function quickDestroy(Request $request, Designation $designation): JsonResponse
    {
        $partyContactCount = $designation->partyContacts()->count();
        $employeeCount = $designation->employees()->count();

        if ($partyContactCount > 0 || $employeeCount > 0) {
            return response()->json([
                'success' => false,
                'in_use' => true,
                'message' => "Cannot delete designation '{$designation->name}' because it is currently assigned to {$partyContactCount} party contact(s) and {$employeeCount} employee(s). You can deactivate it instead.",
                'can_deactivate' => true,
            ], 422);
        }

        try {
            $designation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Designation deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the designation: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleActive(Request $request, Designation $designation): JsonResponse|RedirectResponse
    {
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : ! $designation->is_active;

        $designation->update(['is_active' => $isActive]);

        $statusText = $isActive ? 'activated' : 'deactivated';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Designation {$statusText} successfully.",
                'item' => [
                    'id' => $designation->id,
                    'name' => $designation->name,
                    'code' => $designation->code,
                    'is_active' => $designation->is_active,
                ],
            ]);
        }

        return $this->flashSuccess("Designation {$statusText} successfully.", 'masters.designations.index');
    }

    public function list(Request $request): JsonResponse
    {
        $query = Designation::query()
            ->when(! $request->boolean('all'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name');

        $items = $query->get(['id', 'name', 'code', 'is_active']);

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    protected function generateUniqueCode(string $name, ?int $ignoreId = null): string
    {
        $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        if ($baseCode === '') {
            $baseCode = 'DESIG';
        }
        $baseCode = substr($baseCode, 0, 15);
        $code = $baseCode;
        $suffix = 1;

        while (
            Designation::where('code', $code)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $code = substr($baseCode, 0, 14) . '_' . $suffix;
            $suffix++;
        }

        return $code;
    }
}
