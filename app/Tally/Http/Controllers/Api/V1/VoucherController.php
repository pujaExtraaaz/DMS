<?php

namespace Tally\Http\Controllers\Api\V1;

use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends ApiController
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $query = $company->vouchers()->with('integrationReference')->orderByDesc('voucher_date')->orderByDesc('id');

        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->string('voucher_type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('financial_year_id')) {
            $query->where('financial_year_id', $request->integer('financial_year_id'));
        }

        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($like) {
                $query->where('voucher_number', 'like', $like)
                    ->orWhere('narration', 'like', $like)
                    ->orWhere('reference_number', 'like', $like);
            });
        }

        return $this->page($query->paginate($this->perPage($request)), fn (Voucher $voucher) => $this->transform($voucher));
    }

    public function store(Request $request, Company $company, VoucherEngine $engine): JsonResponse
    {
        $data = $this->validateVoucher($request, $company);
        $branch = Branch::query()->whereKey($data['branch_id'])->firstOrFail();
        $year = FinancialYear::query()->whereKey($data['financial_year_id'])->firstOrFail();
        $voucher = $engine->save(
            $company,
            $branch,
            $year,
            $request->user(),
            VoucherType::from($data['voucher_type']),
            $data,
            (bool) ($data['post'] ?? false),
        );
        $this->rememberReference($request, $voucher, $company);

        return $this->data($this->transform($voucher->load('entries')), 201);
    }

    public function show(Company $company, Voucher $voucher): JsonResponse
    {
        $voucher->load(['entries.ledger', 'integrationReference']);

        return $this->data($this->transform($voucher, true));
    }

    public function update(Request $request, Company $company, Voucher $voucher, VoucherEngine $engine): JsonResponse
    {
        $data = $this->validateVoucher($request, $company, false);
        $saved = $engine->save(
            $company,
            $voucher->branch,
            $voucher->financialYear,
            $request->user(),
            $voucher->voucher_type,
            $data,
            (bool) ($data['post'] ?? false),
            $voucher,
        );

        return $this->data($this->transform($saved->load('entries'), true));
    }

    public function post(Company $company, Voucher $voucher, VoucherEngine $engine): JsonResponse
    {
        return $this->data($this->transform($engine->post($voucher)->load('entries'), true));
    }

    public function cancel(Company $company, Voucher $voucher, VoucherEngine $engine): JsonResponse
    {
        return $this->data($this->transform($engine->cancel($voucher), true));
    }

    public function destroy(Company $company, Voucher $voucher, VoucherEngine $engine): JsonResponse
    {
        $engine->deleteDraft($voucher);

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVoucher(Request $request, Company $company, bool $creating = true): array
    {
        $rules = [
            'branch_id' => [$creating ? 'required' : 'sometimes', 'integer', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'financial_year_id' => [$creating ? 'required' : 'sometimes', 'integer', Rule::exists('financial_years', 'id')->where('company_id', $company->id)],
            'voucher_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'narration' => ['nullable', 'string', 'max:1000'],
            'post' => ['sometimes', 'boolean'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.ledger_id' => ['required', 'integer', Rule::exists('ledgers', 'id')->where('company_id', $company->id)],
            'entries.*.debit' => ['nullable', 'numeric', 'min:0'],
            'entries.*.credit' => ['nullable', 'numeric', 'min:0'],
            'entries.*.narration' => ['nullable', 'string', 'max:255'],
            'entries.*.reference' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:80'],
            'external_reference_id' => ['nullable', 'string', 'max:255'],
            'sync_status' => ['nullable', 'in:pending,synced,failed'],
        ];

        if ($creating) {
            $rules['voucher_type'] = ['required', Rule::enum(VoucherType::class)];
        }

        return $request->validate($rules);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Voucher $voucher, bool $withEntries = false): array
    {
        $payload = $this->withIntegration($voucher, [
            'id' => $voucher->id,
            'company_id' => $voucher->company_id,
            'branch_id' => $voucher->branch_id,
            'financial_year_id' => $voucher->financial_year_id,
            'voucher_type' => $voucher->voucher_type->value,
            'voucher_number' => $voucher->voucher_number,
            'voucher_date' => $voucher->voucher_date?->toDateString(),
            'reference_number' => $voucher->reference_number,
            'narration' => $voucher->narration,
            'status' => $voucher->status->value,
            'total_debit' => (string) $voucher->total_debit,
            'total_credit' => (string) $voucher->total_credit,
        ]);

        if ($withEntries) {
            $payload['entries'] = $voucher->entries->map(fn ($entry) => [
                'id' => $entry->id,
                'line_number' => $entry->line_number,
                'ledger_id' => $entry->ledger_id,
                'debit' => (string) $entry->debit,
                'credit' => (string) $entry->credit,
                'narration' => $entry->narration,
                'reference' => $entry->reference,
            ])->values();
        }

        return $payload;
    }
}
