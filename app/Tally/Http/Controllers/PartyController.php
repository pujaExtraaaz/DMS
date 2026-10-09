<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\OpeningBalanceType;
use Tally\Audit\AuditLogger;
use Tally\Context\WorkspaceContext;
use Tally\Models\Party;
use Tally\Parties\PartyService;
use Tally\Tax\GstRegistrationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PartyController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Parties', 'message' => 'Select a company before managing parties.']);
        }

        $search = trim((string) $request->query('q', ''));
        $parties = $company->parties()
            ->with(['ledger', 'branch'])
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($like): void {
                    $query->where('legal_name', 'like', $like)
                        ->orWhere('gstin', 'like', $like)
                        ->orWhereHas('ledger', fn ($ledger) => $ledger->where('name', 'like', $like)->orWhere('code', 'like', $like));
                });
            })
            ->orderBy('type')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('tally::parties.index', [
            'company' => $company,
            'parties' => $parties,
            'filters' => $request->only(['q', 'type', 'status']),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        return view('tally::parties.form', $this->formData($context, new Party(['type' => 'customer', 'is_active' => true, 'country' => 'India'])));
    }

    public function store(Request $request, WorkspaceContext $context, PartyService $parties, AuditLogger $audit): RedirectResponse
    {
        $party = $parties->save($context->company(), $this->validated($request, $context));
        $audit->record('party_created', 'parties', $party, 'Party '.$party->ledger->name.' created.');

        return redirect()->route('books.tally.parties.show', $party)->with('status', 'Party created.');
    }

    public function show(WorkspaceContext $context, Party $party): View
    {
        $party->load(['ledger', 'branch']);

        return view('tally::parties.show', [
            'company' => $context->company(),
            'party' => $party,
        ]);
    }

    public function edit(WorkspaceContext $context, Party $party): View
    {
        $party->load('ledger');

        return view('tally::parties.form', $this->formData($context, $party));
    }

    public function update(Request $request, Party $party, PartyService $parties, AuditLogger $audit): RedirectResponse
    {
        $party = $parties->save($party->company, $this->validated($request, null, $party), $party);
        $audit->record('party_updated', 'parties', $party, 'Party '.$party->ledger->name.' updated.');

        return redirect()->route('books.tally.parties.show', $party)->with('status', 'Party updated.');
    }

    public function updateActivation(Request $request, Party $party, PartyService $parties): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);
        $parties->setActive($party, $request->boolean('is_active'));

        return back()->with('status', $party->fresh()->is_active ? 'Party activated.' : 'Party deactivated.');
    }

    public function destroy(Party $party, PartyService $parties): RedirectResponse
    {
        try {
            $parties->delete($party);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first('party'));
        }

        return redirect()->route('books.tally.parties.index')->with('status', 'Party deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(WorkspaceContext $context, Party $party): array
    {
        $company = $context->company() ?? $party->company;

        return [
            'company' => $company,
            'party' => $party,
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'types' => ['customer' => 'Customer', 'supplier' => 'Supplier'],
            'registrations' => GstRegistrationType::cases(),
            'balanceTypes' => OpeningBalanceType::cases(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?WorkspaceContext $context, ?Party $party = null): array
    {
        $companyId = $party?->company_id ?? $context?->company()?->id;

        $request->merge([
            'gstin' => $request->filled('gstin') ? strtoupper(trim((string) $request->input('gstin'))) : null,
            'pan' => $request->filled('pan') ? strtoupper(trim((string) $request->input('pan'))) : null,
        ]);

        $data = $request->validate([
            'type' => ['required', Rule::in(['customer', 'supplier'])],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:80'],
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', Rule::unique('ledgers', 'gstin')->where('company_id', $companyId)->ignore($party?->ledger_id)],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'gst_registration_type' => ['nullable', Rule::enum(GstRegistrationType::class)],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_type' => ['required', Rule::enum(OpeningBalanceType::class)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $companyId)],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
