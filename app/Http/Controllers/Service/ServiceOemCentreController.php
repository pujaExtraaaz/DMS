<?php

namespace App\Http\Controllers\Service;

use App\Domains\Organization\Models\DocumentNumberSeries;
use App\Domains\Service\Models\ServiceOemCentre;
use App\Domains\Service\Models\ServiceSlaPolicy;
use App\Http\Controllers\Controller;
use App\Support\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceOemCentreController extends Controller
{
    public function index(DocumentNumberService $numbers): View
    {
        $companyId = auth()->user()?->company_id;
        $srvSeries = DocumentNumberSeries::query()
            ->where('document_type', 'SRV')
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->orderByRaw('company_id is null')
            ->first();

        return view('service.settings.oem-centres', [
            'items' => ServiceOemCentre::query()->orderBy('name')->paginate(30),
            'slaPolicies' => ServiceSlaPolicy::query()->orderBy('priority')->get(),
            'srvSeries' => $srvSeries,
            'srvPreview' => $srvSeries ? $numbers->peekFromSeries($srvSeries) : null,
        ]);
    }

    public function updateSla(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'policies' => 'required|array',
            'policies.*.id' => 'required|exists:service_sla_policies,id',
            'policies.*.resolution_hours' => 'required|integer|min:1|max:720',
            'policies.*.escalate_after_hours' => 'nullable|integer|min:1|max:720',
        ]);

        foreach ($validated['policies'] as $index => $row) {
            $policy = ServiceSlaPolicy::query()->find($row['id']);
            if (! $policy) {
                continue;
            }
            $policy->update([
                'resolution_hours' => $row['resolution_hours'],
                'escalate_after_hours' => $row['escalate_after_hours'] ?? $policy->escalate_after_hours,
                'is_active' => $request->boolean("policies.$index.is_active"),
            ]);
        }

        return $this->flashSuccess('SLA policies updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        ServiceOemCentre::create($validated);

        return $this->flashSuccess('OEM centre saved.');
    }

    public function update(Request $request, ServiceOemCentre $oemCentre): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $oemCentre->update($validated);

        return $this->flashSuccess('OEM centre updated.');
    }
}
