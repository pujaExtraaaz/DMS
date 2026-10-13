<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Assets\DepreciationService;
use Tally\Context\WorkspaceContext;
use Tally\Models\DepreciationEntry;

class DepreciationController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Depreciation',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::depreciation.index', [
            'company' => $company,
            'entries' => DepreciationEntry::query()
                ->with('fixedAsset')
                ->where('company_id', $company->id)
                ->latest('period')
                ->latest('id')
                ->paginate(40),
            'defaultPeriod' => now()->format('Y-m'),
        ]);
    }

    public function run(Request $request, WorkspaceContext $context, DepreciationService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'entry_date' => ['nullable', 'date'],
        ]);

        $created = $service->runForPeriod(
            $company,
            $data['period'],
            $data['entry_date'] ?? null,
            $request->user(),
        );

        return redirect()->to(tally_route('books.tally.depreciation.index'))
            ->with('status', count($created) === 0
                ? 'No depreciation posted for '.$data['period'].'.'
                : count($created).' depreciation entries created for '.$data['period'].'.');
    }
}
