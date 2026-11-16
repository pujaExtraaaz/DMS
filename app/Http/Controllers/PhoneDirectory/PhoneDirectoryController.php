<?php

namespace App\Http\Controllers\PhoneDirectory;

use App\Domains\PhoneDirectory\Models\PhoneDirectoryCategory;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryContact;
use App\Domains\PhoneDirectory\Models\PhoneDirectoryDepartment;
use App\Http\Controllers\Controller;
use App\Http\Requests\PhoneDirectory\StoreContactRequest;
use App\Http\Requests\PhoneDirectory\UpdateContactRequest;
use App\Support\IndianStates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PhoneDirectoryController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizePermission('phone_directory.view');

        // Dynamic summary stats (calculated from live database)
        $summary = [
            'total' => PhoneDirectoryContact::whereNull('archived_at')->count(),
            'employees' => PhoneDirectoryContact::whereNull('archived_at')->where('contact_type', 'employee')->count(),
            'customers' => PhoneDirectoryContact::whereNull('archived_at')->where('contact_type', 'customer')->count(),
            'suppliers' => PhoneDirectoryContact::whereNull('archived_at')->where('contact_type', 'supplier')->count(),
            'important' => PhoneDirectoryContact::whereNull('archived_at')->where('is_important', true)->count(),
            'archived' => PhoneDirectoryContact::whereNotNull('archived_at')->count(),
        ];

        // Filters
        $filters = [
            'search' => $request->query('search'),
            'contact_type' => $request->query('contact_type'),
            'department_id' => $request->query('department_id'),
            'designation' => $request->query('designation'),
            'company_name' => $request->query('company_name'),
            'state' => $request->query('state'),
            'city' => $request->query('city'),
            'status' => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'important' => $request->query('important'),
            'archive_view' => $request->query('archive_view', 'active'),
        ];

        // Query
        $query = PhoneDirectoryContact::query()
            ->with(['department', 'category'])
            ->filter($filters);

        // Sorting
        $allowedSorts = [
            'contact_name' => 'contact_name',
            'contact_type' => 'contact_type',
            'designation' => 'designation',
            'company_name' => 'company_name',
            'city' => 'city',
            'state' => 'state',
            'created_at' => 'created_at',
        ];
        $sort = $request->query('sort', 'contact_name');
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (isset($allowedSorts[$sort])) {
            $query->orderBy($allowedSorts[$sort], $direction);
        } else {
            $query->orderBy('contact_name', 'asc');
        }

        // Pagination
        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $contacts = $query->paginate($perPage)->withQueryString();

        // AJAX / JSON response
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'contacts' => $contacts,
                'summary' => $summary,
            ]);
        }

        // Dropdown options
        $departments = PhoneDirectoryDepartment::where('is_active', true)->orderBy('name')->get();
        $categories = PhoneDirectoryCategory::where('is_active', true)->orderBy('name')->get();
        $contactTypes = PhoneDirectoryContact::CONTACT_TYPES;
        $states = IndianStates::all();

        // Distinct companies and designations for filter autocomplete
        $companies = PhoneDirectoryContact::whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->distinct()
            ->orderBy('company_name')
            ->limit(50)
            ->pluck('company_name');

        $designations = PhoneDirectoryContact::whereNotNull('designation')
            ->where('designation', '!=', '')
            ->distinct()
            ->orderBy('designation')
            ->limit(50)
            ->pluck('designation');

        $cities = PhoneDirectoryContact::whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->limit(50)
            ->pluck('city');

        return view('phone_directory.index', compact(
            'contacts',
            'summary',
            'filters',
            'sort',
            'direction',
            'perPage',
            'departments',
            'categories',
            'contactTypes',
            'states',
            'companies',
            'designations',
            'cities'
        ));
    }

    public function create(): View
    {
        $this->authorizePermission('phone_directory.create');

        $contact = new PhoneDirectoryContact();
        $departments = PhoneDirectoryDepartment::where('is_active', true)->orderBy('name')->get();
        $categories = PhoneDirectoryCategory::where('is_active', true)->orderBy('name')->get();
        $contactTypes = PhoneDirectoryContact::CONTACT_TYPES;
        $states = IndianStates::all();

        return view('phone_directory.form', [
            'contact' => $contact,
            'isEdit' => false,
            'departments' => $departments,
            'categories' => $categories,
            'contactTypes' => $contactTypes,
            'states' => $states,
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('phone-directory/photos', 'public');
            $data['profile_photo'] = $path;
        }

        $data['is_important'] = $request->boolean('is_important');
        $data['status'] = $request->input('status', 'active');
        $data['country'] = $request->input('country', 'India');
        $data['created_by'] = auth()->id();

        $contact = PhoneDirectoryContact::create($data);

        return redirect()->route('phone-directory.index')
            ->with('success', "Contact '{$contact->contact_name}' created successfully.");
    }

    public function show(PhoneDirectoryContact $contact, Request $request): View|JsonResponse
    {
        $this->authorizePermission('phone_directory.view');

        $contact->load(['department', 'category', 'creator', 'updater']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'contact' => array_merge($contact->toArray(), [
                    'profile_photo_url' => $contact->profile_photo_url,
                    'initials' => $contact->initials,
                    'avatar_bg_class' => $contact->avatar_bg_class,
                    'clean_mobile' => $contact->clean_mobile,
                    'clean_whatsapp' => $contact->clean_whatsapp,
                    'formatted_location' => $contact->formatted_location,
                    'contact_type_label' => PhoneDirectoryContact::CONTACT_TYPES[$contact->contact_type] ?? ucfirst($contact->contact_type),
                    'department_name' => $contact->department?->name ?? '—',
                    'category_name' => $contact->category?->name ?? '—',
                ]),
            ]);
        }

        return view('phone_directory.show', compact('contact'));
    }

    public function edit(PhoneDirectoryContact $contact): View
    {
        $this->authorizePermission('phone_directory.edit');

        $departments = PhoneDirectoryDepartment::where('is_active', true)->orderBy('name')->get();
        $categories = PhoneDirectoryCategory::where('is_active', true)->orderBy('name')->get();
        $contactTypes = PhoneDirectoryContact::CONTACT_TYPES;
        $states = IndianStates::all();

        return view('phone_directory.form', [
            'contact' => $contact,
            'isEdit' => true,
            'departments' => $departments,
            'categories' => $categories,
            'contactTypes' => $contactTypes,
            'states' => $states,
        ]);
    }

    public function update(UpdateContactRequest $request, PhoneDirectoryContact $contact): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('remove_photo')) {
            if ($contact->profile_photo && Storage::disk('public')->exists($contact->profile_photo)) {
                Storage::disk('public')->delete($contact->profile_photo);
            }
            $data['profile_photo'] = null;
        } elseif ($request->hasFile('profile_photo')) {
            if ($contact->profile_photo && Storage::disk('public')->exists($contact->profile_photo)) {
                Storage::disk('public')->delete($contact->profile_photo);
            }
            $path = $request->file('profile_photo')->store('phone-directory/photos', 'public');
            $data['profile_photo'] = $path;
        }

        $data['is_important'] = $request->boolean('is_important');
        $data['status'] = $request->input('status', 'active');
        $data['updated_by'] = auth()->id();

        $contact->update($data);

        return redirect()->route('phone-directory.index')
            ->with('success', "Contact '{$contact->contact_name}' updated successfully.");
    }

    public function destroy(PhoneDirectoryContact $contact, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('phone_directory.delete');

        $name = $contact->contact_name;
        $contact->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Contact '{$name}' deleted successfully.",
            ]);
        }

        return redirect()->route('phone-directory.index')
            ->with('success', "Contact '{$name}' deleted successfully.");
    }

    public function archive(PhoneDirectoryContact $contact, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('phone_directory.archive');

        $isArchiving = is_null($contact->archived_at);
        $contact->archived_at = $isArchiving ? now() : null;
        $contact->updated_by = auth()->id();
        $contact->save();

        $message = $isArchiving
            ? "Contact '{$contact->contact_name}' has been archived."
            : "Contact '{$contact->contact_name}' has been restored.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_archived' => $isArchiving,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function toggleImportant(PhoneDirectoryContact $contact, Request $request): JsonResponse
    {
        $this->authorizePermission('phone_directory.edit');

        $contact->is_important = ! $contact->is_important;
        $contact->updated_by = auth()->id();
        $contact->save();

        return response()->json([
            'success' => true,
            'is_important' => (bool) $contact->is_important,
            'message' => $contact->is_important
                ? "Marked '{$contact->contact_name}' as important."
                : "Removed '{$contact->contact_name}' from important contacts.",
        ]);
    }

    public function toggleStatus(PhoneDirectoryContact $contact, Request $request): JsonResponse
    {
        $this->authorizePermission('phone_directory.edit');

        $contact->status = ($contact->status === 'active') ? 'inactive' : 'active';
        $contact->updated_by = auth()->id();
        $contact->save();

        return response()->json([
            'success' => true,
            'status' => $contact->status,
            'message' => "Contact status changed to {$contact->status}.",
        ]);
    }

    public function bulkAction(Request $request): RedirectResponse|JsonResponse
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            $msg = 'Please select at least one contact.';
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $count = count($ids);

        switch ($action) {
            case 'archive':
                $this->authorizePermission('phone_directory.archive');
                PhoneDirectoryContact::whereIn('id', $ids)->update([
                    'archived_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
                $msg = "{$count} contact(s) archived successfully.";
                break;

            case 'restore':
                $this->authorizePermission('phone_directory.archive');
                PhoneDirectoryContact::whereIn('id', $ids)->update([
                    'archived_at' => null,
                    'updated_by' => auth()->id(),
                ]);
                $msg = "{$count} contact(s) restored successfully.";
                break;

            case 'delete':
                $this->authorizePermission('phone_directory.delete');
                PhoneDirectoryContact::whereIn('id', $ids)->delete();
                $msg = "{$count} contact(s) deleted successfully.";
                break;

            case 'mark_important':
                $this->authorizePermission('phone_directory.edit');
                PhoneDirectoryContact::whereIn('id', $ids)->update([
                    'is_important' => true,
                    'updated_by' => auth()->id(),
                ]);
                $msg = "{$count} contact(s) marked as important.";
                break;

            case 'unmark_important':
                $this->authorizePermission('phone_directory.edit');
                PhoneDirectoryContact::whereIn('id', $ids)->update([
                    'is_important' => false,
                    'updated_by' => auth()->id(),
                ]);
                $msg = "{$count} contact(s) unmarked from important.";
                break;

            default:
                $msg = 'Invalid bulk action requested.';
                return $request->wantsJson() || $request->ajax()
                    ? response()->json(['success' => false, 'message' => $msg], 400)
                    : back()->with('error', $msg);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
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

        // Support both dot and dash notation, plus general manage
        $altPermission = str_replace('_', '-', $permission);
        if ($user->hasAnyPermission([$permission, $altPermission, 'phone_directory.manage', 'phone-directory.manage'])) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }
}
