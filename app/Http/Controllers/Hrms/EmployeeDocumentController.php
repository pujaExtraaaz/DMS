<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\EmployeeDocument;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'document_type' => 'required|string|max:80',
            'document_number' => 'nullable|string|max:100',
            'issued_on' => 'nullable|date',
            'expires_on' => 'nullable|date|after_or_equal:issued_on',
            'notes' => 'nullable|string',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $path = $request->file('document_file')->store('employee_documents/' . $employee->id, 'local');

        $doc = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'] ?? null,
            'issued_on' => $data['issued_on'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
            'notes' => $data['notes'] ?? null,
            'file_path' => $path,
        ]);

        $this->auditLogService->record($doc, 'created');

        return $this->flashSuccess('Document uploaded successfully.', 'hrms.employees.show', ['employee' => $employee]);
    }

    public function download(EmployeeDocument $document): StreamedResponse|RedirectResponse
    {
        $user = auth()->user();
        $isOwner = $user && $document->employee?->user_id === $user->id;
        $hasPermission = $user && ($user->hasRole('super-admin') || $user->can('hrms.view') || $user->can('hrms.manage'));

        if (! $isOwner && ! $hasPermission) {
            abort(403, 'Unauthorized access to employee document.');
        }

        if (! $document->file_path || ! Storage::disk('local')->exists($document->file_path)) {
            return $this->flashError('Document file not found on disk.');
        }

        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);
        $fileName = str($document->employee->name . '_' . $document->document_type)
            ->slug('_')
            ->append('.' . $extension);

        return Storage::disk('local')->download($document->file_path, (string) $fileName);
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        $employee = $document->employee;

        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();
        $this->auditLogService->record($document, 'deleted');

        return $this->flashSuccess('Document deleted successfully.', 'hrms.employees.show', ['employee' => $employee]);
    }
}

