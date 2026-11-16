<?php

namespace App\Http\Controllers\PhoneDirectory;

use App\Domains\PhoneDirectory\Models\PhoneDirectoryCategory;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryDepartment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhoneDirectoryDepartmentController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission('phone_directory.manage_departments');

        $departments = PhoneDirectoryDepartment::withCount('contacts')->orderBy('name')->get();
        $categories = PhoneDirectoryCategory::withCount('contacts')->orderBy('name')->get();

        return view('phone_directory.departments', compact('departments', 'categories'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('phone_directory.manage_departments');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:phone_directory_departments,name'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();

        $department = PhoneDirectoryDepartment::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'department' => $department,
                'message' => "Department '{$department->name}' created successfully.",
            ]);
        }

        return back()->with('success', "Department '{$department->name}' created successfully.");
    }

    public function update(Request $request, PhoneDirectoryDepartment $department): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('phone_directory.manage_departments');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:phone_directory_departments,name,' . $department->id],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['updated_by'] = auth()->id();

        $department->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'department' => $department,
                'message' => "Department '{$department->name}' updated successfully.",
            ]);
        }

        return back()->with('success', "Department '{$department->name}' updated successfully.");
    }

    public function destroy(PhoneDirectoryDepartment $department, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('phone_directory.manage_departments');

        if ($department->contacts()->count() > 0) {
            $msg = "Cannot delete department '{$department->name}' because it has {$department->contacts()->count()} linked contact(s).";
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $name = $department->name;
        $department->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Department '{$name}' deleted successfully.",
            ]);
        }

        return back()->with('success', "Department '{$name}' deleted successfully.");
    }

    public function storeCategory(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('phone_directory.manage_departments');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:phone_directory_categories,name'],
            'color' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['color'] = $validated['color'] ?: 'violet';
        $validated['created_by'] = auth()->id();

        $category = PhoneDirectoryCategory::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'category' => $category,
                'message' => "Category '{$category->name}' created successfully.",
            ]);
        }

        return back()->with('success', "Category '{$category->name}' created successfully.");
    }

    public function updateCategory(Request $request, PhoneDirectoryCategory $category): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('phone_directory.manage_departments');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:phone_directory_categories,name,' . $category->id],
            'color' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['updated_by'] = auth()->id();

        $category->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'category' => $category,
                'message' => "Category '{$category->name}' updated successfully.",
            ]);
        }

        return back()->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function destroyCategory(PhoneDirectoryCategory $category, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('phone_directory.manage_departments');

        if ($category->contacts()->count() > 0) {
            $msg = "Cannot delete category '{$category->name}' because it has {$category->contacts()->count()} linked contact(s).";
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $name = $category->name;
        $category->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$name}' deleted successfully.",
            ]);
        }

        return back()->with('success', "Category '{$name}' deleted successfully.");
    }

    protected function authorizePermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        if ($user->hasAnyRole(['super-admin', 'client-admin'])) {
            return;
        }

        $altPermission = str_replace('_', '-', $permission);
        if ($user->hasAnyPermission([$permission, $altPermission, 'phone_directory.manage', 'phone-directory.manage'])) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }
}
