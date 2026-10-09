<?php

namespace Tally\Http\Controllers;

use Tally\Audit\AuditedModels;
use Tally\Models\AuditLog;
use Tally\Models\Company;
use Tally\Support\Queries\DateRange;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    /** @var list<string> */
    public const SECURITY_ACTIONS = [
        'login', 'logout', 'login_failed', 'login_locked', 'password_changed',
        'session_expired', 'api_unauthenticated',
    ];

    public function index(Request $request): View
    {
        return $this->list($request, 'Audit trail', false);
    }

    public function security(Request $request): View
    {
        return $this->list($request, 'Security events', true);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load(['user', 'company', 'branch', 'financialYear']);
        $previous = $auditLog->previous_values ?? [];
        $current = $auditLog->new_values ?? [];
        $keys = array_values(array_unique([...array_keys($previous), ...array_keys($current)]));

        return view('tally::audit.show', [
            'entry' => $auditLog,
            'keys' => $keys,
        ]);
    }

    private function list(Request $request, string $title, bool $security): View
    {
        $filters = [
            'q' => trim($request->string('q')->toString()),
            'module' => trim($request->string('module')->toString()),
            'action' => trim($request->string('action')->toString()),
            'entity' => trim($request->string('entity')->toString()),
            'user_id' => trim($request->string('user_id')->toString()),
            'company_id' => trim($request->string('company_id')->toString()),
            'branch_id' => trim($request->string('branch_id')->toString()),
            'financial_year_id' => trim($request->string('financial_year_id')->toString()),
            'from' => trim($request->string('from')->toString()),
            'to' => trim($request->string('to')->toString()),
        ];

        $entries = AuditLog::query()
            ->with(['user', 'company', 'branch', 'financialYear'])
            ->when($security, fn ($query) => $query->whereIn('action', self::SECURITY_ACTIONS))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '%_\\').'%';
                $query->where('description', 'like', $like);
            })
            ->when($filters['module'] !== '', fn ($query) => $query->where('module', $filters['module']))
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['entity'] !== '', fn ($query) => $query->where('auditable_type', $filters['entity']))
            ->when($filters['user_id'] !== '', fn ($query) => $query->where('user_id', (int) $filters['user_id']))
            ->when($filters['company_id'] !== '', fn ($query) => $query->where('company_id', (int) $filters['company_id']))
            ->when($filters['branch_id'] !== '', fn ($query) => $query->where('branch_id', (int) $filters['branch_id']))
            ->when($filters['financial_year_id'] !== '', fn ($query) => $query->where('financial_year_id', (int) $filters['financial_year_id']))
            ->tap(fn ($query) => DateRange::apply($query, 'created_at', $filters['from'], $filters['to'], true))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('tally::audit.index', [
            'title' => $title,
            'security' => $security,
            'entries' => $entries,
            'filters' => $filters,
            'modules' => array_values(array_unique(array_values(app(AuditedModels::class)->map()))),
            'actions' => $security ? self::SECURITY_ACTIONS : [
                'created', 'updated', 'activated', 'deactivated', 'deleted', 'delete_blocked',
                'change_blocked', 'posted', 'cancelled', 'backup_completed', 'restore_completed',
                'import_completed', 'export_completed',
            ],
            'entities' => array_keys(app(AuditedModels::class)->map()),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'branches' => \Tally\Models\Branch::query()->orderBy('name')->get(['id', 'name']),
            'years' => \Tally\Models\FinancialYear::query()->orderBy('name')->get(['id', 'name']),
            'users' => \App\Models\User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
