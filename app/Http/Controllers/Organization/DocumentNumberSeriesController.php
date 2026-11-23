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
                'padding' => 4,
                'starting_number' => 1,
                'prefix_zero' => true,
                'numbering_method' => DocumentNumberSeries::METHOD_AUTOMATIC,
                'restart_each_fy' => true,
                'is_active' => true,
                'document_type' => 'PO',
                'restart_rules' => [],
                'prefix_rules' => [],
                'suffix_rules' => [],
            ]),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'documentTypes' => DocumentNumberSeries::documentTypes(),
            'numberingMethods' => DocumentNumberSeries::numberingMethods(),
            'periodicities' => DocumentNumberSeries::periodicities(),
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
            'numberingMethods' => DocumentNumberSeries::numberingMethods(),
            'periodicities' => DocumentNumberSeries::periodicities(),
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
            'starting_number' => 'nullable|integer|min:1',
            'prefix_zero' => 'nullable',
            'numbering_method' => ['required', 'string', Rule::in(array_keys(DocumentNumberSeries::numberingMethods()))],
            'restart_each_fy' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'restart_rules' => 'nullable|array',
            'prefix_rules' => 'nullable|array',
            'suffix_rules' => 'nullable|array',
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
        $data['starting_number'] = max(1, (int) ($request->input('starting_number') ?: 1));
        // Accept 1/0/true/false/"true"/"false" from Alpine or native selects.
        $rawPrefixZero = $request->input('prefix_zero', true);
        $data['prefix_zero'] = filter_var($rawPrefixZero, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($data['prefix_zero'] === null) {
            $data['prefix_zero'] = true;
        }

        $data['restart_rules'] = $this->cleanAndValidateRules($request->input('restart_rules', []), 'restart_rules');
        $data['prefix_rules'] = $this->cleanAndValidateRules($request->input('prefix_rules', []), 'prefix_rules');
        $data['suffix_rules'] = $this->cleanAndValidateRules($request->input('suffix_rules', []), 'suffix_rules');

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

    protected function cleanAndValidateRules(?array $rules, string $attribute): ?array
    {
        if (empty($rules) || ! is_array($rules)) {
            return null;
        }

        $cleaned = [];
        foreach ($rules as $index => $rule) {
            $from = ! empty($rule['applicable_from']) ? \Illuminate\Support\Carbon::parse($rule['applicable_from'])->toDateString() : null;
            $to = ! empty($rule['applicable_to']) ? \Illuminate\Support\Carbon::parse($rule['applicable_to'])->toDateString() : null;

            if (! $from) {
                continue;
            }

            if ($to && $to < $from) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "{$attribute}.{$index}.applicable_to" => 'Applicable To must not be earlier than Applicable From.',
                ]);
            }

            $entry = [
                'applicable_from' => $from,
                'applicable_to' => $to,
            ];

            if ($attribute === 'restart_rules') {
                $entry['starting_number'] = max(1, (int) ($rule['starting_number'] ?? 1));
                $periodicity = strtolower(trim((string) ($rule['periodicity'] ?? 'yearly')));
                if (! in_array($periodicity, ['daily', 'weekly', 'monthly', 'yearly', 'never'], true)) {
                    $periodicity = 'yearly';
                }
                $entry['periodicity'] = $periodicity;
            } elseif ($attribute === 'prefix_rules') {
                $entry['prefix'] = strtoupper(trim((string) ($rule['prefix'] ?? '')));
            } elseif ($attribute === 'suffix_rules') {
                $entry['suffix'] = trim((string) ($rule['suffix'] ?? ''));
            }

            $cleaned[] = $entry;
        }

        if (empty($cleaned)) {
            return null;
        }

        // Validate no overlapping date ranges
        $count = count($cleaned);
        for ($i = 0; $i < $count; $i++) {
            $aFrom = $cleaned[$i]['applicable_from'];
            $aTo = $cleaned[$i]['applicable_to'] ?? '9999-12-31';

            for ($j = $i + 1; $j < $count; $j++) {
                $bFrom = $cleaned[$j]['applicable_from'];
                $bTo = $cleaned[$j]['applicable_to'] ?? '9999-12-31';

                $overlapStart = max($aFrom, $bFrom);
                $overlapEnd = min($aTo, $bTo);

                if ($overlapStart <= $overlapEnd) {
                    $label = match ($attribute) {
                        'restart_rules' => 'Restart numbering',
                        'prefix_rules' => 'Prefix details',
                        'suffix_rules' => 'Suffix details',
                        default => $attribute,
                    };

                    throw \Illuminate\Validation\ValidationException::withMessages([
                        $attribute => "{$label} date ranges must not overlap: {$aFrom} to ".($cleaned[$i]['applicable_to'] ?? 'open')." conflicts with {$bFrom} to ".($cleaned[$j]['applicable_to'] ?? 'open').'.',
                    ]);
                }
            }
        }

        return $cleaned;
    }
}
