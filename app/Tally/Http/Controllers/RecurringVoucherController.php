<?php

namespace Tally\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Accounting\RecurringVoucherService;
use Tally\Accounting\VoucherType;
use Tally\Context\WorkspaceContext;
use Tally\Models\RecurringVoucher;

class RecurringVoucherController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Recurring journals',
                'message' => 'Select a company before managing recurring vouchers.',
            ]);
        }

        return view('tally::recurring.index', [
            'company' => $company,
            'items' => RecurringVoucher::query()
                ->where('company_id', $company->id)
                ->with('branch')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        if (! $context->company()) {
            return redirect()->route('books.tally.recurring.index');
        }

        return view('tally::recurring.form', [
            'company' => $context->company(),
            'item' => new RecurringVoucher([
                'voucher_type' => VoucherType::Journal,
                'frequency' => 'monthly',
                'day_of_month' => 1,
                'auto_post' => false,
                'is_active' => true,
                'branch_id' => $context->branch()?->id,
                'start_date' => now()->toDateString(),
                'next_run_on' => now()->toDateString(),
            ]),
            'ledgers' => $context->company()->ledgers()->where('is_active', true)->orderBy('name')->get(),
            'branches' => $context->company()->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, RecurringVoucherService $service): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $service->save($context->company(), $user, $this->validated($request), $this->lines($request));

        return redirect()->route('books.tally.recurring.index')->with('status', 'Recurring voucher saved.');
    }

    public function edit(WorkspaceContext $context, RecurringVoucher $recurring): View
    {
        abort_unless($context->company() && $recurring->company_id === $context->company()->id, 404);

        return view('tally::recurring.form', [
            'company' => $context->company(),
            'item' => $recurring->load('lines'),
            'ledgers' => $context->company()->ledgers()->where('is_active', true)->orderBy('name')->get(),
            'branches' => $context->company()->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, RecurringVoucher $recurring, RecurringVoucherService $service): RedirectResponse
    {
        abort_unless($context->company() && $recurring->company_id === $context->company()->id, 404);
        /** @var User $user */
        $user = $request->user();
        $service->save($context->company(), $user, $this->validated($request), $this->lines($request), $recurring);

        return redirect()->route('books.tally.recurring.index')->with('status', 'Recurring voucher updated.');
    }

    public function run(Request $request, WorkspaceContext $context, RecurringVoucher $recurring, RecurringVoucherService $service): RedirectResponse
    {
        abort_unless($context->company() && $recurring->company_id === $context->company()->id, 404);
        $service->generate($recurring);

        return back()->with('status', 'Voucher generated from '.$recurring->name.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'branch_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'voucher_type' => ['required', 'in:journal,payment,receipt,contra,transfer'],
            'frequency' => ['required', 'in:weekly,monthly,quarterly,yearly'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:28'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'next_run_on' => ['required', 'date'],
            'narration' => ['nullable', 'string', 'max:1000'],
            'auto_post' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lines(Request $request): array
    {
        $rows = $request->input('entries', []);
        $lines = [];

        foreach ($rows as $row) {
            if (empty($row['ledger_id'])) {
                continue;
            }
            $lines[] = $row;
        }

        return $lines;
    }
}
