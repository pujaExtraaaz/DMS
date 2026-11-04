<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\DocumentNumberSeries;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentNumberSeriesController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = DocumentNumberSeries::query()->with('company');

        $this->applySearch($query, $request->input('search'), [
            'name',
            'document_type',
            'prefix',
            'suffix',
        ], [
            'company' => ['name'],
        ]);

        [$sort, $direction] = $this->applySorting(
            $query,
            $request,
            [
                'document_type' => 'document_type',
                'prefix' => 'prefix',
                'numbering_method' => 'numbering_method',
                'is_active' => 'is_active',
                'name' => 'name',
                'company' => function ($q, $dir) {
                    $q->leftJoin('companies as c_dns', 'document_number_series.company_id', '=', 'c_dns.id')
                        ->orderBy('c_dns.name', $dir)
                        ->select('document_number_series.*');
                },
            ],
            'document_type',
            'asc'
        );

        $items = $query->paginate(20)->withQueryString();

        return view('organization.document-number-series.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'sort' => $sort,
            'direction' => $direction,
            'documentTypes' => DocumentNumberSeries::documentTypes(),
        ]);
    }

    public function create(): View
    {
        return view('organization.document-number-series.form', [
            'item' => new DocumentNumberSeries([
                'prefix' => 'AVITPO',
                'include_financial_year' => true,
                'financial_year_style' => 'yy_yy',
                'separator' => '-',
                'padding' => 3,
                'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
                'restart_each_fy' => true,
                'is_active' => true,
                'document_type' => 'PO',
            ]),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'documentTypes' => DocumentNumberSeries::documentTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DocumentNumberSeries::create($data);

        return $this->flashSuccess('Voucher numbering series created.', 'organization.document-number-series.index');
    }

    public function edit(DocumentNumberSeries $document_number_series): View
    {
        return view('organization.document-number-series.form', [
            'item' => $document_number_series,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'documentTypes' => DocumentNumberSeries::documentTypes(),
        ]);
    }

    public function update(Request $request, DocumentNumberSeries $document_number_series): RedirectResponse
    {
        $data = $this->validated($request, $document_number_series);
        $document_number_series->update($data);

        return $this->flashSuccess('Voucher numbering series updated.', 'organization.document-number-series.index');
    }

    public function destroy(DocumentNumberSeries $document_number_series): RedirectResponse
    {
        $document_number_series->delete();

        return $this->flashSuccess('Voucher numbering series deleted.', 'organization.document-number-series.index');
    }

    protected function validated(Request $request, ?DocumentNumberSeries $existing = null): array
    {
        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'document_type' => ['required', 'string', 'max:40', Rule::in(array_keys(DocumentNumberSeries::documentTypes()))],
            'name' => 'nullable|string|max:120',
            'prefix' => 'nullable|string|max:40',
            'suffix' => 'nullable|string|max:40',
            'include_financial_year' => 'nullable|boolean',
            'financial_year_style' => ['required', 'string', Rule::in(['yy_yy', 'YYYY-YY', 'name'])],
            'separator' => 'nullable|string|max:5',
            'padding' => 'required|integer|min:1|max:8',
            'numbering_method' => ['required', 'string', Rule::in(['automatic', 'manual'])],
            'restart_each_fy' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data['include_financial_year'] = $request->boolean('include_financial_year');
        $data['restart_each_fy'] = $request->boolean('restart_each_fy');
        $data['is_active'] = $request->boolean('is_active');
        $data['company_id'] = $data['company_id'] ?: null;
        $data['prefix'] = strtoupper(trim((string) ($data['prefix'] ?? '')));
        $data['suffix'] = ($data['suffix'] ?? null) !== null && $data['suffix'] !== ''
            ? trim((string) $data['suffix'])
            : null;
        $data['separator'] = $data['separator'] ?? '-';
        $data['name'] = $data['name'] ?: (DocumentNumberSeries::documentTypes()[$data['document_type']] ?? $data['document_type']);

        $duplicate = DocumentNumberSeries::query()
            ->where('document_type', $data['document_type'])
            ->when(
                $data['company_id'],
                fn ($q) => $q->where('company_id', $data['company_id']),
                fn ($q) => $q->whereNull('company_id')
            )
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();

        if ($duplicate) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'document_type' => 'A numbering series already exists for this document type and company.',
            ]);
        }

        return $data;
    }
}
