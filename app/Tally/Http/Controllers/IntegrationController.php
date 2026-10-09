<?php

namespace Tally\Http\Controllers;

use Tally\Audit\AuditLogger;
use Tally\Context\WorkspaceContext;
use Tally\Integration\IntegrationManager;
use Tally\Integration\WebhookDispatcher;
use Tally\Models\Integration;
use Tally\Models\IntegrationReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Integrations', 'message' => 'Select a company before managing integrations.']);
        }

        $integrations = $company->integrations()
            ->with('endpoint')
            ->when($request->query('status') === 'failed', fn ($query) => $query->where('last_status', 'failed'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('tally::integrations.index', [
            'company' => $company,
            'integrations' => $integrations,
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        return view('tally::integrations.form', [
            'company' => $context->company(),
            'integration' => new Integration(['type' => 'webhook', 'direction' => 'outbound', 'is_active' => true]),
            'events' => WebhookDispatcher::EVENTS,
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, IntegrationManager $manager, AuditLogger $audit): RedirectResponse
    {
        $integration = $manager->save($request->user(), $this->validated($request, $context->company()->id));
        $audit->record('integration_created', 'integrations', $integration, 'Integration '.$integration->name.' created.');

        return redirect()->route('books.tally.integrations.show', $integration)->with('status', 'Integration created.');
    }

    public function show(Request $request, Integration $integration): View
    {
        $integration->load('endpoint');
        $syncs = $integration->syncs()->latest('id')->paginate(20, ['*'], 'syncs');
        $deliveries = $integration->endpoint?->deliveries()->latest('id')->paginate(20, ['*'], 'deliveries') ?? collect();
        $references = IntegrationReference::query()
            ->where('company_id', $integration->company_id)
            ->latest('id')
            ->limit(20)
            ->get();

        return view('tally::integrations.show', [
            'integration' => $integration,
            'syncs' => $syncs,
            'deliveries' => $deliveries,
            'references' => $references,
            'failed' => $request->query('failed') === '1',
        ]);
    }

    public function edit(Integration $integration): View
    {
        $integration->load('endpoint');

        return view('tally::integrations.form', [
            'company' => $integration->company,
            'integration' => $integration,
            'events' => WebhookDispatcher::EVENTS,
        ]);
    }

    public function update(Request $request, Integration $integration, IntegrationManager $manager, AuditLogger $audit): RedirectResponse
    {
        $integration = $manager->save($request->user(), $this->validated($request, $integration->company_id), $integration);
        $audit->record('integration_updated', 'integrations', $integration, 'Integration '.$integration->name.' updated.');

        return redirect()->route('books.tally.integrations.show', $integration)->with('status', 'Integration updated.');
    }

    public function updateActivation(Request $request, Integration $integration): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);
        $active = $request->boolean('is_active');
        $integration->update(['is_active' => $active]);
        $integration->endpoint?->update(['is_active' => $active]);

        return back()->with('status', $active ? 'Integration enabled.' : 'Integration disabled.');
    }

    public function sync(Integration $integration, IntegrationManager $manager, AuditLogger $audit): RedirectResponse
    {
        $sync = $manager->sync($integration);
        $audit->record('integration_sync', 'integrations', $integration, 'Manual sync for '.$integration->name.' '.$sync->status.'.');

        return back()->with('status', $sync->status === 'completed' ? 'Sync queued.' : 'Sync failed.');
    }

    public function destroy(Integration $integration): RedirectResponse
    {
        if ($integration->syncs()->exists() || $integration->endpoint?->deliveries()->exists()) {
            return back()->with('error', 'This integration has sync or delivery history and cannot be deleted.');
        }

        $endpoint = $integration->endpoint;
        $integration->delete();
        $endpoint?->delete();

        return redirect()->route('books.tally.integrations.index')->with('status', 'Integration deleted.');
    }

    public function retry(Integration $integration, IntegrationManager $manager): RedirectResponse
    {
        $count = $manager->retryFailed($integration);

        return back()->with('status', $count.' failed delivery'.($count === 1 ? '' : 'ies').' retried.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $companyId): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['webhook', 'api', 'file'])],
            'direction' => ['required', Rule::in(['inbound', 'outbound', 'both'])],
            'external_system' => ['nullable', 'string', 'max:80'],
            'url' => ['required', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in(WebhookDispatcher::EVENTS)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return $data + [
            'company_id' => $companyId,
            'is_active' => true,
            'external_system' => $data['external_system'] ?? null,
            'api_key' => $data['api_key'] ?? '',
        ];
    }
}
