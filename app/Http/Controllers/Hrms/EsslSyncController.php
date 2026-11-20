<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\AttendancePunch;
use App\Domains\Hrms\Models\EsslSyncLog;
use App\Domains\Hrms\Services\EsslSyncService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EsslSyncController extends Controller
{
    public function __construct(protected EsslSyncService $sync) {}

    public function index(): View
    {
        return view('hrms.essl.index', [
            'logs' => EsslSyncLog::query()->latest()->limit(20)->get(),
            'punches' => AttendancePunch::query()->with('employee')->latest('punched_at')->limit(50)->get(),
            'enabled' => (bool) config('hrms.essl.enabled'),
        ]);
    }

    public function pull(Request $request): RedirectResponse
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDay()->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();
        $log = $this->sync->sync($from, $to);

        if ($log->status === 'failed') {
            return $this->flashError('eSSL sync failed: '.($log->message ?? 'unknown error'));
        }

        return $this->flashSuccess(
            "eSSL sync OK — fetched {$log->fetched}, imported {$log->imported}, unmatched {$log->unmatched}.",
            'hrms.essl.index'
        );
    }
}
