<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Banking\BankFeedService;
use Tally\Context\WorkspaceContext;
use Tally\Integrations\IntegrationEndpointService;
use Tally\Models\BankAccount;
use Tally\Models\IntegrationEndpoint;

class IntegrationEndpointController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Integration endpoints',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::integration-endpoints.index', [
            'company' => $company,
            'accounts' => BankAccount::query()->where('company_id', $company->id)->orderBy('bank_name')->get(),
            'endpoints' => IntegrationEndpoint::query()
                ->with('bankAccount')
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, IntegrationEndpointService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(['webhook', 'bank_feed', 'payment_api', 'gateway', 'crm', 'payroll', 'expense'])],
            'url' => ['nullable', 'string', 'max:500'],
            'bank_account_id' => ['nullable', 'integer', Rule::exists('acct_bank_accounts', 'id')->where('company_id', $company->id)],
            'auth_header' => ['nullable', 'string', 'max:500'],
        ]);

        $service->save($company, $data);

        return back()->with('status', 'Endpoint saved.');
    }

    public function show(WorkspaceContext $context, IntegrationEndpoint $integrationEndpoint): View
    {
        abort_unless($context->company() && $integrationEndpoint->company_id === $context->company()->id, 404);
        $integrationEndpoint->load('bankAccount');

        return view('tally::integration-endpoints.show', [
            'company' => $context->company(),
            'endpoint' => $integrationEndpoint,
        ]);
    }

    public function sync(WorkspaceContext $context, IntegrationEndpoint $integrationEndpoint, BankFeedService $feeds): RedirectResponse
    {
        abort_unless($context->company() && $integrationEndpoint->company_id === $context->company()->id, 404);
        abort_unless($integrationEndpoint->kind === 'bank_feed', 404);

        try {
            $result = $feeds->pull($integrationEndpoint);
            $message = $result['imported'].' line(s) imported, '.$result['duplicates'].' duplicate(s) skipped.';
            if ($result['errors'] !== []) {
                return back()->with('status', $message)->with('error', implode(' ', array_slice($result['errors'], 0, 5)));
            }

            return back()->with('status', $message);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
