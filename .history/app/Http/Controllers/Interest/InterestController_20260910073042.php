<?php

namespace App\Http\Controllers\Interest;

use App\Domains\Interest\Models\InterestDocument;
use App\Domains\Interest\Models\InterestLedger;
use App\Domains\Interest\Models\InterestRule;
use App\Domains\Interest\Services\InterestService;
use App\Domains\Master\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterestController extends Controller
{
    public function __construct(
        protected InterestService $interestService,
    ) {}

    public function index(Request $request): View
    {
        $ledgers = InterestLedger::query()
            ->with(['customer', 'invoice', 'rule'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->latest('as_of_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('interest.index', [
            'ledgers' => $ledgers,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'rules' => InterestRule::query()->orderByDesc('is_default')->orderBy('name')->limit(10)->get(),
        ]);
    }

    public function rules(Request $request): View
    {
        $items = InterestRule::query()
            ->with('customer')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('interest.rules-index', compact('items'));
    }

    public function createRule(): View
    {
        return view('interest.rule-form', [
            'item' => new InterestRule(['annual_rate' => 18, 'grace_days' => 0, 'is_active' => true]),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeRule(Request $request): RedirectResponse
    {
        InterestRule::create($this->validatedRule($request));

        return $this->flashSuccess('Interest rule created.', 'interest.rules.index');
    }

    public function editRule(InterestRule $interest_rule): View
    {
        return view('interest.rule-form', [
            'item' => $interest_rule,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateRule(Request $request, InterestRule $interest_rule): RedirectResponse
    {
        $interest_rule->update($this->validatedRule($request, $interest_rule));

        return $this->flashSuccess('Interest rule updated.', 'interest.rules.index');
    }

    public function preview(): RedirectResponse
    {
        $rows = $this->interestService->previewOverdue();

        return $this->flashSuccess('Interest preview generated for '.$rows->count().' overdue invoice(s).');
    }

    public function post(Request $request, InterestLedger $interest_ledger): RedirectResponse
    {
        if ($interest_ledger->status !== 'preview') {
            return $this->flashError('Only preview ledgers can be posted.');
        }

        $document = $this->interestService->postLedger($interest_ledger, $request->user());

        return $this->flashSuccess('Interest posted as '.$document->document_no.'.');
    }

    public function documents(Request $request): View
    {
        $items = InterestDocument::query()
            ->with(['customer', 'ledger'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('interest.documents', compact('items'));
    }

    protected function validatedRule(Request $request, ?InterestRule $item = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'annual_rate' => 'required|numeric|min:0|max:100',
            'grace_days' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            InterestRule::query()
                ->when($item, fn ($q) => $q->where('id', '!=', $item->id))
                ->update(['is_default' => false]);
        }

        return $data;
    }
}
