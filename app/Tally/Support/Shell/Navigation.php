<?php

namespace Tally\Support\Shell;

use Tally\Authorization\RoutePermissions;
use Tally\Authorization\CompanyAccess;
use Tally\Context\WorkspaceContext;
use Tally\Keyboard\ShortcutRegistry;
use Tally\Models\Company;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Models\Party;
use App\Models\User;
use Tally\Models\Voucher;
use Illuminate\Support\Facades\Route;

class Navigation
{
    /**
     * @return list<array<string, mixed>>
     */
    public function sections(): array
    {
        $sections = config('navigation.sections', []);
        $user = auth()->user();

        if (! $user) {
            return $sections;
        }

        $visible = [];

        foreach ($sections as $section) {
            if (! isset($section['children'])) {
                $visible[] = $section;

                continue;
            }

            $section['children'] = array_values(array_filter(
                $section['children'],
                function (array $item) use ($user): bool {
                    $permission = RoutePermissions::for($item['route'] ?? null, 'GET');

                    return $permission === null || $user->can($permission);
                },
            ));

            if ($section['children'] !== []) {
                $visible[] = $section;
            }
        }

        return $visible;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function placeholders(): array
    {
        $pages = [];

        foreach ($this->sections() as $section) {
            foreach ($section['children'] ?? [] as $child) {
                if (! empty($child['placeholder'])) {
                    $pages[] = $child + ['section' => $section['label']];
                }
            }
        }

        return $pages;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function placeholder(string $key): ?array
    {
        foreach ($this->placeholders() as $page) {
            if ($page['key'] === $key) {
                return $page;
            }
        }

        return null;
    }

    public function shortcutKeys(array $item): ?string
    {
        $id = $item['shortcut'] ?? null;

        if (! is_string($id) || $id === '') {
            return null;
        }

        $shortcut = collect(app(ShortcutRegistry::class)->resolved())->firstWhere('id', $id);

        if (! is_array($shortcut) || ($shortcut['enabled'] ?? true) !== true) {
            return null;
        }

        return $shortcut['keys'];
    }

    public function href(array $item): string
    {
        $company = app(WorkspaceContext::class)->company();

        if (($item['route'] ?? null) === 'branches.current' && $company) {
            return tally_route('companies.branches.index', $company);
        }

        if (($item['route'] ?? null) === 'financial-years.current' && $company) {
            return tally_route('companies.financial-years.index', $company);
        }

        return tally_route($item['route'], $item['params'] ?? []);
    }

    public function isCurrent(array $item): bool
    {
        if (isset($item['voucher'])) {
            return $this->voucherIsCurrent((string) $item['voucher']);
        }

        $patterns = $item['active'] ?? (isset($item['route']) ? [$item['route']] : []);

        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function voucherIsCurrent(string $type): bool
    {
        $route = request()->route()?->getName() ?? '';

        if (in_array($route, ["vouchers.$type.create", "vouchers.$type.store"], true)) {
            return true;
        }

        if ($type === 'journal' && in_array($route, ['vouchers.create', 'vouchers.store'], true)) {
            return true;
        }

        $voucher = request()->route('voucher');

        if ($voucher instanceof \Tally\Models\Voucher) {
            return $voucher->voucher_type->value === $type;
        }

        if ($route === 'vouchers.index') {
            $selected = request('voucher_type');

            return $selected ? $selected === $type : $type === 'journal';
        }

        return false;
    }

    public function sectionIsCurrent(array $section): bool
    {
        if ($this->isCurrent($section)) {
            return true;
        }

        foreach ($section['children'] ?? [] as $child) {
            if ($this->isCurrent($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{label: string, group: string, url: string, keywords: string}>
     */
    public function searchIndex(): array
    {
        $items = [];

        foreach ($this->sections() as $section) {
            if (isset($section['route']) && tally_route_has($section['route'])) {
                $items[] = [
                    'label' => $section['label'],
                    'group' => 'Menu',
                    'url' => tally_route($section['route']),
                    'keywords' => strtolower($section['label']),
                ];
            }

            foreach ($section['children'] ?? [] as $child) {
                if (! isset($child['route']) || ! tally_route_has($child['route'])) {
                    continue;
                }

                $items[] = [
                    'label' => $child['label'],
                    'group' => $section['label'],
                    'url' => $this->href($child),
                    'keywords' => strtolower($section['label'].' '.$child['label'].' '.($child['keywords'] ?? '')),
                ];
            }
        }

        $ids = CompanyAccess::ids(auth()->user());

        Company::query()
            ->where('is_active', true)
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids === [] ? [0] : $ids))
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name', 'city', 'gstin'])
            ->each(function (Company $company) use (&$items) {
                $items[] = [
                    'label' => $company->name,
                    'group' => 'Companies',
                    'url' => tally_route('companies.show', $company),
                    'keywords' => strtolower(trim($company->name.' '.$company->city.' '.$company->gstin)),
                ];
            });

        $company = app(WorkspaceContext::class)->company();

        if ($company) {
            Ledger::query()
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'name', 'code'])
                ->each(function (Ledger $ledger) use (&$items) {
                    $items[] = [
                        'label' => $ledger->name,
                        'group' => 'Ledgers',
                        'url' => tally_route('ledgers.show', $ledger),
                        'keywords' => strtolower(trim($ledger->name.' '.$ledger->code)),
                    ];
                });
        }

        return $items;
    }

    /**
     * Posted documents are loaded when the user types, so voucher numbers
     * stay out of screens that are filtered by year, branch, or status.
     *
     * @return list<array{label: string, group: string, url: string, keywords: string}>
     */
    public function searchRecords(?User $user, string $query): array
    {
        $query = trim($query);

        if ($user === null || $query === '') {
            return [];
        }

        $company = app(WorkspaceContext::class)->company();
        $year = app(WorkspaceContext::class)->financialYear();

        if ($company === null) {
            return [];
        }

        $like = '%'.addcslashes($query, '%_\\').'%';
        $items = [];

        if ($user->can('masters.view')) {
            Ledger::query()
                ->where('company_id', $company->id)
                ->where(function ($rows) use ($like) {
                    $rows->where('name', 'like', $like)->orWhere('code', 'like', $like);
                })
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'code'])
                ->each(function (Ledger $ledger) use (&$items) {
                    $items[] = [
                        'label' => $ledger->name,
                        'group' => 'Ledgers',
                        'url' => tally_route('ledgers.show', $ledger),
                        'keywords' => strtolower(trim($ledger->name.' '.$ledger->code)),
                    ];
                });
        }

        if ($user->can('parties.view')) {
            Party::query()
                ->where('company_id', $company->id)
                ->where(function ($rows) use ($like) {
                    $rows->where('legal_name', 'like', $like)->orWhere('gstin', 'like', $like);
                })
                ->orderBy('legal_name')
                ->limit(8)
                ->get(['id', 'legal_name', 'gstin'])
                ->each(function (Party $party) use (&$items) {
                    $items[] = [
                        'label' => $party->legal_name,
                        'group' => 'Parties',
                        'url' => tally_route('parties.show', $party),
                        'keywords' => strtolower(trim($party->legal_name.' '.$party->gstin)),
                    ];
                });
        }

        if ($year && ($user->can('sales.view') || $user->can('purchase.view'))) {
            Invoice::query()
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->where(function ($rows) use ($like) {
                    $rows->where('invoice_number', 'like', $like)->orWhere('narration', 'like', $like);
                })
                ->latest('id')
                ->limit(8)
                ->get()
                ->each(function (Invoice $invoice) use (&$items, $user) {
                    $sales = $invoice->kind->usesCostOfGoods();

                    if ($sales && ! $user->can('sales.view')) {
                        return;
                    }

                    if (! $sales && ! $user->can('purchase.view')) {
                        return;
                    }

                    $items[] = [
                        'label' => $invoice->invoice_number,
                        'group' => $invoice->kind->label(),
                        'url' => tally_route($invoice->kind->routeName('show'), $invoice),
                        'keywords' => strtolower(trim($invoice->invoice_number.' '.$invoice->narration)),
                    ];
                });
        }

        if ($year && $user->can('accounting.view')) {
            Voucher::query()
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->where(function ($rows) use ($like) {
                    $rows->where('voucher_number', 'like', $like)->orWhere('narration', 'like', $like);
                })
                ->latest('id')
                ->limit(8)
                ->get(['id', 'voucher_number', 'voucher_type', 'narration'])
                ->each(function (Voucher $voucher) use (&$items) {
                    $items[] = [
                        'label' => $voucher->voucher_number,
                        'group' => $voucher->voucher_type->label(),
                        'url' => tally_route('vouchers.show', $voucher),
                        'keywords' => strtolower(trim($voucher->voucher_number.' '.$voucher->narration)),
                    ];
                });
        }

        return $items;
    }
}
