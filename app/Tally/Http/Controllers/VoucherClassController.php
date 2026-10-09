<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\VoucherType;
use Tally\Context\WorkspaceContext;
use Tally\Models\VoucherClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoucherClassController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Voucher Class',
                'message' => 'Select a company before creating a voucher class.',
            ]);
        }

        return view('tally::vouchers.classes', [
            'company' => $company,
            'classes' => VoucherClass::query()->with('ledger')->where('company_id', $company->id)->orderBy('voucher_type')->orderBy('name')->get(),
            'ledgers' => $company->ledgers()->where('is_active', true)->orderBy('name')->get(),
            'types' => VoucherType::cases(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'voucher_type' => ['required', Rule::in(array_map(fn (VoucherType $type) => $type->value, VoucherType::cases()))],
            'default_ledger_id' => ['nullable', Rule::exists('ledgers', 'id')->where('company_id', $company->id)],
        ]);

        VoucherClass::query()->create([
            'company_id' => $company->id,
            'voucher_type' => $data['voucher_type'],
            'name' => trim($data['name']),
            'default_ledger_id' => $data['default_ledger_id'] ?: null,
        ]);

        return back()->with('status', 'Voucher class saved.');
    }

    public function destroy(WorkspaceContext $context, VoucherClass $voucherClass): RedirectResponse
    {
        abort_unless($voucherClass->company_id === $context->company()?->id, 404);
        $voucherClass->delete();

        return back()->with('status', 'Voucher class deleted.');
    }
}
