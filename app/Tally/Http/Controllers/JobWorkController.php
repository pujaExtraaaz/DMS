<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Inventory\JobWorkService;
use Tally\Models\JobWork;
use Tally\Models\Ledger;
use Tally\Models\Product;

class JobWorkController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Job work',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::job-works.index', [
            'company' => $company,
            'jobs' => JobWork::query()
                ->with(['vendor', 'product'])
                ->where('company_id', $company->id)
                ->latest('issue_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();
        abort_unless($company, 404);

        return view('tally::job-works.form', [
            'company' => $company,
            'job' => new JobWork(['issue_date' => now()->toDateString(), 'status' => 'open']),
            'vendors' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, JobWorkService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $job = $service->save($company, $context->branch(), $this->validated($request, $company->id));

        return redirect()->to(tally_route('books.tally.job-works.show', $job))
            ->with('status', 'Job work saved.');
    }

    public function show(WorkspaceContext $context, JobWork $jobWork): View
    {
        abort_unless($context->company() && $jobWork->company_id === $context->company()->id, 404);
        $jobWork->load(['vendor', 'product']);

        return view('tally::job-works.show', [
            'company' => $context->company(),
            'job' => $jobWork,
        ]);
    }

    public function receive(Request $request, WorkspaceContext $context, JobWork $jobWork, JobWorkService $service): RedirectResponse
    {
        abort_unless($context->company() && $jobWork->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'received_qty' => ['required', 'numeric', 'gt:0'],
        ]);

        $service->receive($jobWork, (string) $data['received_qty']);

        return back()->with('status', 'Receipt recorded.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $companyId): array
    {
        return $request->validate([
            'issue_date' => ['required', 'date'],
            'vendor_ledger_id' => ['required', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $companyId)],
            'product_id' => ['nullable', 'integer', Rule::exists('acct_products', 'id')->where('company_id', $companyId)],
            'issued_qty' => ['required', 'numeric', 'gt:0'],
            'received_qty' => ['nullable', 'numeric', 'min:0'],
            'job_charges' => ['nullable', 'numeric', 'min:0'],
            'narration' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['open', 'closed', 'cancelled'])],
        ]);
    }
}
