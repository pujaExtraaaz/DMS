<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Services\CompOffService;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompOffController extends Controller
{
    public function __construct(protected CompOffService $compOff) {}

    public function index(): View
    {
        return view('hrms.comp-off.index', [
            'year' => (int) now()->year,
            'month' => (int) now()->month,
            'companies' => Company::orderBy('name')->get(),
            'summary' => session('comp_off_summary', []),
        ]);
    }

    public function credit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        try {
            $summary = $this->compOff->creditCompanyMonth(
                (int) $data['year'],
                (int) $data['month'],
                $data['company_id'] ?? null
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('hrms.comp-off.index')
                ->with('error', $e->getMessage());
        }

        $msg = count($summary) > 0
            ? 'Comp-off credited for '.count($summary).' employee(s).'
            : 'No new comp-off to credit (already credited or no OT/holiday work).';

        return redirect()
            ->route('hrms.comp-off.index')
            ->with('status', $msg)
            ->with('comp_off_summary', $summary);
    }
}
